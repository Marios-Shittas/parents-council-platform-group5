<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/services/ProductsService.php';
require_once __DIR__ . '/../../app/services/EshopSettingsService.php';
require_once __DIR__ . '/../../app/includes/product_sizes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

$productsService = new ProductsService();
$eshopSettingsService = new EshopSettingsService();
$message = '';
$messageType = '';
$editProduct = null;
$availableSizeOptions = product_sizes_allowed_options();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['product_name'] ?? '');
        $description = trim($_POST['product_description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $hasSizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] === '1';
        $sizeOptions = $_POST['size_options'] ?? [];
        $customSizeOptions = trim($_POST['custom_size_options'] ?? '');

        if (!is_array($sizeOptions)) {
            $sizeOptions = [];
        }

        if ($name !== '' && $price !== '') {
            $productId = $productsService->createProduct($name, $description, $price);

            if ($productId) {
                $sizesSaved = product_sizes_set_for_product((int)$productId, $hasSizes, $sizeOptions, $customSizeOptions);

                if (!empty($_FILES['product_image']['name'])) {
                    $uploadDir = __DIR__ . '/../assets/Products_img/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = basename($_FILES['product_image']['name']);
                    $fileTmp = $_FILES['product_image']['tmp_name'];
                    $fileError = $_FILES['product_image']['error'];
                    $fileSize = $_FILES['product_image']['size'];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $maxFileSize = 5 * 1024 * 1024;

                    $newFileName = uniqid('product_', true) . '.' . $fileExt;
                    $targetPath = $uploadDir . $newFileName;

                    if ($fileError !== 0) {
                        $_SESSION['flash_message'] = 'Κωδικός σφάλματος μεταφόρτωσης: ' . $fileError;
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif (!in_array($fileExt, $allowedExtensions)) {
                        $_SESSION['flash_message'] = 'Μη επιτρεπτός τύπος αρχείου: ' . $fileExt;
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif ($fileSize > $maxFileSize) {
                        $_SESSION['flash_message'] = 'Η εικόνα είναι πολύ μεγάλη: ' . $fileSize . ' bytes';
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif (!is_uploaded_file($fileTmp)) {
                        $_SESSION['flash_message'] = 'Το αρχείο δεν αναγνωρίζεται ως uploaded file. TMP: ' . $fileTmp;
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif (!is_writable($uploadDir)) {
                        $_SESSION['flash_message'] = 'Ο φάκελος δεν είναι writable: ' . $uploadDir;
                        $_SESSION['flash_message_type'] = 'warning';
                    } else {
                        if (move_uploaded_file($fileTmp, $targetPath)) {
                            $dbImagePath = '/parents-council-platform-group5/public/assets/Products_img/' . $newFileName;
                            $imageSaved = $productsService->addProductImage($productId, $dbImagePath);

                            if ($imageSaved) {
                                $_SESSION['flash_message'] = 'Το προϊόν δημιουργήθηκε επιτυχώς με εικόνα.';
                                $_SESSION['flash_message_type'] = 'success';
                            } else {
                                $_SESSION['flash_message'] = 'Η εικόνα μεταφέρθηκε, αλλά δεν αποθηκεύτηκε στη βάση.';
                                $_SESSION['flash_message_type'] = 'warning';
                            }
                        } else {
                            $_SESSION['flash_message'] = 'Απέτυχε το move_uploaded_file. TMP: ' . $fileTmp . ' | TARGET: ' . $targetPath;
                            $_SESSION['flash_message_type'] = 'warning';
                        }
                    }
                } else {
                    $_SESSION['flash_message'] = 'Το προϊόν δημιουργήθηκε επιτυχώς.';
                    $_SESSION['flash_message_type'] = 'success';
                }

                if (!$sizesSaved) {
                    $_SESSION['flash_message'] = 'Το προϊόν δημιουργήθηκε, αλλά τα μεγέθη δεν αποθηκεύτηκαν σωστά.';
                    $_SESSION['flash_message_type'] = 'warning';
                }

                header("Location: eshop.php");
                exit;
            } else {
                $message = 'Σφάλμα κατά τη δημιουργία του προϊόντος.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Το όνομα και η τιμή είναι υποχρεωτικά.';
            $messageType = 'danger';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $description = trim($_POST['product_description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $hasSizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] === '1';
        $sizeOptions = $_POST['size_options'] ?? [];
        $customSizeOptions = trim($_POST['custom_size_options'] ?? '');

        if (!is_array($sizeOptions)) {
            $sizeOptions = [];
        }

        if ($id > 0 && $name !== '' && $price !== '') {
            $updated = $productsService->updateProduct($id, $name, $description, $price);

            if ($updated) {
                $sizesSaved = product_sizes_set_for_product($id, $hasSizes, $sizeOptions, $customSizeOptions);

                if (!empty($_FILES['product_image']['name'])) {
                    $uploadDir = __DIR__ . '/../assets/Products_img/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = basename($_FILES['product_image']['name']);
                    $fileTmp = $_FILES['product_image']['tmp_name'];
                    $fileError = $_FILES['product_image']['error'];
                    $fileSize = $_FILES['product_image']['size'];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $maxFileSize = 5 * 1024 * 1024;

                    $newFileName = uniqid('product_', true) . '.' . $fileExt;
                    $targetPath = $uploadDir . $newFileName;

                    if ($fileError !== 0) {
                        $_SESSION['flash_message'] = 'Κωδικός σφάλματος μεταφόρτωσης: ' . $fileError;
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif (!in_array($fileExt, $allowedExtensions)) {
                        $_SESSION['flash_message'] = 'Μη επιτρεπτός τύπος αρχείου: ' . $fileExt;
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif ($fileSize > $maxFileSize) {
                        $_SESSION['flash_message'] = 'Η εικόνα είναι πολύ μεγάλη: ' . $fileSize . ' bytes';
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif (!is_uploaded_file($fileTmp)) {
                        $_SESSION['flash_message'] = 'Το αρχείο δεν αναγνωρίζεται ως uploaded file. TMP: ' . $fileTmp;
                        $_SESSION['flash_message_type'] = 'warning';
                    } elseif (!is_writable($uploadDir)) {
                        $_SESSION['flash_message'] = 'Ο φάκελος δεν είναι writable: ' . $uploadDir;
                        $_SESSION['flash_message_type'] = 'warning';
                    } else {
                        if (move_uploaded_file($fileTmp, $targetPath)) {
                            $dbImagePath = '/parents-council-platform-group5/public/assets/Products_img/' . $newFileName;
                            $imageSaved = $productsService->replaceProductImage($id, $dbImagePath);

                            if ($imageSaved) {
                                $_SESSION['flash_message'] = 'Το προϊόν ενημερώθηκε επιτυχώς και η εικόνα αντικαταστάθηκε.';
                                $_SESSION['flash_message_type'] = 'success';
                            } else {
                                if (file_exists($targetPath)) {
                                    @unlink($targetPath);
                                }

                                $_SESSION['flash_message'] = 'Η νέα εικόνα ανέβηκε, αλλά δεν αποθηκεύτηκε σωστά στη βάση.';
                                $_SESSION['flash_message_type'] = 'warning';
                            }
                        } else {
                            $_SESSION['flash_message'] = 'Απέτυχε το move_uploaded_file. TMP: ' . $fileTmp . ' | TARGET: ' . $targetPath;
                            $_SESSION['flash_message_type'] = 'warning';
                        }
                    }
                }

                if (!isset($_SESSION['flash_message'])) {
                    $_SESSION['flash_message'] = 'Το προϊόν ενημερώθηκε επιτυχώς.';
                    $_SESSION['flash_message_type'] = 'success';
                }

                if (!$sizesSaved) {
                    $_SESSION['flash_message'] = 'Το προϊόν ενημερώθηκε, αλλά τα μεγέθη δεν αποθηκεύτηκαν σωστά.';
                    $_SESSION['flash_message_type'] = 'warning';
                }
            } else {
                $_SESSION['flash_message'] = 'Σφάλμα κατά την ενημέρωση του προϊόντος.';
                $_SESSION['flash_message_type'] = 'danger';
            }

            header("Location: eshop.php");
            exit;
        } else {
            $_SESSION['flash_message'] = 'Συμπλήρωσε σωστά τα υποχρεωτικά πεδία.';
            $_SESSION['flash_message_type'] = 'danger';
            header("Location: eshop.php");
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $existsInOrders = $productsService->productExistsInOrders($id);

            if ($existsInOrders === true) {
                $_SESSION['flash_message'] = 'Το προϊόν δεν μπορεί να διαγραφεί, γιατί υπάρχει σε ολοκληρωμένες παραγγελίες ή πληρωμές. Καθάρισε πρώτα το αντίστοιχο ιστορικό e-shop από τις Παραγγελίες.';
                $_SESSION['flash_message_type'] = 'warning';
            } else {
                $deleted = $productsService->deleteProduct($id);

                if ($deleted) {
                    product_sizes_remove_for_product($id);
                    $_SESSION['flash_message'] = 'Το προϊόν διαγράφηκε επιτυχώς.';
                    $_SESSION['flash_message_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Σφάλμα κατά τη διαγραφή του προϊόντος.';
                    $_SESSION['flash_message_type'] = 'danger';
                }
            }
        } else {
            $_SESSION['flash_message'] = 'Μη έγκυρο προϊόν.';
            $_SESSION['flash_message_type'] = 'danger';
        }

        header("Location: eshop.php");
        exit;
    }

    if ($action === 'toggle_shop_visibility') {
        $isVisible = isset($_POST['shop_visible']) && $_POST['shop_visible'] === '1';
        $updated = $eshopSettingsService->setShopVisibility($isVisible);

        if ($updated) {
            $_SESSION['flash_message'] = $isVisible
                ? 'Το κατάστημα είναι ξανά διαθέσιμο στους γονείς.'
                : 'Το κατάστημα τέθηκε σε κατάσταση "Έρχεται Σύντομα" και τα προϊόντα κρύφτηκαν.';
            $_SESSION['flash_message_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Δεν ήταν δυνατή η ενημέρωση της κατάστασης του καταστήματος.';
            $_SESSION['flash_message_type'] = 'danger';
        }

        header("Location: eshop.php");
        exit;
    }
}

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_message_type'] ?? 'info';
    unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];

    if ($editId > 0) {
        $editProduct = $productsService->getProductById($editId);
    }
}

if (is_array($editProduct)) {
    $sizeMeta = product_sizes_get_for_product((int)($editProduct['product_id'] ?? 0));
    $editProduct['has_sizes'] = $sizeMeta['has_sizes'];
    $editProduct['size_options'] = $sizeMeta['size_options'];
    $editProduct['custom_size_options'] = $sizeMeta['custom_size_options'];
}

$products = $productsService->getAllProducts();
$productSizesMap = product_sizes_read_all();
$isShopVisible = $eshopSettingsService->isShopVisible();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Καταστήματος - Διαχείριση</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_eshop.css?v=14">
</head>
<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

        <main class="admin-content">
            <a href="home.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Πίσω στην Αρχική
            </a>

            <div class="admin-header">
                <h1><i class="fas fa-shopping-cart mr-2"></i>Διαχείριση Καταστήματος</h1>
                <button class="btn btn-primary-custom" data-toggle="modal" data-target="#createProductModal">
                    <i class="fas fa-plus mr-1"></i>Νέο Προϊόν
                </button>
            </div>

            <div class="eshop-visibility-panel">
                <div class="eshop-visibility-copy">
                    <span class="eshop-visibility-label">Ορατότητα καταστήματος</span>
                    <h2><?php echo $isShopVisible ? 'Το κατάστημα είναι ενεργό' : 'Το κατάστημα εμφανίζει μήνυμα "Έρχεται Σύντομα"'; ?></h2>
                    <p>
                        <?php echo $isShopVisible
                            ? 'Οι γονείς βλέπουν κανονικά τα προϊόντα και μπορούν να πραγματοποιήσουν αγορές.'
                            : 'Οι γονείς δεν βλέπουν προϊόντα και εμφανίζεται μόνο μήνυμα "Έρχεται Σύντομα".'; ?>
                    </p>
                </div>

                <form method="POST" class="eshop-visibility-form">
                    <input type="hidden" name="action" value="toggle_shop_visibility">
                    <input type="hidden" name="shop_visible" value="<?php echo $isShopVisible ? '0' : '1'; ?>">
                    <button
                        type="submit"
                        class="btn <?php echo $isShopVisible ? 'btn-warning' : 'btn-success'; ?> eshop-visibility-btn"
                    >
                        <i class="fas <?php echo $isShopVisible ? 'fa-eye-slash' : 'fa-eye'; ?> mr-1"></i>
                        <?php echo $isShopVisible ? 'Έρχεται Σύντομα' : 'Επαναφορά καταστήματος'; ?>
                    </button>
                </form>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-body p-0">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>Δεν υπάρχουν προϊόντα</h3>
                            <p>Κάντε κλικ στο "Νέο Προϊόν" για να προσθέσετε ένα νέο προϊόν.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Εικόνα</th>
                                        <th>Τίτλος</th>
                                        <th>Τιμή</th>
                                        <th>Μεγέθη</th>
                                        <th>Περιγραφή</th>
                                        <th style="width: 170px;">Ενέργειες</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="thumbnail d-flex align-items-center justify-content-center">
                                                    <?php if (!empty($product['product_image'])): ?>
                                                        <img
                                                            src="<?php echo htmlspecialchars($product['product_image']); ?>"
                                                            alt="Εικόνα προϊόντος"
                                                            class="img-fluid product-thumb-img"
                                                        >
                                                    <?php else: ?>
                                                        <i class="fas fa-box text-muted"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                            </td>
                                            <td>
                                                €<?php echo htmlspecialchars($product['price']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $productIdKey = (string)((int)$product['product_id']);
                                                $sizeMeta = $productSizesMap[$productIdKey] ?? null;
                                                $sizeValues = [];

                                                if (is_array($sizeMeta) && !empty($sizeMeta['has_sizes']) && !empty($sizeMeta['size_options_with_labels']) && is_array($sizeMeta['size_options_with_labels'])) {
                                                    foreach ($sizeMeta['size_options_with_labels'] as $sizeOption) {
                                                        if (is_array($sizeOption) && !empty($sizeOption['label'])) {
                                                            $sizeValues[] = (string)$sizeOption['label'];
                                                        }
                                                    }
                                                }
                                                ?>

                                                <?php if (!empty($sizeValues)): ?>
                                                    <span><?php echo htmlspecialchars(implode(', ', $sizeValues)); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Χωρίς μέγεθος</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $desc = $product['product_description'] ?? '';
                                                echo htmlspecialchars(substr($desc, 0, 80));
                                                if (strlen($desc) > 80) echo '...';
                                                ?>
                                            </td>
                                            <td>
                                                <a
                                                    href="eshop.php?edit=<?php echo $product['product_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary mr-1"
                                                    title="Επεξεργασία"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger delete-product-btn"
                                                    data-id="<?php echo $product['product_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                    title="Διαγραφή"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="createProductModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-header" style="background:#2f6fb3;">
                        <h5 class="modal-title" style="color:#ffffff !important;">
                            <i class="fas fa-plus mr-2" style="color:#ffffff !important;"></i>Νέο Προϊόν
                        </h5>
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close"
                            style="color:#ffffff !important; opacity:1; text-shadow:none; border:none; background:transparent;"
                        >
                            <span aria-hidden="true" style="color:#ffffff !important;">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Όνομα Προϊόντος *</strong></label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label><strong>Τιμή *</strong></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><strong>Περιγραφή</strong></label>
                            <textarea name="product_description" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="form-group">
                            <label><strong>Εικόνα Προϊόντος</strong></label>
                            <input type="file" name="product_image" class="form-control-file" accept=".jpg,.jpeg,.png,.gif,.webp">
                            <small class="text-muted d-block mt-1">
                                Επιτρεπόμενοι τύποι: JPG, JPEG, PNG, GIF, WEBP | Μέγιστο μέγεθος: 5MB
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input js-has-sizes-toggle" id="create_has_sizes" name="has_sizes" value="1">
                                <label class="custom-control-label" for="create_has_sizes"><strong>Το προϊόν έχει διαθέσιμα μεγέθη</strong></label>
                            </div>
                        </div>

                        <div class="form-group js-size-options-group" style="display:none;">
                            <label><strong>Διαθέσιμα Μεγέθη στον parent</strong></label>
                            <div class="d-flex flex-wrap" style="gap:10px 14px;">
                                <?php foreach ($availableSizeOptions as $sizeValue => $sizeLabel): ?>
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input js-size-option"
                                            id="create_size_<?php echo htmlspecialchars($sizeValue); ?>"
                                            name="size_options[]"
                                            value="<?php echo htmlspecialchars($sizeValue); ?>"
                                            checked
                                        >
                                        <label class="custom-control-label" for="create_size_<?php echo htmlspecialchars($sizeValue); ?>"><?php echo htmlspecialchars($sizeLabel); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="custom-size-builder js-custom-size-builder">
                                <label for="create_custom_size_input"><strong>Πρόσθεσε άλλο μέγεθος / επιλογή</strong></label>
                                <div class="custom-size-input-row">
                                    <input
                                        type="text"
                                        id="create_custom_size_input"
                                        class="form-control js-custom-size-input"
                                        placeholder="Π.χ. 2-3 ετών ή 36"
                                    >
                                    <button type="button" class="btn btn-outline-primary js-add-custom-size">
                                        <i class="fas fa-plus mr-1"></i>Προσθήκη
                                    </button>
                                </div>
                                <div class="custom-size-chips js-custom-size-chips" aria-live="polite"></div>
                                <textarea
                                    id="create_custom_size_options"
                                    name="custom_size_options"
                                    class="js-custom-size-options d-none"
                                ></textarea>
                            </div>
                            <small class="text-muted d-block mt-2">Επίλεξε τα έτοιμα μεγέθη ή γράψε δικά σου. Αν το προϊόν δεν έχει μέγεθος, άφησε το checkbox ανενεργό.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Ακύρωση</button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save mr-1"></i>Αποθήκευση
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['product_id'] ?? ''; ?>">

                    <div class="modal-header" style="background:#2f6fb3;">
                        <h5 class="modal-title" style="color:#ffffff !important;">
                            <i class="fas fa-edit mr-2" style="color:#ffffff !important;"></i>Επεξεργασία Προϊόντος
                        </h5>
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close"
                            style="color:#ffffff !important; opacity:1; text-shadow:none; border:none; background:transparent;"
                        >
                            <span aria-hidden="true" style="color:#ffffff !important;">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Όνομα Προϊόντος *</strong></label>
                            <input
                                type="text"
                                name="product_name"
                                class="form-control"
                                value="<?php echo htmlspecialchars($editProduct['product_name'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label><strong>Τιμή *</strong></label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="price"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>"
                                    required
                                >
                                <div class="input-group-append">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><strong>Περιγραφή</strong></label>
                            <textarea name="product_description" class="form-control" rows="4"><?php echo htmlspecialchars($editProduct['product_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><strong>Νέα Εικόνα Προϊόντος (προαιρετικά)</strong></label>
                            <input type="file" name="product_image" class="form-control-file" accept=".jpg,.jpeg,.png,.gif,.webp">
                            <small class="text-muted d-block mt-1">
                                Αν επιλέξεις νέα εικόνα, θα αντικαταστήσει την τρέχουσα.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input
                                    type="checkbox"
                                    class="custom-control-input js-has-sizes-toggle"
                                    id="edit_has_sizes"
                                    name="has_sizes"
                                    value="1"
                                    <?php echo (!empty($editProduct['has_sizes'])) ? 'checked' : ''; ?>
                                >
                                <label class="custom-control-label" for="edit_has_sizes"><strong>Το προϊόν έχει διαθέσιμα μεγέθη</strong></label>
                            </div>
                        </div>

                        <div class="form-group js-size-options-group" style="display:<?php echo (!empty($editProduct['has_sizes'])) ? 'block' : 'none'; ?>;">
                            <label><strong>Διαθέσιμα Μεγέθη στον parent</strong></label>
                            <div class="d-flex flex-wrap" style="gap:10px 14px;">
                                <?php foreach ($availableSizeOptions as $sizeValue => $sizeLabel): ?>
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input js-size-option"
                                            id="edit_size_<?php echo htmlspecialchars($sizeValue); ?>"
                                            name="size_options[]"
                                            value="<?php echo htmlspecialchars($sizeValue); ?>"
                                            <?php echo (!empty($editProduct['size_options']) && is_array($editProduct['size_options']) && in_array($sizeValue, $editProduct['size_options'], true)) ? 'checked' : ''; ?>
                                        >
                                        <label class="custom-control-label" for="edit_size_<?php echo htmlspecialchars($sizeValue); ?>"><?php echo htmlspecialchars($sizeLabel); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="custom-size-builder js-custom-size-builder">
                                <label for="edit_custom_size_input"><strong>Πρόσθεσε άλλο μέγεθος / επιλογή</strong></label>
                                <div class="custom-size-input-row">
                                    <input
                                        type="text"
                                        id="edit_custom_size_input"
                                        class="form-control js-custom-size-input"
                                        placeholder="Π.χ. 2-3 ετών ή 36"
                                    >
                                    <button type="button" class="btn btn-outline-primary js-add-custom-size">
                                        <i class="fas fa-plus mr-1"></i>Προσθήκη
                                    </button>
                                </div>
                                <div class="custom-size-chips js-custom-size-chips" aria-live="polite"></div>
                                <textarea
                                    id="edit_custom_size_options"
                                    name="custom_size_options"
                                    class="js-custom-size-options d-none"
                                ><?php echo htmlspecialchars($editProduct['custom_size_options'] ?? ''); ?></textarea>
                            </div>
                            <small class="text-muted d-block mt-2">Επίλεξε τα έτοιμα μεγέθη ή γράψε δικά σου. Αν το προϊόν δεν έχει μέγεθος, άφησε το checkbox ανενεργό.</small>
                        </div>

                        <?php if (!empty($editProduct['product_image'])): ?>
                            <div class="form-group">
                                <label><strong>Τρέχουσα Εικόνα</strong></label>
                                <div>
                                    <img
                                        src="<?php echo htmlspecialchars($editProduct['product_image']); ?>"
                                        alt="Τρέχουσα εικόνα προϊόντος"
                                        style="max-width: 120px; border-radius: 8px; border:1px solid #ddd;"
                                    >
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer">
                        <a href="eshop.php" class="btn btn-secondary">Ακύρωση</a>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save mr-1"></i>Αποθήκευση Αλλαγών
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:16px; overflow:hidden;">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteProductId">

                    <div class="modal-header" style="background:#2f6fb3;">
                        <h5 class="modal-title" style="color:#ffffff !important;">
                            <i class="fas fa-exclamation-triangle mr-2" style="color:#ffffff !important;"></i>Επιβεβαίωση Διαγραφής
                        </h5>
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close"
                            style="color:#ffffff !important; opacity:1; text-shadow:none; border:none; background:transparent;"
                        >
                            <span aria-hidden="true" style="color:#ffffff !important;">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body text-center" style="padding: 30px 25px;">
                        <p style="font-size: 18px; margin-bottom: 10px;">
                            Είστε σίγουροι ότι θέλετε να διαγράψετε το προϊόν
                        </p>
                        <p id="deleteProductName" style="font-weight:700; color:#1A374D; font-size:20px;"></p>
                        <p style="margin-top:15px; color:#6c757d;">
                            Η ενέργεια αυτή δεν αναιρείται.
                        </p>
                    </div>

                    <div class="modal-footer d-flex justify-content-center" style="gap:10px;">
                        <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Ακύρωση</button>
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-trash mr-1"></i>Διαγραφή
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteButtons = document.querySelectorAll('.delete-product-btn');
            const deleteProductIdInput = document.getElementById('deleteProductId');
            const deleteProductName = document.getElementById('deleteProductName');

            function splitCustomSizes(value) {
                return String(value || '')
                    .split(/[\r\n,;]+/)
                    .map(function(item) {
                        return item.replace(/\s+/g, ' ').trim();
                    })
                    .filter(Boolean);
            }

            function bindCustomSizeBuilder(scopeEl) {
                const builder = scopeEl.querySelector('.js-custom-size-builder');

                if (!builder) {
                    return null;
                }

                const input = builder.querySelector('.js-custom-size-input');
                const addButton = builder.querySelector('.js-add-custom-size');
                const chips = builder.querySelector('.js-custom-size-chips');
                const hiddenField = builder.querySelector('.js-custom-size-options');
                let values = splitCustomSizes(hiddenField ? hiddenField.value : '');

                function syncHiddenField() {
                    if (hiddenField) {
                        hiddenField.value = values.join('\n');
                    }
                }

                function render() {
                    if (!chips) {
                        syncHiddenField();
                        return;
                    }

                    chips.innerHTML = '';

                    if (values.length === 0) {
                        const empty = document.createElement('span');
                        empty.className = 'custom-size-empty';
                        empty.textContent = 'Δεν έχουν προστεθεί άλλα μεγέθη.';
                        chips.appendChild(empty);
                        syncHiddenField();
                        return;
                    }

                    values.forEach(function(value, index) {
                        const chip = document.createElement('span');
                        chip.className = 'custom-size-chip';

                        const text = document.createElement('span');
                        text.textContent = value;

                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'custom-size-chip-remove';
                        removeButton.setAttribute('aria-label', 'Αφαίρεση ' + value);
                        removeButton.innerHTML = '<i class="fas fa-times"></i>';
                        removeButton.addEventListener('click', function() {
                            values.splice(index, 1);
                            render();
                        });

                        chip.appendChild(text);
                        chip.appendChild(removeButton);
                        chips.appendChild(chip);
                    });

                    syncHiddenField();
                }

                function addValue(shouldFocus) {
                    if (!input) {
                        return;
                    }

                    const nextValues = splitCustomSizes(input.value);

                    nextValues.forEach(function(nextValue) {
                        const exists = values.some(function(value) {
                            return value.toLowerCase() === nextValue.toLowerCase();
                        });

                        if (!exists) {
                            values.push(nextValue);
                        }
                    });

                    input.value = '';
                    render();

                    if (shouldFocus !== false) {
                        input.focus();
                    }
                }

                if (addButton) {
                    addButton.addEventListener('click', function() {
                        addValue(true);
                    });
                }

                if (input) {
                    input.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            addValue(true);
                        }
                    });
                }

                render();

                return {
                    clear: function() {
                        values = [];
                        if (input) {
                            input.value = '';
                        }
                        render();
                    },
                    hasValues: function() {
                        return values.length > 0;
                    },
                    commit: function() {
                        addValue(false);
                    },
                    sync: syncHiddenField
                };
            }

            function bindSizeVisibility(scopeEl) {
                if (!scopeEl) {
                    return;
                }

                const toggle = scopeEl.querySelector('.js-has-sizes-toggle');
                const optionsGroup = scopeEl.querySelector('.js-size-options-group');
                const options = scopeEl.querySelectorAll('.js-size-option');
                const customOptions = scopeEl.querySelector('.js-custom-size-options');
                const customBuilder = bindCustomSizeBuilder(scopeEl);
                const shouldPreselectDefaults = scopeEl.closest('#createProductModal') !== null;

                if (!toggle || !optionsGroup) {
                    return;
                }

                const sync = function(clearValues) {
                    const enabled = toggle.checked;
                    optionsGroup.style.display = enabled ? 'block' : 'none';

                    if (enabled && shouldPreselectDefaults) {
                        const hasCheckedOption = Array.prototype.some.call(options, function(option) {
                            return option.checked;
                        });
                        const hasCustomValues = customBuilder ? customBuilder.hasValues() : splitCustomSizes(customOptions ? customOptions.value : '').length > 0;

                        if (!hasCheckedOption && !hasCustomValues) {
                            options.forEach(function(option) {
                                option.checked = true;
                            });
                        }
                    }

                    if (!enabled && clearValues) {
                        options.forEach(function(option) {
                            option.checked = false;
                        });

                        if (customOptions) {
                            customOptions.value = '';
                        }

                        if (customBuilder) {
                            customBuilder.clear();
                        }
                    }
                };

                toggle.addEventListener('change', function() {
                    sync(true);
                });

                scopeEl.addEventListener('submit', function(event) {
                    if (!toggle.checked) {
                        return;
                    }

                    if (customBuilder) {
                        customBuilder.commit();
                    }

                    const hasClassicSize = Array.prototype.some.call(options, function(option) {
                        return option.checked;
                    });
                    const hasCustomSize = customBuilder ? customBuilder.hasValues() : splitCustomSizes(customOptions ? customOptions.value : '').length > 0;

                    if (!hasClassicSize && !hasCustomSize) {
                        event.preventDefault();
                        alert('Επίλεξε τουλάχιστον ένα μέγεθος ή άφησε ανενεργό το πεδίο "Το προϊόν έχει διαθέσιμα μεγέθη".');
                    } else if (customBuilder) {
                        customBuilder.sync();
                    }
                });

                sync(false);
            }

            bindSizeVisibility(document.querySelector('#createProductModal form'));
            bindSizeVisibility(document.querySelector('#editProductModal form'));

            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');

                    deleteProductIdInput.value = productId;
                    deleteProductName.textContent = productName;

                    $('#deleteConfirmModal').modal('show');
                });
            });

            <?php if ($editProduct): ?>
                $('#editProductModal').modal('show');
            <?php endif; ?>
        });
    </script>
</body>
</html>
