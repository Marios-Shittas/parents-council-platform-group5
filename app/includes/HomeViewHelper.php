<?php

final class HomeViewHelper
{
    /**
     * Checks whether a public-relative content URL resolves to an existing file.
     */
    public static function publicContentUrlExists(string $url): bool
    {
        $path = (string)parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return true;
        }

        $publicPrefix = '/parents-council-platform-group5/public/';
        if (strpos($path, $publicPrefix) !== 0) {
            return true;
        }

        $relativePath = urldecode(substr($path, strlen($publicPrefix)));
        if ($relativePath === '' || strpos(str_replace('\\', '/', $relativePath), '..') !== false) {
            return false;
        }

        $publicRoot = realpath(__DIR__ . '/../../public');
        if ($publicRoot === false) {
            return false;
        }

        $filePath = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        return is_file($filePath);
    }
}
