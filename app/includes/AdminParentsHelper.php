<?php

final class AdminParentsHelper
{
    // Trims plain text fields from admin forms.
    public static function trimText($value): string
    {
        return trim((string)$value);
    }

    // Normalizes textarea line endings and trims outer whitespace.
    public static function textareaText($value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
        return trim($value);
    }

    // Trims URL-like input values.
    public static function trimUrl($value): string
    {
        return trim((string)$value);
    }

    // Returns the fixed icon used for the parents page header.
    public static function fixedPageHeaderIcon(): string
    {
        return 'fas fa-users';
    }

    // Parses textarea into a simple list of non-empty lines.
    public static function textareaToList($value): array
    {
        $lines = explode("\n", self::textareaText($value));
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }

        return $items;
    }

    // Parses pipe-separated textarea rows into keyed arrays.
    public static function textareaToRows($value, array $keys): array
    {
        $lines = explode("\n", self::textareaText($value));
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', array_pad(explode('|', $line), count($keys), ''));
            $row = [];
            $hasValue = false;

            foreach ($keys as $index => $key) {
                $row[$key] = $parts[$index] ?? '';
                if ($row[$key] !== '') {
                    $hasValue = true;
                }
            }

            if ($hasValue) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    // Serializes a list back into textarea format.
    public static function listToTextarea($items): string
    {
        return implode("\n", is_array($items) ? $items : []);
    }

    // Serializes keyed rows into pipe-separated textarea lines.
    public static function rowsToTextarea($rows, array $keys): string
    {
        if (!is_array($rows)) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $parts = [];
            $hasValue = false;
            foreach ($keys as $key) {
                $value = trim((string)($row[$key] ?? ''));
                $parts[] = $value;
                if ($value !== '') {
                    $hasValue = true;
                }
            }

            if ($hasValue) {
                $lines[] = implode(' | ', $parts);
            }
        }

        return implode("\n", $lines);
    }

    // Ensures archive rows include missing year groups from reference data.
    public static function mergeBoardArchiveReferenceRows(array $rows, array $referenceRows): array
    {
        $mergedRows = is_array($rows) ? $rows : [];
        $existingYears = [];

        foreach ($mergedRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $year = trim((string)($row['year'] ?? ''));
            if ($year !== '') {
                $existingYears[(string)preg_replace('/\s*-\s*/', '-', $year)] = true;
            }
        }

        foreach ($referenceRows as $referenceRow) {
            if (!is_array($referenceRow)) {
                continue;
            }

            $year = trim((string)($referenceRow['year'] ?? ''));
            $normalizedYear = (string)preg_replace('/\s*-\s*/', '-', $year);
            if ($year === '' || isset($existingYears[$normalizedYear])) {
                continue;
            }

            foreach ($referenceRows as $candidateRow) {
                if (is_array($candidateRow) && trim((string)($candidateRow['year'] ?? '')) === $year) {
                    $mergedRows[] = $candidateRow;
                }
            }

            $existingYears[$normalizedYear] = true;
        }

        return $mergedRows;
    }

    // Groups archive rows by school year.
    public static function groupBoardArchiveRowsByYear(array $rows): array
    {
        $groupedRows = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $year = trim((string)($row['year'] ?? ''));
            if ($year === '') {
                continue;
            }

            if (!isset($groupedRows[$year])) {
                $groupedRows[$year] = [];
            }

            $groupedRows[$year][] = [
                'role' => trim((string)($row['role'] ?? '')),
                'name' => trim((string)($row['name'] ?? '')),
            ];
        }

        return $groupedRows;
    }

    // Renders one archive year group into textarea rows.
    public static function boardArchiveGroupToTextarea(array $rows): string
    {
        return self::rowsToTextarea($rows, ['role', 'name']);
    }

    // Builds archive rows from parallel year and row block inputs.
    public static function boardArchiveBlocksToRows($years, $rowsPerYear): array
    {
        $archiveRows = [];
        $years = is_array($years) ? $years : [];
        $rowsPerYear = is_array($rowsPerYear) ? $rowsPerYear : [];

        foreach ($years as $index => $yearValue) {
            $year = trim((string)$yearValue);
            $rowsText = (string)($rowsPerYear[$index] ?? '');

            if ($year === '' && trim($rowsText) === '') {
                continue;
            }

            if ($year === '') {
                continue;
            }

            $parsedRows = self::textareaToRows($rowsText, ['role', 'name']);
            foreach ($parsedRows as $row) {
                $archiveRows[] = [
                    'year' => $year,
                    'role' => trim((string)($row['role'] ?? '')),
                    'name' => trim((string)($row['name'] ?? '')),
                ];
            }
        }

        return $archiveRows;
    }
}
