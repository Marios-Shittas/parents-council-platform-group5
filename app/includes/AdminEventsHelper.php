<?php

final class AdminEventsHelper
{
    // Provides the default GDPR notice text for events.
    public static function getDefaultGdprNotice(): string
    {
        return 'Το φωτογραφικό υλικό της εκδήλωσης δημοσιεύεται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με τις ισχύουσες εγκρίσεις/πολιτικές του σχολείου.';
    }

    // Returns the upload directory for event images.
    public static function getImageUploadDir(): string
    {
        return dirname(__DIR__) . '/../public/assets/Events_img/';
    }

    // Builds a public web path for an uploaded event image.
    public static function buildImageWebPath($fileName): string
    {
        return '/parents-council-platform-group5/public/assets/Events_img/' . $fileName;
    }

    // Resolves a stored event image path to a local filesystem path.
    public static function resolveImageFilePath($imagePath): string
    {
        return self::getImageUploadDir() . basename((string)$imagePath);
    }

    // Uploads event images and stores their records via the events service.
    public static function uploadEventImages($eventsService, int $eventId, int $imageLimit): array
    {
        $uploadedCount = 0;
        $uploadErrors = [];

        if (empty($_FILES['images']['name'][0])) {
            return [$uploadedCount, $uploadErrors];
        }

        $uploadDir = self::getImageUploadDir();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 5 * 1024 * 1024;

        $existingImagesCount = $eventsService->countImages($eventId);
        $availableSlots = max(0, $imageLimit - $existingImagesCount);
        $selectedFilesCount = is_array($_FILES['images']['name'] ?? null) ? count($_FILES['images']['name']) : 0;

        if ($availableSlots === 0) {
            $uploadErrors[] = 'Η εκδήλωση έχει ήδη τον μέγιστο επιτρεπόμενο αριθμό φωτογραφιών (' . $imageLimit . ').';
            return [$uploadedCount, $uploadErrors];
        }

        if ($selectedFilesCount > $availableSlots) {
            $uploadErrors[] = 'Επιλέχθηκαν ' . $selectedFilesCount . ' αρχεία, αλλά μπορούν να αποθηκευτούν μόνο ' . $availableSlots . ' ακόμη φωτογραφίες για αυτή την εκδήλωση.';
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($uploadedCount >= $availableSlots) {
                $uploadErrors[] = 'Μπορούν να αποθηκευτούν έως ' . $imageLimit . ' φωτογραφίες ανά εκδήλωση.';
                break;
            }

            $fileName = basename($_FILES['images']['name'][$key] ?? '');
            $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
            $uploadError = $_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

            if ($uploadError !== UPLOAD_ERR_OK) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' απέτυχε να ανέβει (Error: {$uploadError})";
                continue;
            }

            $fileSize = $_FILES['images']['size'][$key] ?? 0;
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

            if (!in_array($fileMime, $allowedTypes, true) && !str_starts_with((string)$fileMime, 'image/')) {
                $uploadErrors[] = "Το αρχείο '{$safeFileName}' δεν έχει έγκυρο τύπο εικόνας (MIME: {$fileMime})";
                continue;
            }

            if (!file_exists($tmpName)) {
                $uploadErrors[] = "Το προσωρινό αρχείο για '{$safeFileName}' δεν βρέθηκε";
                continue;
            }

            if (!is_writable($uploadDir)) {
                $uploadErrors[] = "Ο φάκελος Events_img δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα του {$uploadDir}";
                continue;
            }

            $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $imagePath = self::buildImageWebPath($newFileName);
                if ($eventsService->addImage($eventId, $imagePath)) {
                    $uploadedCount++;
                } else {
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }

                    $serviceError = trim((string)$eventsService->getLastOperationError());
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
}
