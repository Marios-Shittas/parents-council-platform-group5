<?php
/**
 * Σελίδα διαχείρισης μηνυμάτων επικοινωνίας (admin)
 * Εδώ ο διαχειριστής μπορεί να δει, να διαβάσει και να διαγράψει
 * μηνύματα που έχουν σταλει μέσω της φόρμας επικοινωνίας.
 */

require_once __DIR__ . '/../../app/services/EpikoinoniaService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$epikoinoniaService = new EpikoinoniaService();
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// Επεξεργασία της φόρμας όταν πατηθεί submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Διαγραφή μηνύματος
    if ($action === 'delete') {
        $messageId = intval($_POST['message_id'] ?? 0);
        
        if ($messageId > 0 && $epikoinoniaService->deleteMessage($messageId)) {
            $_SESSION['flash_message'] = 'Το μήνυμα διαγράφηκε επιτυχώς.';
            $_SESSION['flash_message_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Σφάλμα κατά τη διαγραφή του μηνύματος.';
            $_SESSION['flash_message_type'] = 'danger';
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Σήμανση ως αναγνωσμένο
    if ($action === 'mark_read') {
        $messageId = intval($_POST['message_id'] ?? 0);
        
        if ($messageId > 0) {
            $epikoinoniaService->markAsRead($messageId);
            $_SESSION['flash_message'] = 'Το μήνυμα σημειώθηκε ως αναγνωσμένο.';
            $_SESSION['flash_message_type'] = 'success';
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Λήψη παραμέτρων σελιδοποίησης
$page = intval($_GET['page'] ?? 1);
$page = max(1, $page);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Λήψη δεδομένων
$messages = $epikoinoniaService->getAllMessages($perPage, $offset);
$totalMessages = $epikoinoniaService->getMessageCount();
$unreadMessages = $epikoinoniaService->getUnreadMessageCount();
$totalPages = ceil($totalMessages / $perPage);

// Λήψη συγκεκριμένου μηνύματος αν ζητηθεί
$selectedMessage = null;
if (!empty($_GET['id'])) {
    $messageId = intval($_GET['id']);
    $selectedMessage = $epikoinoniaService->getMessageById($messageId);
    
    // Σήμανση ως αναγνωσμένο όταν ανοίγει
    if ($selectedMessage && !$selectedMessage['is_read']) {
        $epikoinoniaService->markAsRead($messageId);
    }
}
?>
<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Διαχείριση Επικοινωνίας - Admin</title>

    <!-- Bootstrap 4.6 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2f6ea0 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .page-header h1 {
            margin: 0;
            font-weight: 700;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1a3a5c;
            margin: 10px 0;
        }

        .stat-card .stat-label {
            color: #475569;
            font-size: 0.9rem;
        }

        .messages-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            color: #1a3a5c;
            font-weight: 700;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        .message-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .message-row:hover {
            background-color: #f8f9fa;
        }

        .message-row.unread {
            font-weight: 600;
            background-color: #f0f7ff;
        }

        .badge-unread {
            background-color: #2f6ea0;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .message-detail {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .message-detail h2 {
            color: #1a3a5c;
            margin-bottom: 20px;
            border-bottom: 2px solid #2f6ea0;
            padding-bottom: 10px;
        }

        .message-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #2f6ea0;
        }

        .info-label {
            font-weight: 700;
            color: #1a3a5c;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: #475569;
        }

        .message-content {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #2f6ea0;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .pagination {
            justify-content: center;
            margin-top: 30px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #475569;
        }

        .no-messages i {
            font-size: 3rem;
            color: #dde5ef;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../../app/includes/admin_sidebar.php'; ?>

    <div class="admin-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-envelope me-2"></i>Διαχείριση Επικοινωνίας
            </h1>
            <p class="mb-0 mt-2">Διαχειριστείτε τα μηνύματα που λαμβάνετε μέσω της φόρμας επικοινωνίας</p>
        </div>

        <!-- Flash Message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-envelope" style="font-size: 2rem; color: #2f6ea0;"></i>
                <div class="stat-number"><?php echo $totalMessages; ?></div>
                <div class="stat-label">Σύνολο Μηνυμάτων</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope-open-text" style="font-size: 2rem; color: #28a745;"></i>
                <div class="stat-number"><?php echo $totalMessages - $unreadMessages; ?></div>
                <div class="stat-label">Αναγνωσμένα</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-bell" style="font-size: 2rem; color: #ffc107;"></i>
                <div class="stat-number"><?php echo $unreadMessages; ?></div>
                <div class="stat-label">Μη αναγνωσμένα</div>
            </div>
        </div>

        <!-- Selected Message Detail -->
        <?php if ($selectedMessage): ?>
            <div class="message-detail">
                <h2>
                    <i class="fas fa-envelope-open me-2"></i>Λεπτομέρειες Μηνύματος
                </h2>

                <div class="message-info">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-user me-2"></i>Όνομα
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($selectedMessage['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </div>
                        <div class="info-value">
                            <a href="mailto:<?php echo htmlspecialchars($selectedMessage['email']); ?>">
                                <?php echo htmlspecialchars($selectedMessage['email']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-phone me-2"></i>Τηλέφωνο
                        </div>
                        <div class="info-value">
                            <a href="tel:<?php echo htmlspecialchars($selectedMessage['phone']); ?>">
                                <?php echo htmlspecialchars($selectedMessage['phone']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar-alt me-2"></i>Ημερομηνία
                        </div>
                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($selectedMessage['created_at'])); ?></div>
                    </div>
                </div>

                <h5 style="color: #1a3a5c; margin-top: 30px; margin-bottom: 15px;">
                    <i class="fas fa-heading me-2"></i>Θέμα
                </h5>
                <p style="font-size: 1.1rem; color: #475569; margin-bottom: 30px;">
                    <?php echo htmlspecialchars($selectedMessage['subject']); ?>
                </p>

                <h5 style="color: #1a3a5c; margin-bottom: 15px;">
                    <i class="fas fa-message me-2"></i>Μήνυμα
                </h5>
                <div class="message-content">
                    <?php echo htmlspecialchars($selectedMessage['message']); ?>
                </div>

                <div class="action-buttons" style="margin-top: 20px;">
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Πίσω
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Είστε σίγουρος ότι θέλετε να διαγράψετε αυτό το μήνυμα;');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="message_id" value="<?php echo $selectedMessage['message_id']; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Διαγραφή
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Messages Table -->
        <div class="messages-table">
            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <i class="fas fa-inbox"></i>
                    <h5>Δεν υπάρχουν μηνύματα</h5>
                    <p>Δεν έχετε λάβει κανένα μήνυμα ακόμα.</p>
                </div>
            <?php else: ?>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Όνομα</th>
                            <th>Email</th>
                            <th>Θέμα</th>
                            <th>Ημερομηνία</th>
                            <th>Κατάσταση</th>
                            <th>Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="message-row <?php echo !$msg['is_read'] ? 'unread' : ''; ?>">
                                <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                <td><?php echo htmlspecialchars(substr($msg['subject'], 0, 50)); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td>
                                    <?php if (!$msg['is_read']): ?>
                                        <span class="badge badge-unread">Μη αναγνωσμένο</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Αναγνωσμένο</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?id=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Προβολή
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Είστε σίγουρος ότι θέλετε να διαγράψετε αυτό το μήνυμα;');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Διαγραφή
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" style="padding: 20px;">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1">
                                        <i class="fas fa-chevron-left"></i> Πρώτη
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Προηγούμενη</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Επόμενη</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>">
                                        Τελευταία <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
