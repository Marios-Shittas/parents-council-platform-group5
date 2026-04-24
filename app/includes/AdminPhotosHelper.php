<?php

final class AdminPhotosHelper
{
    // Trims scalar values coming from photos admin forms.
    public static function trimText($value): string
    {
        return trim((string)$value);
    }

    // Returns the filesystem upload directory for gallery images.
    public static function getGalleryUploadDir(): string
    {
        return dirname(__DIR__) . '/../public/assets/Parents_img/';
    }

    // Builds the public web path for a stored gallery image.
    public static function buildGalleryWebPath(string $fileName): string
    {
        return '/parents-council-platform-group5/public/assets/Parents_img/' . $fileName;
    }

    // Detects whether a gallery path points to local uploaded assets.
    public static function isLocalGalleryPath($imagePath): bool
    {
        return str_starts_with((string)$imagePath, '/parents-council-platform-group5/public/assets/Parents_img/');
    }

    // Resolves a local gallery web path to an absolute filesystem path.
    public static function resolveGalleryFilePath($imagePath): string
    {
        return self::getGalleryUploadDir() . basename((string)$imagePath);
    }

    // Deletes an existing local gallery file if it is present.
    public static function deleteGalleryFileIfExists($imagePath): void
    {
        if (!self::isLocalGalleryPath($imagePath)) {
            return;
        }

        $filePath = self::resolveGalleryFilePath($imagePath);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Uploads gallery image files and persists them via the service.
    public static function uploadGalleryImages($service): array
    {
        $uploadedCount = 0;
        $uploadErrors = [];

        if (empty($_FILES['gallery_images']['name'][0])) {
            return [$uploadedCount, $uploadErrors];
        }

        $uploadDir = self::getGalleryUploadDir();
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                $uploadErrors[] = "Αδυναμία δημιουργίας του φακέλου ανεβάσματος: {$uploadDir}";
                return [$uploadedCount, $uploadErrors];
            }
        }

        @chmod($uploadDir, 0777);
        clearstatcache(true, $uploadDir);

        if (!is_writable($uploadDir)) {
            $uploadErrors[] = "Ο φάκελος ανεβάσματος δεν είναι εγγράψιμος: {$uploadDir}";
            return [$uploadedCount, $uploadErrors];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024;

        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['gallery_images']['name'][$key] ?? '');
            $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
            $uploadError = $_FILES['gallery_images']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

            if ($uploadError !== UPLOAD_ERR_OK) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$uploadError})";
                continue;
            }

            $fileSize = $_FILES['gallery_images']['size'][$key] ?? 0;
            $fileMime = mime_content_type($tmpName);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExtensions, true)) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
                continue;
            }

            if ($fileSize > $maxFileSize) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο. Μέγιστο: 5MB";
                continue;
            }

            if (!getimagesize($tmpName)) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα.";
                continue;
            }

            if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with((string)$fileMime, 'image/')) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο MIME type ({$fileMime}).";
                continue;
            }

            $newFileName = uniqid('parents_', true) . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                $uploadErrors[] = "Αποτυχία μεταφόρτωσης του '{$safeFileName}'.";
                continue;
            }

            $imagePath = self::buildGalleryWebPath($newFileName);
            if ($service->addGalleryImage($imagePath, $imagePath, 'Φωτογραφικό υλικό σχολείου')) {
                $uploadedCount++;
            } else {
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων.";
            }
        }

        return [$uploadedCount, $uploadErrors];
    }

    // Adds a gallery image using an external image URL.
    public static function addGalleryImageFromUrl($service): array
    {
        $imageUrl = self::trimText($_POST['image_url'] ?? '');

        if ($imageUrl === '') {
            return [false, 'Δώστε το URL της εικόνας.'];
        }

        if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
            return [false, 'Το URL της εικόνας δεν είναι έγκυρο.'];
        }

        $imagePath = parse_url($imageUrl, PHP_URL_PATH) ?? '';
        $imageExt = strtolower(pathinfo((string)$imagePath, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageExt, $allowedExtensions, true)) {
            return [false, 'Βάλτε direct URL εικόνας που να οδηγεί κατευθείαν σε αρχείο JPG, JPEG, PNG, GIF ή WEBP.'];
        }

        if ($service->addGalleryImage($imageUrl, $imageUrl, 'Φωτογραφικό υλικό σχολείου')) {
            return [true, 'Η φωτογραφία από εξωτερικό σύνδεσμο προστέθηκε επιτυχώς.'];
        }

        return [false, 'Αποτυχία αποθήκευσης της φωτογραφίας. ' . $service->getLastError()];
    }
}
