<?php
require_once __DIR__ . '/../../app/services/ProductsService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productsService = new ProductsService();
$products = $productsService->getAllProducts();
?>
<?php
require_once __DIR__ . '/../../app/services/ProductsService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productsService = new ProductsService();
$products = $productsService->getAllProducts();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση E-shop - Admin</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Shared project styles -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/announcements.css">
    <link rel="stylesheet" href="../assets/css/admin_announcements.css">

    <!-- E-shop admin styles -->
    <link rel="stylesheet" href="../assets/css/admin_eshop.css">
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
            <button class="btn btn-primary-custom">
                <i class="fas fa-plus mr-1"></i>Νέο Προϊόν
            </button>
        </div>

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
                                                <i class="fas fa-box text-muted"></i>
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
                                                echo htmlspecialchars(mb_substr($desc, 0, 80));
                                                if (mb_strlen($desc) > 80) echo '...';
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

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>