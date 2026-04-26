<?php

class SiteContext
{
    private const BASE_PUBLIC_URL = '/parents-council-platform-group5/public';
    private const PROJECT_URL = '/parents-council-platform-group5';
// Prosdiorizei to runtime context (public i parent) apo global override i apo to request path.
    public function context(): string
    {
        global $siteContext;

        if (isset($siteContext) && in_array($siteContext, ['public', 'parent'], true)) {
            return $siteContext;
        }

        $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        return strpos($requestPath, '/public/parent/') !== false ? 'parent' : 'public';
    }
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function isParent(): bool
    {
        return $this->context() === 'parent';
    }
// Epistrefei to stathero public base URL prefix pou xrisimopoieitai apo ola ta builders.
    public function baseUrl(): string
    {
        return self::BASE_PUBLIC_URL;
    }
// Epistrefei to root project URL prefix gia links ektos public (px storage/public mapping).
    public function projectUrl(): string
    {
        return self::PROJECT_URL;
    }
// Ftiaxnei URLs ana section kai vazei automatic prefix /parent otan to context einai parent.
    public function sectionUrl(string $path = ''): string
    {
        $prefix = $this->isParent() ? '/parent' : '';
        $normalized = ltrim($path, '/');

        if ($normalized === '') {
            return $this->baseUrl() . $prefix;
        }

        return $this->baseUrl() . $prefix . '/' . $normalized;
    }
// Ftiaxnei absolute project-relative URL pou panta deixnei sto public root.
    public function publicUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');

        return $normalized === '' ? $this->baseUrl() : $this->baseUrl() . '/' . $normalized;
    }
// Ftiaxnei URL gia static assets kato apo public/assets me normalized slashes.
    public function assetUrl(string $path = ''): string
    {
        $normalized = ltrim($path, '/');

        return $normalized === ''
            ? $this->baseUrl() . '/assets'
            : $this->baseUrl() . '/assets/' . $normalized;
    }
// Epistrefei kentriko login URL oste to auth entrypoint na allazei apo ena mono simeio.
    public function loginUrl(): string
    {
        return $this->publicUrl('login.php');
    }
// Normalopoiei kai ftiaxnei browser path gia storage resources, afairontas diplous storage prefixes.
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
// Metatrepei diafores morfes apothikevmenwn paths (absolute, relative, storage/public, external, data URI)
// se browser-safe URLs xwris na peirazei hdh egkyra absolute links.
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
