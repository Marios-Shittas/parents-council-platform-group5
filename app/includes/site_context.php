<?php

final class SiteContext
{
    /**
     * Returns active site context (public or parent).
     */
    public static function context(): string
    {
        global $siteContext;

        if (isset($siteContext) && in_array($siteContext, ['public', 'parent'], true)) {
            return $siteContext;
        }

        $requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return strpos($requestPath, '/public/parent/') !== false ? 'parent' : 'public';
    }

    /**
     * Tells whether current request is in parent context.
     */
    public static function isParent(): bool
    {
        return self::context() === 'parent';
    }

    /**
     * Returns base URL for public entrypoint.
     */
    public static function baseUrl(): string
    {
        return '/parents-council-platform-group5/public';
    }

    /**
     * Returns project root URL.
     */
    public static function projectUrl(): string
    {
        return '/parents-council-platform-group5';
    }

    /**
     * Builds URL to a public or parent section path.
     */
    public static function sectionUrl(string $path = ''): string
    {
        $prefix = self::isParent() ? '/parent' : '';
        $normalized = ltrim($path, '/');

        if ($normalized === '') {
            return self::baseUrl() . $prefix;
        }

        return self::baseUrl() . $prefix . '/' . $normalized;
    }

    /**
     * Builds URL under public root.
     */
    public static function publicUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        return $normalized === '' ? self::baseUrl() : self::baseUrl() . '/' . $normalized;
    }

    /**
     * Builds URL under public assets root.
     */
    public static function assetUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        return $normalized === ''
            ? self::baseUrl() . '/assets'
            : self::baseUrl() . '/assets/' . $normalized;
    }

    /**
     * Returns login page URL.
     */
    public static function loginUrl(): string
    {
        return self::publicUrl('login.php');
    }

    /**
     * Builds URL under project storage root.
     */
    public static function storageUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        $root = self::projectUrl() . '/storage';

        if ($normalized === '' || $normalized === 'storage') {
            return $root;
        }

        if (strpos($normalized, 'storage/') === 0) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return $root . '/' . $normalized;
    }

    /**
     * Resolves relative content paths to accessible URLs.
     */
    public static function resolveContentUrl(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || strpos($trimmed, 'data:') === 0 || preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        if (strpos($trimmed, '/parents-council-platform-group5/') === 0) {
            return $trimmed;
        }

        if (strpos($trimmed, 'storage/') === 0 || strpos($trimmed, '/storage/') === 0) {
            return self::storageUrl($trimmed);
        }

        if (strpos($trimmed, 'public/') === 0) {
            return self::projectUrl() . '/' . ltrim($trimmed, '/');
        }

        if ($trimmed[0] === '/') {
            return $trimmed;
        }

        return self::publicUrl($trimmed);
    }
}
