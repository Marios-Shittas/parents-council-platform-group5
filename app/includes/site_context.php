<?php
// Arxeio: app\includes\site_context.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.

require_once __DIR__ . '/../core/SiteContext.php';

if (!function_exists('site_context')) {
// Wrapper pou ekthenei to runtime context (public/parent) apo tin SiteContext klasi.
    function site_context(): string
    {
        return (new SiteContext())->context();
    }
}

if (!function_exists('site_is_parent')) {
// Shortcut helper pou elegxei an to trexon request anhkei sto parent section.
    function site_is_parent(): bool
    {
        return (new SiteContext())->isParent();
    }
}

if (!function_exists('site_base_url')) {
// Epistrefei to canonical public base URL pou xrisimevei ws vasi gia section kai asset links.
    function site_base_url(): string
    {
        return (new SiteContext())->baseUrl();
    }
}

if (!function_exists('site_project_url')) {
// Epistrefei to project root URL prefix gia paths pou den einai apokleistika kato apo public.
    function site_project_url(): string
    {
        return (new SiteContext())->projectUrl();
    }
}

if (!function_exists('site_section_url')) {
// Ftiaxnei context-aware section URL kai prosthetei /parent prefix automatic otan xreiazetai.
    function site_section_url(string $path = ''): string
    {
        return (new SiteContext())->sectionUrl($path);
    }
}

if (!function_exists('site_public_url')) {
// Ftiaxnei URL pou panta resolvearei mesa sto public root aneksartita apo context.
    function site_public_url(string $path = ''): string
    {
        return (new SiteContext())->publicUrl($path);
    }
}

if (!function_exists('site_asset_url')) {
// Ftiaxnei normalized URL gia static arxeia kato apo public/assets.
    function site_asset_url(string $path = ''): string
    {
        return (new SiteContext())->assetUrl($path);
    }
}

if (!function_exists('site_login_url')) {
// Epistrefei to kentriko login endpoint URL gia consistency sta auth redirects.
    function site_login_url(): string
    {
        return (new SiteContext())->loginUrl();
    }
}

if (!function_exists('site_storage_url')) {
// Ftiaxnei browser path gia storage periexomeno kai normalopoiei optional storage prefixes.
    function site_storage_url(string $path = ''): string
    {
        return (new SiteContext())->storageUrl($path);
    }
}

if (!function_exists('site_resolve_content_url')) {
// Kanei resolve diafores morfes stored periexomeno paths se asfali, amesa xrisima browser URLs.
    function site_resolve_content_url(string $path): string
    {
        return (new SiteContext())->resolveContentUrl($path);
    }
}
