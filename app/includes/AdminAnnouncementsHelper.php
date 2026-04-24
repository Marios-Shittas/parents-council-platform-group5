<?php

final class AdminAnnouncementsHelper
{
    // Provides the default GDPR notice text for announcements.
    public static function getDefaultGdprNotice(): string
    {
        return 'Το φωτογραφικό υλικό και τα συνημμένα έγγραφα των ανακοινώσεων δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.';
    }

    // Returns the upload directory for announcement images.
    public static function getImageUploadDir(): string
    {
        return dirname(__DIR__) . '/../public/assets/Announcements_img/';
    }

    // Builds a public web path for an announcement image.
    public static function buildImageWebPath($fileName): string
    {
        return '/parents-council-platform-group5/public/assets/Announcements_img/' . $fileName;
    }

    // Returns the upload directory for announcement attachments.
    public static function getAttachmentUploadDir(): string
    {
        return dirname(__DIR__) . '/../public/assets/Announcements_docs/';
    }

    // Builds a public web path for an announcement attachment.
    public static function buildAttachmentWebPath($fileName): string
    {
        return '/parents-council-platform-group5/public/assets/Announcements_docs/' . $fileName;
    }

    // Ensures the upload directory exists and is writable.
    public static function ensureUploadDir($uploadDir, int $permissions = 0777): bool
    {
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, $permissions, true);
        }

        clearstatcache(true, $uploadDir);

        if (is_dir($uploadDir) && !is_writable($uploadDir)) {
            @chmod($uploadDir, $permissions);
            clearstatcache(true, $uploadDir);
        }

        return is_dir($uploadDir) && is_writable($uploadDir);
    }

    // Resolves an announcement asset web path to local filesystem path.
    public static function resolveAssetFilePath($filePath, string $type = 'image'): string
    {
        $baseDir = $type === 'attachment' ? self::getAttachmentUploadDir() : self::getImageUploadDir();
        return $baseDir . basename((string)$filePath);
    }

    // Uploads announcement images and saves their references.
    public static function uploadImages($announcementsService, int $announcementId, int $imageLimit): array
    {
        $uploadedCount = 0;
        $uploadErrors = [];

        if (empty($_FILES['images']['name'][0])) {
            return [$uploadedCount, $uploadErrors];
        }

        $uploadDir = self::getImageUploadDir();
        self::ensureUploadDir($uploadDir);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 5 * 1024 * 1024;
        $existingImagesCount = $announcementsService->countImages($announcementId);
        $availableSlots = max(0, $imageLimit - $existingImagesCount);
        $selectedFilesCount = is_array($_FILES['images']['name'] ?? null) ? count($_FILES['images']['name']) : 0;

        if ($availableSlots === 0) {
            $uploadErrors[] = 'Η ανακοίνωση έχει ήδη τον μέγιστο επιτρεπόμενο αριθμό εικόνων (' . $imageLimit . ').';
            return [$uploadedCount, $uploadErrors];
        }

        if ($selectedFilesCount > $availableSlots) {
            $uploadErrors[] = 'Επιλέχθηκαν ' . $selectedFilesCount . ' αρχεία, αλλά μπορούν να αποθηκευτούν μόνο ' . $availableSlots . ' ακόμη εικόνες για αυτή την ανακοίνωση.';
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($uploadedCount >= $availableSlots) {
                $uploadErrors[] = 'Μπορούν να αποθηκευτούν έως ' . $imageLimit . ' εικόνες ανά ανακοίνωση.';
                break;
            }

            $fileName = basename($_FILES['images']['name'][$key] ?? '');
            $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');

            if (($_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$_FILES['images']['error'][$key]})";
                continue;
            }

            $fileSize = $_FILES['images']['size'][$key];
            $fileMime = mime_content_type($tmpName);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExtensions, true)) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
                continue;
            }

            if ($fileSize > $maxFileSize) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' είναι πολύ μεγάλο (" . round($fileSize / 1024 / 1024, 2) . "MB). Μέγιστο: 5MB";
                continue;
            }

            if (!getimagesize($tmpName)) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν είναι έγκυρη εικόνα";
                continue;
            }

            if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with($fileMime, 'image/')) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας (MIME: {$fileMime})";
                continue;
            }

            $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (!file_exists($tmpName)) {
                $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε";
                continue;
            }

            if (!is_writable($uploadDir)) {
                $uploadErrors[] = "Ο φάκελος δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα (chmod 777 {$uploadDir})";
                continue;
            }

            if (move_uploaded_file($tmpName, $targetPath)) {
                $imagePath = self::buildImageWebPath($newFileName);
                if ($announcementsService->addImage($announcementId, $imagePath)) {
                    $uploadedCount++;
                } else {
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }

                    $serviceError = trim((string)$announcementsService->getLastOperationError());
                    if ($serviceError !== '') {
                        $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν αποθηκεύτηκε. {$serviceError}";
                    } else {
                        $uploadErrors[] = "Αποτυχία αποθήκευσης του '{$safeFileName}' στη βάση δεδομένων";
                    }
                }
            } else {
                $uploadErrors[] = "Αποτυχία μεταφόρτωσης του αρχείου '{$safeFileName}' - Έλεγξε δικαιώματα φακέλου: " . substr(sprintf('%o', fileperms($uploadDir)), -4);
            }
        }

        return [$uploadedCount, $uploadErrors];
    }

    // Uploads announcement attachments and saves their references.
    public static function uploadAttachments($announcementsService, int $announcementId): array
    {
        $uploadedCount = 0;
        $uploadErrors = [];

        if (empty($_FILES['attachments']['name'][0])) {
            return [$uploadedCount, $uploadErrors];
        }

        $uploadDir = self::getAttachmentUploadDir();
        self::ensureUploadDir($uploadDir);

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxFileSize = 8 * 1024 * 1024;

        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['attachments']['name'][$key] ?? '');
            $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
            $errorCode = $_FILES['attachments']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

            if ($errorCode !== UPLOAD_ERR_OK) {
                $uploadErrors[] = "Το συνημμένο '{$safeFileName}' απέτυχε να ανέβει (Error: {$errorCode})";
                continue;
            }

            $fileSize = (int)($_FILES['attachments']['size'][$key] ?? 0);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExtensions, true)) {
                $uploadErrors[] = "Το συνημμένο '{$safeFileName}' δεν έχει έγκυρη επέκταση. Επιτρέπονται: " . implode(', ', $allowedExtensions);
                continue;
            }

            if ($fileSize > $maxFileSize) {
                $uploadErrors[] = "Το συνημμένο '{$safeFileName}' είναι πολύ μεγάλο (" . round($fileSize / 1024 / 1024, 2) . "MB). Μέγιστο: 8MB";
                continue;
            }

            if (!file_exists($tmpName)) {
                $uploadErrors[] = "Το προσωρινό αρχείο για το συνημμένο '{$safeFileName}' δεν βρέθηκε";
                continue;
            }

            if (!is_writable($uploadDir)) {
                $uploadErrors[] = "Ο φάκελος συνημμένων δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα του {$uploadDir}";
                continue;
            }

            $newFileName = uniqid('announcement_attachment_', true) . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $attachmentPath = self::buildAttachmentWebPath($newFileName);
                if ($announcementsService->addAttachment($announcementId, $attachmentPath, $fileName)) {
                    $uploadedCount++;
                } else {
                    @unlink($targetPath);
                    $uploadErrors[] = "Αποτυχία αποθήκευσης του συνημμένου '{$safeFileName}' στη βάση δεδομένων";
                }
            } else {
                $uploadErrors[] = "Αποτυχία μεταφόρτωσης του συνημμένου '{$safeFileName}'.";
            }
        }

        return [$uploadedCount, $uploadErrors];
    }
}
