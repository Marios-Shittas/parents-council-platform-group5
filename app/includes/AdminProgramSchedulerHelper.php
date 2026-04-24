<?php

final class AdminProgramSchedulerHelper
{
    // Normalizes schedule status values accepted by admin forms.
    public static function normalizeScheduleStatus(string $status): string
    {
        return in_array($status, ['active', 'inactive'], true) ? $status : 'inactive';
    }

    // Normalizes schedule feature keys to supported values.
    public static function normalizeScheduleFeature(string $feature): string
    {
        $normalized = trim((string)$feature);
        if ($normalized === 'cleanup_applications' || $normalized === 'cleanuo_submissions') {
            $normalized = 'cleanup_submissions';
        }

        $allowed = ['registration', 'delete_users', 'cleanup_submissions'];
        return in_array($normalized, $allowed, true) ? $normalized : 'registration';
    }

    // Converts a schedule feature key to a readable Greek label.
    public static function scheduleFeatureLabel(string $feature): string
    {
        $map = [
            'registration' => 'Εγγραφές',
            'delete_users' => 'Διαγραφή Χρηστών',
            'cleanup_submissions' => 'Καθαρισμός Υποβολών',
        ];

        return $map[$feature] ?? $feature;
    }

    // Parses date-time values from UI input to database format.
    public static function normalizeDateTimeLocalInput(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $dateTime = DateTime::createFromFormat('d/m/Y H:i', $trimmed);

        if (!$dateTime instanceof DateTime) {
            // Supports browser datetime-local fallback format.
            $dateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $trimmed);
            if (!$dateTime instanceof DateTime) {
                return null;
            }
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    // Formats stored datetime values for datetime-local inputs.
    public static function toDateTimeLocalValue(?string $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d\\TH:i', $timestamp) : '';
    }

    // Sets flash state and redirects back to schedule management.
    public static function redirectWithFlash(string $message, string $type = 'info', string $email = ''): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_message_type'] = $type;

        $redirectUrl = 'programatismo-litourgion.php';
        if ($email !== '') {
            $redirectUrl .= '?email=' . urlencode($email);
        }

        header('Location: ' . $redirectUrl);
        exit;
    }
}
