<?php
require_once __DIR__ . '/../../app/services/ProductsService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productsService = new ProductsService();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['product_name'] ?? '');
        $description = trim($_POST['product_description'] ?? '');
        $price = trim($_POST['price'] ?? '');

        if ($name !== '' && $price !== '') {
            $productId = $productsService->createProduct($name, $description, $price);

            if ($productId) {
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

                    if ($fileError === 0) {
                        if (in_array($fileExt, $allowedExtensions)) {
                            if ($fileSize <= $maxFileSize) {
                                $newFileName = uniqid('product_', true) . '.' . $fileExt;
                                $targetPath = $uploadDir . $newFileName;

                                if (move_uploaded_file($fileTmp, $targetPath)) {
                                    $dbImagePath = '/parents-council-platform-group5/public/assets/Products_img/' . $newFileName;
                                    $productsService->addProductImage($productId, $dbImagePath);
                                    $message = 'Το προϊόν δημιουργήθηκε επιτυχώς με εικόνα.';
                                    $messageType = 'success';
                                } else {
                                    $message = 'Το προϊόν δημιουργήθηκε, αλλά απέτυχε το upload της εικόνας.';
                                    $messageType = 'warning';
                                }
                            } else {
                                $message = 'Το προϊόν δημιουργήθηκε, αλλά η εικόνα είναι πολύ μεγάλη.';
                                $messageType = 'warning';
                            }
                        } else {
                            $message = 'Το προϊόν δημιουργήθηκε, αλλά ο τύπος της εικόνας δεν επιτρέπεται.';
                            $messageType = 'warning';
                        }
                    } else {
                        $message = 'Το προϊόν δημιουργήθηκε, αλλά υπήρξε σφάλμα στο upload της εικόνας.';
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Το προϊόν δημιουργήθηκε επιτυχώς.';
                    $messageType = 'success';
                }

                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_message_type'] = $messageType;
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
}

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_message_type'] ?? 'info';
    unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
}

$products = $productsService->getAllProducts();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση E-shop - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_eshop.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
        </a>

        <div class="admin-header">
            <h1><i class="fas fa-shopping-cart mr-2"></i>Διαχείριση E-shop</h1>
            <button class="btn btn-primary-custom" data-toggle="modal" data-target="#createProductModal">
                <i class="fas fa-plus mr-1"></i>Νέο Προϊόν
            </button>
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
                                    <th>Περιγραφή</th>
                                    <th style="width: 150px;">Ενέργειες</th>
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
                                                        alt="Product Image"
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
                                                $desc = $product['product_description'] ?? '';
                                                echo htmlspecialchars(substr($desc, 0, 80));
                                                if (strlen($desc) > 80) echo '...';
                                            ?>
                                        </td>

                                        <td>
                                            <a href="?edit=<?php echo $product['product_id']; ?>"
                                               class="btn btn-sm btn-outline-primary mr-1"
                                               title="Επεξεργασία">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Διαγραφή"
                                                        onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το προϊόν;')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus mr-2"></i>Νέο Προϊόν
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Όνομα Προϊόντος *</strong></label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><strong>Τιμή *</strong></label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
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

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>