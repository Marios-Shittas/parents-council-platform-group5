<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/TokenValidator.php';
require_once __DIR__ . '/ApprovalMailer.php';
require_once __DIR__ . '/PaymentReceiptService.php';

class SubscriptionJccService
{
    private mysqli $conn;
    private TokenValidator $tokenValidator;

    // Leitourgia __construct: xeirizetai to antistoixo kommati tis selidas i tou service.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->tokenValidator = new TokenValidator($conn);
    }

    // Leitourgia handleRequest: xeirizetai to antistoixo kommati tis selidas i tou service.
    public function handleRequest(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $orderId = trim((string) ($_GET['orderId'] ?? $_GET['mdOrder'] ?? ''));
        $membershipPaymentId = (int) ($_GET['mp'] ?? 0);
        $insurancePaymentId = (int) ($_GET['ip'] ?? 0);

        if ($token === '' || $orderId === '' || $membershipPaymentId <= 0) {
            $this->respond(400, [
                'success' => false,
                'message' => 'Missing required callback data.',
            ]);
            return;
        }

        $userId = $this->tokenValidator->getUserIdByToken($token, 'parent', 'waiting_payment', true);
        if ($userId === null) {
            $this->respond(401, [
                'success' => false,
                'message' => 'Invalid or expired token.',
            ]);
            return;
        }

        try {
            $statusResponse = $this->getJccOrderStatus($orderId);
            $orderStatus = (int) ($statusResponse['orderStatus'] ?? -1);
            $paymentStatus = $this->mapOrderStatusToPaymentStatus($orderStatus);
            $transactionId = $this->resolveTransactionId($statusResponse, $orderId);
            $activationCredentials = null;

            $this->conn->begin_transaction();

            $this->updatePaymentStatus($membershipPaymentId, $userId, $paymentStatus, $transactionId);
            if ($insurancePaymentId > 0) {
                $this->updatePaymentStatus($insurancePaymentId, $userId, $paymentStatus, $transactionId);
            }

            if ($paymentStatus === 'completed') {
                if ($insurancePaymentId > 0) {
                    $this->insertInsuranceChildrenPayments($userId, $insurancePaymentId);
                }

                $activationCredentials = $this->activateUserWithTemporaryPassword($userId);
                $this->insertLog(
                    $userId,
                    'PAYMENT_COMPLETED',
                    'JCC payment completed. Order ID: ' . $orderId . ', Transaction ID: ' . $transactionId
                );
            } elseif ($paymentStatus === 'pending') {
                $this->insertLog(
                    $userId,
                    'PAYMENT_PENDING',
                    'JCC payment pending. Order ID: ' . $orderId . ', Transaction ID: ' . $transactionId
                );
            } else {
                $this->insertLog(
                    $userId,
                    'PAYMENT_FAILED',
                    'JCC payment failed. Order ID: ' . $orderId . ', Transaction ID: ' . $transactionId
                );
            }

            $this->conn->commit();

            $receiptEmailSent = false;
            $receiptEmailMessage = null;
            if ($paymentStatus === 'completed') {
                $receiptPaymentIds = [$membershipPaymentId];
                if ($insurancePaymentId > 0) {
                    $receiptPaymentIds[] = $insurancePaymentId;
                }

                try {
                    $receiptSummary = (new PaymentReceiptService($this->conn))
                        ->sendReceiptForPayments($userId, $receiptPaymentIds, 'JCC subscription');
                    $receiptEmailSent = true;
                    $this->insertLog(
                        $userId,
                        'PAYMENT_RECEIPT_SENT',
                        'Receipt email sent for payment IDs: ' . implode(', ', $receiptPaymentIds) . ' to ' . $receiptSummary['email']
                    );
                } catch (Throwable $receiptError) {
                    $receiptEmailMessage = 'Payment completed, but failed to send receipt email.';
                    $this->insertLog(
                        $userId,
                        'PAYMENT_RECEIPT_FAILED',
                        'Receipt email failed for payment IDs: ' . implode(', ', $receiptPaymentIds) . '. Error: ' . $receiptError->getMessage()
                    );
                }
            }

            $credentialsEmailSent = false;
            $credentialsEmailMessage = null;
            if ($paymentStatus === 'completed' && is_array($activationCredentials)) {
                try {
                    $this->sendActivationCredentialsEmail(
                        (string) ($activationCredentials['email'] ?? ''),
                        (string) ($activationCredentials['temporary_password'] ?? '')
                    );
                    $credentialsEmailSent = true;
                    $this->insertLog($userId, 'ACTIVATION_CREDENTIALS_SENT', 'Activation credentials email sent.');
                } catch (Throwable $emailError) {
                    $credentialsEmailMessage = 'Payment completed, but failed to send credentials email.';
                    $this->insertLog(
                        $userId,
                        'ACTIVATION_CREDENTIALS_EMAIL_FAILED',
                        'Payment completed but credentials email failed: ' . $emailError->getMessage()
                    );
                }
            }

            $response = [
                'success' => true,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'transaction_id' => $transactionId,
            ];

            if ($paymentStatus === 'completed' && is_array($activationCredentials)) {
                $response['credentials_email_sent'] = $credentialsEmailSent;
                if ($credentialsEmailMessage !== null) {
                    $response['credentials_email_message'] = $credentialsEmailMessage;
                }
            }

            if ($paymentStatus === 'completed') {
                $response['receipt_email_sent'] = $receiptEmailSent;
                if ($receiptEmailMessage !== null) {
                    $response['receipt_email_message'] = $receiptEmailMessage;
                }
            }

            $this->respond(200, $response);
        } catch (Throwable $e) {
            $this->conn->rollback();
            $this->respond(500, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Leitourgia getJccOrderStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function getJccOrderStatus(string $orderId): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for JCC integration.');
        }

        $requestFields = [
            'userName' => JCC_API_LOGIN,
            'password' => JCC_API_PASSWORD,
            'orderId' => $orderId,
        ];

        $ch = curl_init(JCC_ORDER_STATUS_URL);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize JCC status request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($requestFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false || $curlError !== '') {
            throw new RuntimeException('Failed to connect to JCC gateway: ' . $curlError);
        }

        $rawResponse = ltrim($rawResponse, "\xEF\xBB\xBF");
        $response = json_decode($rawResponse, true);
        if (!is_array($response)) {
            $snippet = substr(trim($rawResponse), 0, 220);
            throw new RuntimeException('Invalid response from JCC status API (HTTP ' . $httpCode . '): ' . $snippet);
        }

        if (($response['errorCode'] ?? '0') !== '0' && ($response['errorCode'] ?? 0) !== 0) {
            $errorMessage = (string) ($response['errorMessage'] ?? 'Unknown JCC status error.');
            throw new RuntimeException('JCC status error: ' . $errorMessage);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('JCC status request failed with HTTP ' . $httpCode . '.');
        }

        return $response;
    }

    // Leitourgia mapOrderStatusToPaymentStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function mapOrderStatusToPaymentStatus(int $orderStatus): string
    {
        if ($orderStatus === 2) {
            return 'completed';
        }

        if ($orderStatus === 0 || $orderStatus === 1 || $orderStatus === 5) {
            return 'pending';
        }

        if ($orderStatus === 4) {
            return 'refunded';
        }

        return 'failed';
    }

    // Leitourgia resolveTransactionId: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function resolveTransactionId(array $statusResponse, string $fallbackOrderId): string
    {
        if (isset($statusResponse['transactionAttributes']) && is_array($statusResponse['transactionAttributes'])) {
            foreach ($statusResponse['transactionAttributes'] as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $name = strtolower((string) ($attribute['name'] ?? ''));
                $value = trim((string) ($attribute['value'] ?? ''));
                if ($name === 'paymentnetrefnum' && $value !== '') {
                    return $value;
                }
            }
        }

        $authRefNum = trim((string) ($statusResponse['authRefNum'] ?? ''));
        if ($authRefNum !== '') {
            return $authRefNum;
        }

        if (isset($statusResponse['attributes']) && is_array($statusResponse['attributes'])) {
            foreach ($statusResponse['attributes'] as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $name = (string) ($attribute['name'] ?? '');
                $value = trim((string) ($attribute['value'] ?? ''));
                if (strtolower($name) === 'mdorder' && $value !== '') {
                    return $value;
                }
            }
        }

        return $fallbackOrderId;
    }

    // Leitourgia updatePaymentStatus: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function updatePaymentStatus(
        int $paymentId,
        int $userId,
        string $status,
        ?string $transactionId
    ): void
    {
        if ($transactionId !== null && $transactionId !== '') {
            $stmt = $this->conn->prepare(
                'UPDATE Payments
                 SET payment_status = ?, transaction_id = ?
                 WHERE payment_id = ? AND user_id = ? LIMIT 1'
            );

            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare payment status update.');
            }

            $stmt->bind_param('ssii', $status, $transactionId, $paymentId, $userId);
        } else {
            $stmt = $this->conn->prepare(
                'UPDATE Payments SET payment_status = ? WHERE payment_id = ? AND user_id = ? LIMIT 1'
            );

            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare payment status update.');
            }

            $stmt->bind_param('sii', $status, $paymentId, $userId);
        }

        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();

            if (!$this->paymentExistsForUser($paymentId, $userId)) {
                throw new RuntimeException('Payment not found for callback update.');
            }

            return;
        }

        $stmt->close();
    }

    // Leitourgia paymentExistsForUser: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function paymentExistsForUser(int $paymentId, int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM Payments WHERE payment_id = ? AND user_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to verify payment existence.');
        }

        $stmt->bind_param('ii', $paymentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = (bool) ($result && $result->fetch_assoc());
        $stmt->close();

        return $exists;
    }

    // Leitourgia insertInsuranceChildrenPayments: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function insertInsuranceChildrenPayments(int $userId, int $insurancePaymentId): void
    {
        $childrenStmt = $this->conn->prepare('SELECT child_id FROM Children WHERE user_id = ?');
        if ($childrenStmt === false) {
            throw new RuntimeException('Failed to load children for insurance payment.');
        }

        $childrenStmt->bind_param('i', $userId);
        $childrenStmt->execute();
        $result = $childrenStmt->get_result();

        $insertStmt = $this->conn->prepare(
            'INSERT INTO InsurancePayments (payment_id, child_id)
             SELECT ?, ?
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1 FROM InsurancePayments WHERE payment_id = ? AND child_id = ?
             )'
        );

        if ($insertStmt === false) {
            $childrenStmt->close();
            throw new RuntimeException('Failed to prepare insurance payment insert.');
        }

        while ($row = $result->fetch_assoc()) {
            $childId = (int) ($row['child_id'] ?? 0);
            if ($childId <= 0) {
                continue;
            }

            $insertStmt->bind_param('iiii', $insurancePaymentId, $childId, $insurancePaymentId, $childId);
            $insertStmt->execute();
        }

        $insertStmt->close();
        $childrenStmt->close();
    }

    // Leitourgia activateUserWithTemporaryPassword: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function activateUserWithTemporaryPassword(int $userId): ?array
    {
        $temporaryPassword = $this->generateTemporaryPassword();
        $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "UPDATE Users
             SET account_status = 'active', password = ?, token = NULL, token_expiry = NULL
             WHERE user_id = ? AND account_status = 'waiting_payment'
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare user activation.');
        }

        $stmt->bind_param('si', $hashedPassword, $userId);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            return null;
        }

        $stmt->close();

        $email = $this->getUserEmail($userId);
        if ($email === '') {
            throw new RuntimeException('Failed to load user email for credentials notification.');
        }

        return [
            'email' => $email,
            'temporary_password' => $temporaryPassword,
        ];
    }

    // Leitourgia getUserEmail: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function getUserEmail(int $userId): string
    {
        $stmt = $this->conn->prepare('SELECT email FROM Users WHERE user_id = ? LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare user email lookup.');
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return trim((string) ($row['email'] ?? ''));
    }

    // Leitourgia generateTemporaryPassword: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function generateTemporaryPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }

    // Leitourgia sendActivationCredentialsEmail: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function sendActivationCredentialsEmail(string $toEmail, string $temporaryPassword): void
    {
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Recipient email is invalid for credentials email.');
        }

        if ($temporaryPassword === '') {
            throw new InvalidArgumentException('Temporary password is missing.');
        }

        try {
            $mailer = new ApprovalMailer([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);

            $mailer->sendActivationCredentialsEmail($toEmail, $temporaryPassword);
            return;
        } catch (Throwable $smtpError) {
            $subject = ApprovalMailer::activationCredentialsSubject();
            $message = str_replace("\r\n", "\n", ApprovalMailer::activationCredentialsBody($temporaryPassword));
            $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';

            if (!mail($toEmail, $subject, $message, $headers)) {
                throw new RuntimeException('Failed to send activation credentials email: ' . $smtpError->getMessage());
            }
        }
    }

    // Leitourgia insertLog: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function insertLog(int $userId, string $action, string $description): void
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)'
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare callback log insert.');
        }

        $stmt->bind_param('iss', $userId, $action, $description);
        $stmt->execute();
        $stmt->close();
    }

    // Leitourgia respond: xeirizetai to antistoixo kommati tis selidas i tou service.
    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

$service = new SubscriptionJccService($conn);
$service->handleRequest();
$conn->close();
