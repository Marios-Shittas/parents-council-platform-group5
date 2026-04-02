<?php

namespace Kozzy\ParentsCouncilPlatformGroup5\services;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class EmailApproval
{
    private PHPMailer $mailer;
    private array $transportModes = [];

    public function __construct(array $smtpConfig)
    {
        $host = trim((string) ($smtpConfig['host'] ?? ''));
        $port = (int) ($smtpConfig['port'] ?? 0);
        $username = trim((string) ($smtpConfig['username'] ?? ''));
        $password = (string) ($smtpConfig['password'] ?? '');
        $encryption = trim((string) ($smtpConfig['encryption'] ?? ''));
        $fromEmail = trim((string) ($smtpConfig['from_email'] ?? $username));
        $fromName = trim((string) ($smtpConfig['from_name'] ?? 'Parents Council'));

        if ($host === '' || $port <= 0) {
            throw new \InvalidArgumentException('SMTP host/port is missing. Set SMTP_HOST and SMTP_PORT.');
        }

        if ($username === '' || $password === '') {
            throw new \InvalidArgumentException('SMTP credentials are missing. Set SMTP_USER and SMTP_PASS.');
        }

        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('SMTP from email is invalid. Set SMTP_FROM_EMAIL (or SMTP_USER).');
        }

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = true;
        $mailer->Username = $username;
        $mailer->Password = $password;

        if ($encryption !== '') {
            $mailer->SMTPSecure = $encryption;
        }

        $mailer->SMTPAutoTLS = true;
        $mailer->Timeout = 20;
        $mailer->isHTML(false);

        if (PHP_OS_FAMILY === 'Windows') {
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($fromEmail, $fromName !== '' ? $fromName : 'Parents Council');
        $this->mailer = $mailer;
        $this->transportModes = $this->resolveTransportModes($encryption, $port);
    }

    public function sendApprovalEmail(string $toEmail, string $link): void
    {
        $lastError = null;

        foreach ($this->transportModes as $mode) {
            try {
                $this->applyTransportMode($mode);
                $this->mailer->clearAllRecipients();
                $this->mailer->addAddress($toEmail);
                $this->mailer->isHTML(true);
                $this->mailer->Subject = \ApprovalMailer::approvalEmailSubject();
                $this->mailer->Body = \ApprovalMailer::approvalEmailHtmlBody($link);
                $this->mailer->AltBody = \ApprovalMailer::approvalEmailTextBody();

                $this->mailer->send();
                return;
            } catch (PHPMailerException $e) {
                $lastError = $e;
            }
        }

        if ($lastError instanceof PHPMailerException) {
            throw new \RuntimeException('Failed to send approval email: ' . $lastError->getMessage(), 0, $lastError);
        }

        throw new \RuntimeException('Failed to send approval email.');
    }

    private function resolveTransportModes(string $encryption, int $port): array
    {
        $normalized = strtolower(trim($encryption));

        if ($normalized === PHPMailer::ENCRYPTION_STARTTLS || $normalized === 'tls') {
            return ['starttls', 'smtps', 'none'];
        }

        if ($normalized === PHPMailer::ENCRYPTION_SMTPS || $normalized === 'ssl') {
            return ['smtps', 'starttls', 'none'];
        }

        if ($port === 465) {
            return ['smtps', 'starttls', 'none'];
        }

        if ($port === 587) {
            return ['starttls', 'smtps', 'none'];
        }

        return ['starttls', 'smtps', 'none'];
    }

    private function applyTransportMode(string $mode): void
    {
        if ($mode === 'smtps') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->SMTPAutoTLS = false;
            return;
        }

        if ($mode === 'none') {
            $this->mailer->SMTPSecure = '';
            $this->mailer->SMTPAutoTLS = false;
            return;
        }

        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->SMTPAutoTLS = true;
    }
}