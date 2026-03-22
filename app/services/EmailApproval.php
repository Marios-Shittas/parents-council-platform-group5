<?php

namespace Kozzy\ParentsCouncilPlatformGroup5\services;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class EmailApproval
{
    private PHPMailer $mailer;

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

        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($fromEmail, $fromName !== '' ? $fromName : 'Parents Council');
        $this->mailer = $mailer;
    }

    public function sendApprovalEmail(string $toEmail, string $link): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = 'Η αίτησή σας εγκρίθηκε';
            $this->mailer->Body =
                "Η εγγραφή σας εγκρίθηκε από τον διαχειριστή.\n\n" .
                "Μπορείτε πλέον να προχωρήσετε για να ολοκληρώσετε τη διαδικασία της εγγραφής σας.\n\n" .
                "Παρακαλούμε πατήστε τον παρακάτω σύνδεσμο:\n\n" .
                $link . "\n\n" .
                "Ο σύνδεσμος ισχύει για περιορισμένο χρονικό διάστημα.";

            $this->mailer->send();
        } catch (PHPMailerException $e) {
            throw new \RuntimeException('Failed to send approval email: ' . $e->getMessage(), 0, $e);
        }
    }
}