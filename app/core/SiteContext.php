<?php

class SiteContext
{
    private const BASE_PUBLIC_URL = '/parents-council-platform-group5/public';
    private const PROJECT_URL = '/parents-council-platform-group5';

    // Vriskei an i selida trexei sto public i sto parent section.
    public function context(): string
    {
        global $siteContext;

        if (isset($siteContext) && in_array($siteContext, ['public', 'parent'], true)) {
            return $siteContext;
        }

        $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        return strpos($requestPath, '/public/parent/') !== false ? 'parent' : 'public';
    }

    // Elegxei an to trexon request einai sto parent portal.
    public function isParent(): bool
    {
        return $this->context() === 'parent';
    }

    // Girnaei ti vasi gia ola ta public URLs.
    public function baseUrl(): string
    {
        return self::BASE_PUBLIC_URL;
    }

    // Girnaei ti vasi tou project gia paths ektos public.
    public function projectUrl(): string
    {
        return self::PROJECT_URL;
    }

    // Ftiaxnei URL mesa sto public i parent section.
    public function sectionUrl(string $path = ''): string
    {
        $prefix = $this->isParent() ? '/parent' : '';
        $normalized = ltrim($path, '/');

        if ($normalized === '') {
            return $this->baseUrl() . $prefix;
        }

        return $this->baseUrl() . $prefix . '/' . $normalized;
    }

    // Ftiaxnei URL pou deixnei panta sto public root.
    public function publicUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');

        return $normalized === '' ? $this->baseUrl() : $this->baseUrl() . '/' . $normalized;
    }

    // Ftiaxnei URL gia assets kato apo public/assets.
    public function assetUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');

        return $normalized === ''
            ? $this->baseUrl() . '/assets'
            : $this->baseUrl() . '/assets/' . $normalized;
    }

    // Girnaei to login URL me ena kentriko simio allagis.
    public function loginUrl(): string
    {
        return $this->publicUrl('login.php');
    }

    // Ftiaxnei URL gia arxeia sto storage folder.
    public function storageUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        $root = $this->projectUrl() . '/storage';

        if ($normalized === '' || $normalized === 'storage') {
            return $root;
        }

        if (strpos($normalized, 'storage/') === 0) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return $root . '/' . $normalized;
    }

    // Metatrepei database/file paths se browser-safe content URLs.
    public function resolveContentUrl(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || strpos($trimmed, 'data:') === 0 || preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        if (strpos($trimmed, self::PROJECT_URL . '/') === 0) {
            return $trimmed;
        }

        if (strpos($trimmed, 'storage/') === 0 || strpos($trimmed, '/storage/') === 0) {
            return $this->storageUrl($trimmed);
        }

        if (strpos($trimmed, 'public/') === 0) {
            return $this->projectUrl() . '/' . ltrim($trimmed, '/');
        }

        if ($trimmed[0] === '/') {
            return $trimmed;
        }

        return $this->publicUrl($trimmed);
    }
}
