<?php

require_once __DIR__ . '/../core/SiteContext.php';

if (!function_exists('site_context')) {
    // Girnaei to trexon site context meso tis OO SiteContext klasis.
    function site_context(): string
    {
        return (new SiteContext())->context();
    }
}

if (!function_exists('site_is_parent')) {
    // Elegxei an to request einai sto parent section.
    function site_is_parent(): bool
    {
        return (new SiteContext())->isParent();
    }
}

if (!function_exists('site_base_url')) {
    // Girnaei ti vasi tou public URL.
    function site_base_url(): string
    {
        return (new SiteContext())->baseUrl();
    }
}

if (!function_exists('site_project_url')) {
    // Girnaei ti vasi tou project URL.
    function site_project_url(): string
    {
        return (new SiteContext())->projectUrl();
    }
}

if (!function_exists('site_section_url')) {
    // Ftiaxnei URL gia public i parent section.
    function site_section_url(string $path = ''): string
    {
        return (new SiteContext())->sectionUrl($path);
    }
}

if (!function_exists('site_public_url')) {
    // Ftiaxnei URL pou deixnei sto public root.
    function site_public_url(string $path = ''): string
    {
        return (new SiteContext())->publicUrl($path);
    }
}

if (!function_exists('site_asset_url')) {
    // Ftiaxnei URL gia public asset.
    function site_asset_url(string $path = ''): string
    {
        return (new SiteContext())->assetUrl($path);
    }
}

if (!function_exists('site_login_url')) {
    // Girnaei to kentriko login URL.
    function site_login_url(): string
    {
        return (new SiteContext())->loginUrl();
    }
}

if (!function_exists('site_storage_url')) {
    // Ftiaxnei URL gia storage arxeia.
    function site_storage_url(string $path = ''): string
    {
        return (new SiteContext())->storageUrl($path);
    }
}

if (!function_exists('site_resolve_content_url')) {
    // Metatrepei stored paths se URLs pou anoigoun apo browser.
    function site_resolve_content_url(string $path): string
    {
        return (new SiteContext())->resolveContentUrl($path);
    }
}
