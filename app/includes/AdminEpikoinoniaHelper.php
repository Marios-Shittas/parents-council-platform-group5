<?php

final class AdminEpikoinoniaHelper
{
    // Trims scalar input values coming from admin forms.
    public static function trimText($value): string
    {
        return trim((string)$value);
    }

    // Normalizes textarea input with consistent line endings.
    public static function textareaText($value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
        return trim($value);
    }

    // Returns the fixed icon used in the Epikoinonia page header.
    public static function fixedPageHeaderIcon(): string
    {
        return 'fas fa-envelope';
    }
}
