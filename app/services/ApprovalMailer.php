<?php

class ApprovalMailer
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private int $timeout;

    public function __construct(array $config)
    {
        $this->host = trim((string)($config['host'] ?? ''));
        $this->port = (int)($config['port'] ?? 0);
        $this->encryption = strtolower(trim((string)($config['encryption'] ?? '')));
        $this->username = trim((string)($config['username'] ?? ''));
        $this->password = (string)($config['password'] ?? '');
        $this->fromEmail = trim((string)($config['from_email'] ?? $this->username));
        $this->fromName = trim((string)($config['from_name'] ?? 'Parents Council'));
        $this->timeout = (int)($config['timeout'] ?? 20);

        if ($this->host === '' || $this->port <= 0) {
            throw new InvalidArgumentException('SMTP host/port is missing.');
        }

        if ($this->username === '' || $this->password === '') {
            throw new InvalidArgumentException('SMTP credentials are missing.');
        }

        if ($this->fromEmail === '' || !filter_var($this->fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('SMTP from email is invalid.');
        }
    }

    public function sendApprovalEmail(string $toEmail, string $link): void
    {
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Recipient email is invalid.');
        }

        $subject = 'Η αίτησή σας εγκρίθηκε';
        $body =
            "Η εγγραφή σας εγκρίθηκε από τον διαχειριστή.\r\n\r\n" .
            "Μπορείτε πλέον να προχωρήσετε για να ολοκληρώσετε τη διαδικασία της εγγραφής σας.\r\n\r\n" .
            "Παρακαλούμε πατήστε τον παρακάτω σύνδεσμο:\r\n\r\n" .
            $link . "\r\n\r\n" .
            "Ο σύνδεσμος ισχύει για περιορισμένο χρονικό διάστημα.";

        $socket = $this->openConnection();

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO localhost', [250]);

            if ($this->encryption === 'tls' || $this->encryption === 'starttls' || $this->port === 587) {
                $this->command($socket, 'STARTTLS', [220]);

                $cryptoEnabled = @stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                );

                if ($cryptoEnabled !== true) {
                    throw new RuntimeException('SMTP STARTTLS handshake failed.');
                }

                $this->command($socket, 'EHLO localhost', [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($this->username), [334]);
            $this->command($socket, base64_encode($this->password), [235]);
            $this->command($socket, 'MAIL FROM:<' . $this->fromEmail . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);
            $this->write($socket, $this->buildMessage($toEmail, $subject, $body) . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function openConnection()
    {
        $transport = ($this->encryption === 'ssl' || $this->encryption === 'smtps' || $this->port === 465)
            ? 'ssl://'
            : 'tcp://';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            throw new RuntimeException('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($socket, $this->timeout);

        return $socket;
    }

    private function buildMessage(string $toEmail, string $subject, string $body): string
    {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $encodedFromName . ' <' . $this->fromEmail . '>',
            'To: <' . $toEmail . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function command($socket, string $command, array $expectedCodes): string
    {
        $this->write($socket, $command . "\r\n");
        return $this->expect($socket, $expectedCodes);
    }

    private function write($socket, string $payload): void
    {
        $written = fwrite($socket, $payload);
        if ($written === false || $written < strlen($payload)) {
            throw new RuntimeException('Failed to write to SMTP server.');
        }
    }

    private function expect($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('Empty response from SMTP server.');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }

        return $response;
    }
}
