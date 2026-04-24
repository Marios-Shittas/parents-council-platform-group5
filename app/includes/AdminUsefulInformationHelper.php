<?php

final class AdminUsefulInformationHelper
{
    // Trims scalar input values from admin form fields.
    public static function trimText($value): string
    {
        return trim((string)$value);
    }

    // Splits textarea input into a cleaned list of non-empty lines.
    public static function textareaToList($value): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", (string)$value);
        $lines = explode("\n", $normalized);
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }

        return $items;
    }

    // Parses textarea rows in the format date|name into structured pairs.
    public static function textareaToPairs($value): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", (string)$value);
        $lines = explode("\n", $normalized);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            [$left, $right] = array_pad(explode('|', $line, 2), 2, '');
            $left = trim($left);
            $right = trim($right);

            if ($left !== '' && $right !== '') {
                $rows[] = ['date' => $left, 'name' => $right];
            }
        }

        return $rows;
    }

    // Converts an array of lines back to textarea representation.
    public static function listToTextarea($items): string
    {
        return implode("\n", is_array($items) ? $items : []);
    }

    // Converts array pairs to textarea rows in date|name format.
    public static function pairsToTextarea($rows): string
    {
        if (!is_array($rows)) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $date = trim((string)($row['date'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));
            if ($date !== '' && $name !== '') {
                $lines[] = $date . ' | ' . $name;
            }
        }

        return implode("\n", $lines);
    }
}
