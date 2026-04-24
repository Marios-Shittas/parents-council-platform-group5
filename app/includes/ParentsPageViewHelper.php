<?php

final class ParentsPageViewHelper
{
    /**
     * Renders plain text as safe multiline HTML.
     */
    public static function renderMultiline($value): string
    {
        return nl2br(htmlspecialchars(self::normalizeText($value), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Normalizes incoming text values into trimmed strings.
     */
    public static function normalizeText($value): string
    {
        return is_array($value) ? '' : trim((string)$value);
    }

    /**
     * Keeps only non-empty values from a generic list.
     */
    public static function sanitizeList($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $sanitizedItems = [];
        foreach ($items as $item) {
            $item = self::normalizeText($item);
            if ($item !== '') {
                $sanitizedItems[] = $item;
            }
        }

        return $sanitizedItems;
    }

    /**
     * Normalizes table-like row collections and removes empty rows.
     */
    public static function sanitizeRows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $sanitizedRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sanitizedRow = [];
            foreach ($row as $key => $cell) {
                $sanitizedRow[$key] = self::normalizeText($cell);
            }

            if (count(array_filter($sanitizedRow, static function ($cell) {
                return $cell !== '';
            })) > 0) {
                $sanitizedRows[] = $sanitizedRow;
            }
        }

        return $sanitizedRows;
    }

    /**
     * Sanitizes schedule blocks and removes fully empty ones.
     */
    public static function sanitizeScheduleBlocks($blocks): array
    {
        if (!is_array($blocks)) {
            return [];
        }

        $sanitizedBlocks = [];
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $sanitizedBlock = [
                'title' => self::normalizeText($block['title'] ?? ''),
                'rows' => self::sanitizeRows($block['rows'] ?? []),
            ];

            if ($sanitizedBlock['title'] === '' && empty($sanitizedBlock['rows'])) {
                continue;
            }

            $sanitizedBlocks[] = $sanitizedBlock;
        }

        return $sanitizedBlocks;
    }

    /**
     * Groups archive rows by school year.
     */
    public static function groupArchiveRowsByYear($rows): array
    {
        $grouped = [];

        foreach (self::sanitizeRows($rows) as $row) {
            $year = trim((string)($row['year'] ?? ''));
            if ($year === '') {
                $year = 'Χωρίς σχολική χρονιά';
            }

            if (!isset($grouped[$year])) {
                $grouped[$year] = [];
            }

            $grouped[$year][] = $row;
        }

        return $grouped;
    }

    /**
     * Merges archive rows with missing years from a reference source.
     */
    public static function mergeBoardArchiveReferenceRows(array $rows, array $referenceRows): array
    {
        $mergedRows = self::sanitizeRows($rows);
        $existingYears = [];

        foreach ($mergedRows as $row) {
            $year = trim((string)($row['year'] ?? ''));
            if ($year !== '') {
                $existingYears[(string)preg_replace('/\s*-\s*/', '-', $year)] = true;
            }
        }

        foreach (self::sanitizeRows($referenceRows) as $referenceRow) {
            $year = trim((string)($referenceRow['year'] ?? ''));
            $normalizedYear = (string)preg_replace('/\s*-\s*/', '-', $year);
            if ($year === '' || isset($existingYears[$normalizedYear])) {
                continue;
            }

            foreach (self::sanitizeRows($referenceRows) as $candidateRow) {
                if (trim((string)($candidateRow['year'] ?? '')) === $year) {
                    $mergedRows[] = $candidateRow;
                }
            }

            $existingYears[$normalizedYear] = true;
        }

        return $mergedRows;
    }
}
