<?php
/**
 * Public Announcements Page
 * Displays all announcements with images fetched from the database
 */

require_once __DIR__ . '/../app/services/AnnouncementsService.php';

// Initialize the service and fetch announcements
$announcementsService = new AnnouncementsService();
$announcements = $announcementsService->getAllAnnouncements();

// Default placeholder image if no image exists
$defaultImage = '/parents-council-platform-group5/public/assets/img/placeholder.jpg';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/announcements.css">

    <title>Ανακοινώσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>

<body>

<!-- Header -->
<?php include __DIR__ . '/../app/includes/header.php'; ?>

<!-- Page Header -->
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-bullhorn mr-2"></i>Ανακοινώσεις</h1>
        <p class="lead">Ενημερωθείτε για τα τελευταία νέα του σχολείου</p>
    </div>
</div>

<!-- Announcements Grid -->
<div class="container">
    <?php if (empty($announcements)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Δεν υπάρχουν ανακοινώσεις</h3>
            <p>Δεν έχουν δημοσιευτεί ανακοινώσεις ακόμα.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($announcements as $announcement): ?>
                <?php 
                    // Get first image or use default
                    $image = !empty($announcement['images']) ? $announcement['images'][0] : $defaultImage;
                    
                    // Format date
                    $date = date('d/m/Y', strtotime($announcement['publish_date']));
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="announcement-card">
                        <!-- Card Image -->
                        <img src="<?php echo htmlspecialchars($image); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($announcement['announcement_title']); ?>"
                             onerror="this.src='<?php echo $defaultImage; ?>'">
                        
                        <!-- Card Body -->
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($announcement['announcement_title']); ?>
                            </h5>
                            
                            <div class="card-date">
                                <i class="far fa-calendar-alt"></i>
                                <span><?php echo $date; ?></span>
                            </div>
                            
                            <p class="card-text">
                                <?php 
                                    $description = $announcement['announcement_description'] ?? '';
                                    echo htmlspecialchars(mb_substr($description, 0, 150));
                                    if (mb_strlen($description) > 150) echo '...';
                                ?>
                            </p>
                            
                            <a href="#" class="btn btn-primary-custom btn-sm" 
                               data-toggle="modal" 
                               data-target="#announcementModal<?php echo $announcement['announcement_id']; ?>">
                                <i class="fas fa-arrow-right mr-1"></i>Δες περισσότερα
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Modal for full announcement -->
                <div class="modal fade modal-custom" id="announcementModal<?php echo $announcement['announcement_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <?php echo htmlspecialchars($announcement['announcement_title']); ?>
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Image Carousel if multiple images -->
                                <?php if (!empty($announcement['images'])): ?>
                                    <?php if (count($announcement['images']) > 1): ?>
                                        <div id="carousel<?php echo $announcement['announcement_id']; ?>" class="carousel slide mb-4" data-ride="carousel">
                                            <div class="carousel-inner">
                                                <?php foreach ($announcement['images'] as $index => $img): ?>
                                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100" 
                                                             style="max-height: 400px; object-fit: cover; border-radius: 8px;"
                                                             alt="Image <?php echo $index + 1; ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <a class="carousel-control-prev" href="#carousel<?php echo $announcement['announcement_id']; ?>" role="button" data-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            </a>
                                            <a class="carousel-control-next" href="#carousel<?php echo $announcement['announcement_id']; ?>" role="button" data-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($announcement['images'][0]); ?>" 
                                             class="img-fluid mb-4" 
                                             style="max-height: 400px; width: 100%; object-fit: cover; border-radius: 8px;"
                                             alt="<?php echo htmlspecialchars($announcement['announcement_title']); ?>">
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="card-date mb-3">
                                    <i class="far fa-calendar-alt"></i>
                                    <span><strong>Ημερομηνία:</strong> <?php echo $date; ?></span>
                                </div>
                                
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($announcement['announcement_description'] ?? '')); ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary-custom" data-dismiss="modal">Κλείσιμο</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<?php include __DIR__ . '/../app/includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>