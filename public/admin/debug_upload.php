<?php
/**
 * Εργαλείο ελέγχου upload
 * Βοηθά να βρούμε γιατί αποτυγχάνει το ανέβασμα αρχείων
 */
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Debug</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Διαγνωστικά Εργαλείο Upload</h2>
    
    <div class="card mb-3">
        <div class="card-header">PHP Upload Settings</div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <td><strong>upload_max_filesize:</strong></td>
                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                </tr>
                <tr>
                    <td><strong>post_max_size:</strong></td>
                    <td><?php echo ini_get('post_max_size'); ?></td>
                </tr>
                <tr>
                    <td><strong>max_file_uploads:</strong></td>
                    <td><?php echo ini_get('max_file_uploads'); ?></td>
                </tr>
                <tr>
                    <td><strong>memory_limit:</strong></td>
                    <td><?php echo ini_get('memory_limit'); ?></td>
                </tr>
                <tr>
                    <td><strong>file_uploads:</strong></td>
                    <td><?php echo ini_get('file_uploads') ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Upload Directory Info</div>
        <div class="card-body">
            <?php
            $uploadDir = __DIR__ . '/../assets/Announcements_img/';
            $webPath = '/parents-council-platform-group5/public/assets/Announcements_img/';
            ?>
            <table class="table table-sm">
                <tr>
                    <td><strong>Upload Directory:</strong></td>
                    <td><?php echo $uploadDir; ?></td>
                </tr>
                <tr>
                    <td><strong>Exists:</strong></td>
                    <td><?php echo is_dir($uploadDir) ? '✓ Yes' : '✗ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Writable:</strong></td>
                    <td><?php echo is_writable($uploadDir) ? '✓ Yes' : '✗ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Permissions:</strong></td>
                    <td><?php echo is_dir($uploadDir) ? substr(sprintf('%o', fileperms($uploadDir)), -4) : 'N/A'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Test Upload</div>
        <div class="card-body">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])): ?>
                <div class="alert alert-info">
                    <h5>Upload Results:</h5>
                    <pre><?php
                    echo "POST Data:\n";
                    print_r($_POST);
                    echo "\n\nFILES Data:\n";
                    print_r($_FILES);
                    
                    if (!empty($_FILES['test_file']['name'][0])) {
                        echo "\n\nProcessing files:\n";
                        foreach ($_FILES['test_file']['tmp_name'] as $key => $tmpName) {
                            $fileName = $_FILES['test_file']['name'][$key];
                            $fileError = $_FILES['test_file']['error'][$key];
                            $fileSize = $_FILES['test_file']['size'][$key];
                            
                            echo "\nFile #{$key}: {$fileName}\n";
                            echo "  Error Code: {$fileError}\n";
                            echo "  Size: {$fileSize} bytes\n";
                            
                            if ($fileError === UPLOAD_ERR_OK && file_exists($tmpName)) {
                                echo "  Temp file exists: Yes\n";
                                echo "  MIME type: " . mime_content_type($tmpName) . "\n";
                                
                                $imageInfo = @getimagesize($tmpName);
                                if ($imageInfo) {
                                    echo "  Is valid image: Yes\n";
                                    echo "  Dimensions: {$imageInfo[0]}x{$imageInfo[1]}\n";
                                    echo "  Type: {$imageInfo['mime']}\n";
                                } else {
                                    echo "  Is valid image: No\n";
                                }
                                
                                // Δοκιμή αποθήκευσης του αρχείου στον φάκελο
                                $uploadDir = __DIR__ . '/../assets/Announcements_img/';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }
                                
                                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                $newFileName = 'test_' . uniqid() . '_' . time() . '.' . $fileExt;
                                $targetPath = $uploadDir . $newFileName;
                                
                                if (move_uploaded_file($tmpName, $targetPath)) {
                                    echo "  Upload success: Yes\n";
                                    echo "  Saved to: {$targetPath}\n";
                                    echo "  Web path: {$webPath}{$newFileName}\n";
                                } else {
                                    echo "  Upload success: No\n";
                                    echo "  Reason: move_uploaded_file() failed\n";
                                }
                            } else {
                                echo "  Temp file exists: No\n";
                                echo "  Error message: ";
                                // Μετατρέπουμε τον κωδικό λάθους σε απλό μήνυμα
                                switch ($fileError) {
                                    case UPLOAD_ERR_INI_SIZE:
                                        echo "File exceeds upload_max_filesize\n";
                                        break;
                                    case UPLOAD_ERR_FORM_SIZE:
                                        echo "File exceeds MAX_FILE_SIZE in form\n";
                                        break;
                                    case UPLOAD_ERR_PARTIAL:
                                        echo "File was only partially uploaded\n";
                                        break;
                                    case UPLOAD_ERR_NO_FILE:
                                        echo "No file was uploaded\n";
                                        break;
                                    case UPLOAD_ERR_NO_TMP_DIR:
                                        echo "Missing temporary folder\n";
                                        break;
                                    case UPLOAD_ERR_CANT_WRITE:
                                        echo "Failed to write file to disk\n";
                                        break;
                                    case UPLOAD_ERR_EXTENSION:
                                        echo "A PHP extension stopped the upload\n";
                                        break;
                                    default:
                                        echo "Unknown error\n";
                                }
                            }
                        }
                    }
                    ?></pre>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select test images to upload:</label>
                    <input type="file" class="form-control-file" name="test_file[]" multiple accept="image/*">
                    <small class="text-muted">You can select multiple files</small>
                </div>
                <button type="submit" class="btn btn-primary">Test Upload</button>
            </form>
        </div>
    </div>

    <a href="announcements.php" class="btn btn-secondary">← Back to Announcements</a>
</div>
</body>
</html>
