<?php

if (!function_exists('site_context')) {
    function site_context(): string
    {
        global $siteContext;

        if (isset($siteContext) && in_array($siteContext, ['public', 'parent'], true)) {
            return $siteContext;
        }

        $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return strpos($requestPath, '/public/parent/') !== false ? 'parent' : 'public';
    }
}

if (!function_exists('site_is_parent')) {
    function site_is_parent(): bool
    {
        return site_context() === 'parent';
    }
}

if (!function_exists('site_base_url')) {
    function site_base_url(): string
    {
        return '/parents-council-platform-group5/public';
    }
}

if (!function_exists('site_project_url')) {
    function site_project_url(): string
    {
        return '/parents-council-platform-group5';
    }
}

if (!function_exists('site_section_url')) {
    function site_section_url(string $path = ''): string
    {
        $prefix = site_is_parent() ? '/parent' : '';
        $normalized = ltrim($path, '/');

        if ($normalized === '') {
            return site_base_url() . $prefix;
        }

        return site_base_url() . $prefix . '/' . $normalized;
    }
}

if (!function_exists('site_public_url')) {
    function site_public_url(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        return $normalized === '' ? site_base_url() : site_base_url() . '/' . $normalized;
    }
}

if (!function_exists('site_asset_url')) {
    function site_asset_url(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        return $normalized === ''
            ? site_base_url() . '/assets'
            : site_base_url() . '/assets/' . $normalized;
    }
}

if (!function_exists('site_login_url')) {
    function site_login_url(): string
    {
        return site_public_url('login.php');
    }
}

if (!function_exists('site_storage_url')) {
    function site_storage_url(string $path = ''): string
    {
        $normalized = ltrim($path, '/');
        $root = site_project_url() . '/storage';

        if ($normalized === '' || $normalized === 'storage') {
            return $root;
        }

        if (strpos($normalized, 'storage/') === 0) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return $root . '/' . $normalized;
    }
}

if (!function_exists('site_resolve_content_url')) {
    function site_resolve_content_url(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || strpos($trimmed, 'data:') === 0 || preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        if (strpos($trimmed, '/parents-council-platform-group5/') === 0) {
            return $trimmed;
        }

        if (strpos($trimmed, 'storage/') === 0 || strpos($trimmed, '/storage/') === 0) {
            return site_storage_url($trimmed);
        }

        if (strpos($trimmed, 'public/') === 0) {
            return site_project_url() . '/' . ltrim($trimmed, '/');
        }

        if ($trimmed[0] === '/') {
            return $trimmed;
        }

        return site_public_url($trimmed);
    }
}
