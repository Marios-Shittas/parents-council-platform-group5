<?php

final class ParentProfileViewHelper
{
    /**
     * Formats parent phone numbers to a readable Cyprus-friendly shape.
     */
    public static function formatPhoneNumber($phone): string
    {
        $rawPhone = trim((string)$phone);
        if ($rawPhone === '') {
            return '—';
        }

        $digits = preg_replace('/\D+/', '', $rawPhone);
        if (!is_string($digits) || $digits === '') {
            return $rawPhone;
        }

        if (strpos($digits, '357') === 0) {
            $localNumber = substr($digits, 3);
            if ($localNumber !== '') {
                return '+357 ' . $localNumber;
            }
        }

        if (strlen($digits) === 8) {
            return '+357 ' . $digits;
        }

        return $rawPhone;
    }
}
