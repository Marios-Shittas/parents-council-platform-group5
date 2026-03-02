<?php
// ============================================================
//  public/events.php  –  Events Page
//  Public: list upcoming events, search, view details.
//  Admin:  add / edit / delete events (password-protected).
// ============================================================

session_start();

// ── Database connection ──────────────────────────────────────
$db = null;
$dbError = '';
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=parents_council_db;charset=utf8mb4',
        'root', '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    $dbError = 'Database unavailable. Please try again later.';
    error_log('[Events] DB error: ' . $e->getMessage());
}

// ── Admin session helpers ────────────────────────────────────
// Change this password before going live.
define('ADMIN_PASSWORD', 'admin123');

function isAdmin(): bool { return !empty($_SESSION['events_admin']); }

// ── CSRF helpers ─────────────────────────────────────────────
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrfToken(): string { return $_SESSION['csrf']; }
function verifyCsrf(string $t): bool { return hash_equals($_SESSION['csrf'] ?? '', $t); }

// ── Input sanitiser ──────────────────────────────────────────
function clean(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

// ── Flash messages ────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// ── POST handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {

    $action = $_POST['action'] ?? '';

    // Admin login / logout
    if ($action === 'admin_login') {
        if (($_POST['admin_pass'] ?? '') === ADMIN_PASSWORD) {
            $_SESSION['events_admin'] = true;
            setFlash('success', 'Admin mode enabled.');
        } else {
            setFlash('danger', 'Incorrect password.');
        }
        header('Location: events.php'); exit;
    }

    if ($action === 'admin_logout') {
        unset($_SESSION['events_admin']);
        header('Location: events.php'); exit;
    }

    // All write actions require admin + valid CSRF
    if (isAdmin() && verifyCsrf($_POST['csrf'] ?? '')) {

        // ── Validate shared fields ───────────────────────────
        $errors = [];
        if (in_array($action, ['create', 'update'])) {
            $title       = trim($_POST['title']       ?? '');
            $event_date  = trim($_POST['event_date']  ?? '');
            $event_time  = trim($_POST['event_time']  ?? '');
            $location    = trim($_POST['location']    ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title       === '') $errors[] = 'Title is required.';
            if ($event_date  === '') $errors[] = 'Date is required.';
            if ($event_time  === '') $errors[] = 'Time is required.';
            if ($location    === '') $errors[] = 'Location is required.';
            if ($description === '') $errors[] = 'Description is required.';

            if ($event_date !== '' && !DateTime::createFromFormat('Y-m-d', $event_date)) {
                $errors[] = 'Date format must be YYYY-MM-DD.';
            }
        }

        if ($errors) {
            setFlash('danger', implode(' ', $errors));
            header('Location: events.php' . ($action === 'update' ? '?edit=' . (int)($_POST['id'] ?? 0) : ''));
            exit;
        }

        // ── Create ───────────────────────────────────────────
        if ($action === 'create') {
            $stmt = $db->prepare(
                'INSERT INTO events (title, event_date, event_time, location, description)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$title, $event_date, $event_time, $location, $description]);
            setFlash('success', 'Event created successfully.');
            header('Location: events.php'); exit;
        }

        // ── Update ───────────────────────────────────────────
        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare(
                'UPDATE events
                 SET title=?, event_date=?, event_time=?, location=?, description=?
                 WHERE id=?'
            );
            $stmt->execute([$title, $event_date, $event_time, $location, $description, $id]);
            setFlash('success', 'Event updated successfully.');
            header('Location: events.php'); exit;
        }

        // ── Delete ───────────────────────────────────────────
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM events WHERE id=?')->execute([$id]);
            setFlash('success', 'Event deleted.');
            header('Location: events.php'); exit;
        }
    }
}

// ── Fetch events for display ─────────────────────────────────
$events   = [];
$editEvent = null;

if ($db) {
    $keyword = trim($_GET['search'] ?? '');
    $byDate  = trim($_GET['date']   ?? '');
    $editId  = (int) ($_GET['edit'] ?? 0);

    // Load event to edit
    if ($editId > 0 && isAdmin()) {
        $s = $db->prepare('SELECT * FROM events WHERE id = ?');
        $s->execute([$editId]);
        $editEvent = $s->fetch() ?: null;
    }

    // Build listing query
    $sql    = 'SELECT * FROM events WHERE event_date >= CURDATE()';
    $params = [];

    if ($keyword !== '') {
        $sql     .= ' AND title LIKE ?';
        $params[] = '%' . $keyword . '%';
    }
    if ($byDate !== '') {
        $sql     .= ' AND event_date = ?';
        $params[] = $byDate;
    }
    $sql .= ' ORDER BY event_date ASC, event_time ASC';

    $s = $db->prepare($sql);
    $s->execute($params);
    $events = $s->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">

    <title>Events</title>

    <style>
        /* Card hover lift */
        .event-card { transition: transform .18s, box-shadow .18s; border: none; }
        .event-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }

        /* Date badge on card */
        .date-badge {
            display: inline-block;
            background: #0057a8; color: #fff;
            font-size: .68rem; font-weight: 700;
            border-radius: 3px; padding: 2px 8px;
            margin-bottom: .45rem; text-transform: uppercase; letter-spacing: .05em;
        }

        /* Search bar background */
        .search-bar { background: #f1f3f5; border-radius: .5rem; }

        /* Admin panel accent */
        .admin-panel { border-left: 4px solid #0057a8; background: #f8f9ff; }

        /* Modal header */
        #detailModal .modal-header { background: #0057a8; color: #fff; }
        #detailModal .modal-header .close { color: #fff; }
    </style>
</head>

<body>

<!-- Header -->
<?php include '../app/includes/header.php'; ?>

<div class="container mt-5 mb-5">

    <!-- ── Page title ── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Events</h1>
        <?php if (isAdmin()): ?>
            <span class="badge badge-primary px-3 py-2">Admin Mode</span>
        <?php endif; ?>
    </div>

    <!-- ── Flash message ── -->
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo clean($flash['msg']); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <!-- ── DB error ── -->
    <?php if ($dbError): ?>
        <div class="alert alert-danger"><?php echo $dbError; ?></div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════
         ADMIN PANEL  (visible only when logged in as admin)
    ══════════════════════════════════════════════════════════ -->
    <?php if (isAdmin()): ?>
    <div class="admin-panel rounded p-4 mb-5 shadow-sm">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><?php echo $editEvent ? '✏️ Edit Event' : '➕ Add New Event'; ?></h4>
            <!-- Logout admin -->
            <form method="POST">
                <input type="hidden" name="action" value="admin_logout">
                <input type="hidden" name="csrf"   value="<?php echo csrfToken(); ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Exit Admin</button>
            </form>
        </div>

        <!-- Add / Edit form -->
        <form method="POST" id="eventForm" novalidate>
            <input type="hidden" name="csrf"   value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="action" value="<?php echo $editEvent ? 'update' : 'create'; ?>">
            <?php if ($editEvent): ?>
                <input type="hidden" name="id" value="<?php echo (int)$editEvent['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <!-- Title -->
                <div class="form-group col-md-8">
                    <label for="title">Event Title <span class="text-danger">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255"
                           value="<?php echo clean($editEvent['title'] ?? ''); ?>">
                    <div class="invalid-feedback">Title is required.</div>
                </div>
                <!-- Location -->
                <div class="form-group col-md-4">
                    <label for="location">Location <span class="text-danger">*</span></label>
                    <input type="text" id="location" name="location" class="form-control" required maxlength="255"
                           value="<?php echo clean($editEvent['location'] ?? ''); ?>">
                    <div class="invalid-feedback">Location is required.</div>
                </div>
            </div>

            <div class="form-row">
                <!-- Date -->
                <div class="form-group col-md-4">
                    <label for="event_date">Date <span class="text-danger">*</span></label>
                    <input type="date" id="event_date" name="event_date" class="form-control" required
                           value="<?php echo clean($editEvent['event_date'] ?? ''); ?>">
                    <div class="invalid-feedback">Date is required.</div>
                </div>
                <!-- Time -->
                <div class="form-group col-md-4">
                    <label for="event_time">Time <span class="text-danger">*</span></label>
                    <input type="time" id="event_time" name="event_time" class="form-control" required
                           value="<?php echo clean(isset($editEvent['event_time']) ? substr($editEvent['event_time'], 0, 5) : ''); ?>">
                    <div class="invalid-feedback">Time is required.</div>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description <span class="text-danger">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="3" required
                ><?php echo clean($editEvent['description'] ?? ''); ?></textarea>
                <div class="invalid-feedback">Description is required.</div>
            </div>

            <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editEvent ? 'Save Changes' : 'Add Event'; ?>
                </button>
                <?php if ($editEvent): ?>
                    <a href="events.php" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($events) || !$_GET['search'] ?? true): ?>
        <!-- Admin event table -->
        <hr class="mt-4">
        <h5 class="mb-3">All Events</h5>
        <?php
        // For admin table, load ALL events (not just upcoming)
        $allEvents = $db
            ? $db->query('SELECT * FROM events ORDER BY event_date DESC')->fetchAll()
            : [];
        ?>
        <?php if (empty($allEvents)): ?>
            <p class="text-muted">No events yet. Use the form above to add one.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered bg-white">
                <thead class="thead-dark">
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th style="min-width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allEvents as $ev): ?>
                    <tr>
                        <td><?php echo clean($ev['title']); ?></td>
                        <td><?php echo date('d M Y', strtotime($ev['event_date'])); ?></td>
                        <td><?php echo date('H:i',   strtotime($ev['event_time']));  ?></td>
                        <td><?php echo clean($ev['location']); ?></td>
                        <td>
                            <!-- Edit -->
                            <a href="events.php?edit=<?php echo (int)$ev['id']; ?>"
                               class="btn btn-warning btn-sm mr-1">Edit</a>

                            <!-- Delete -->
                            <form method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this event?');">
                                <input type="hidden" name="csrf"   value="<?php echo csrfToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?php echo (int)$ev['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div><!-- /.admin-panel -->
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════
         SEARCH BAR
    ══════════════════════════════════════════════════════════ -->
    <section class="search-bar p-3 mb-4">
        <form method="GET" action="events.php">
            <div class="form-row align-items-end">
                <!-- Title search -->
                <div class="form-group col-sm-5 mb-2 mb-sm-0">
                    <label for="search" class="sr-only">Search by title</label>
                    <input type="text" id="search" name="search" class="form-control"
                           placeholder="Search events by title…"
                           value="<?php echo clean($_GET['search'] ?? ''); ?>">
                </div>
                <!-- Date filter -->
                <div class="form-group col-sm-4 mb-2 mb-sm-0">
                    <label for="date" class="sr-only">Filter by date</label>
                    <input type="date" id="date" name="date" class="form-control"
                           value="<?php echo clean($_GET['date'] ?? ''); ?>">
                </div>
                <!-- Submit -->
                <div class="form-group col-sm-2 mb-2 mb-sm-0">
                    <button type="submit" class="btn btn-primary btn-block">Search</button>
                </div>
                <!-- Clear -->
                <?php if (!empty($_GET['search']) || !empty($_GET['date'])): ?>
                <div class="form-group col-sm-1 mb-0">
                    <a href="events.php" class="btn btn-outline-secondary btn-block"
                       title="Clear search">✕</a>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         PUBLIC EVENT CARDS
    ══════════════════════════════════════════════════════════ -->
    <?php if (!$db): ?>
        <!-- DB unavailable – already shown above -->
    <?php elseif (empty($events)): ?>
        <div class="alert alert-info">
            No upcoming events found<?php echo (!empty($_GET['search']) || !empty($_GET['date'])) ? ' matching your search.' : '.'; ?>
            <?php if (!empty($_GET['search']) || !empty($_GET['date'])): ?>
                <a href="events.php">Show all events</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
        <?php foreach ($events as $ev):
            $safeTitle = clean($ev['title']);
            $safeLoc   = clean($ev['location']);
            $safeDesc  = clean($ev['description']);
            $fmtDate   = date('d M Y', strtotime($ev['event_date']));
            $fmtTime   = date('H:i',   strtotime($ev['event_time']));
        ?>
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card h-100 event-card shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <span class="date-badge"><?php echo $fmtDate; ?></span>
                        <h5 class="card-title"><?php echo $safeTitle; ?></h5>
                        <ul class="list-unstyled small text-muted mb-2">
                            <li><strong>Time:</strong> <?php echo $fmtTime; ?></li>
                            <li><strong>Location:</strong> <?php echo $safeLoc; ?></li>
                        </ul>
                        <p class="card-text small flex-grow-1">
                            <?php
                                // Show a short preview (120 chars) in the card
                                echo mb_strlen($ev['description']) > 120
                                    ? clean(mb_substr($ev['description'], 0, 120)) . '…'
                                    : $safeDesc;
                            ?>
                        </p>
                        <!-- View full details button – triggers modal -->
                        <div class="mt-auto pt-2">
                            <button class="btn btn-outline-primary btn-sm"
                                    data-toggle="modal" data-target="#detailModal"
                                    data-title="<?php echo $safeTitle; ?>"
                                    data-date="<?php echo $fmtDate; ?>"
                                    data-time="<?php echo $fmtTime; ?>"
                                    data-location="<?php echo $safeLoc; ?>"
                                    data-description="<?php echo $safeDesc; ?>">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- /.container -->

<!-- ══════════════════════════════════════════════════════════
     EVENT DETAIL MODAL
══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Title</dt>
                    <dd class="col-sm-9" id="m-title"></dd>

                    <dt class="col-sm-3">Date</dt>
                    <dd class="col-sm-9" id="m-date"></dd>

                    <dt class="col-sm-3">Time</dt>
                    <dd class="col-sm-9" id="m-time"></dd>

                    <dt class="col-sm-3">Location</dt>
                    <dd class="col-sm-9" id="m-location"></dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9" id="m-description"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include '../app/includes/footer.php'; ?>

<!-- jQuery + Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Populate event detail modal from data-* attributes on the trigger button
document.getElementById('detailModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('m-title').textContent       = btn.dataset.title;
    document.getElementById('m-date').textContent        = btn.dataset.date;
    document.getElementById('m-time').textContent        = btn.dataset.time;
    document.getElementById('m-location').textContent    = btn.dataset.location;
    document.getElementById('m-description').textContent = btn.dataset.description;
});

// Bootstrap 4 HTML5 form validation
(function () {
    'use strict';
    var form = document.getElementById('eventForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
    }, false);

    // Auto-scroll to admin form when editing
    <?php if ($editEvent): ?>
    document.querySelector('.admin-panel').scrollIntoView({ behavior: 'smooth' });
    <?php endif; ?>
}());
</script>

</body>
</html>

