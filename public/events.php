<?php
/**
 * Public Events Page
 * Displays all events with images fetched from the database
 */

require_once __DIR__ . '/../app/services/EventsService.php';

// Initialize the service and fetch events
$eventsService = new EventsService();
$events = $eventsService->getUpcomingEvents(); // Only show upcoming events

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
    <link rel="stylesheet" href="assets/css/events.css">

    <title>Εκδηλώσεις - Γυμνάσιο Αγίου Αθανασίου</title>
</head>

<body>

<!-- Header -->
<?php include __DIR__ . '/../app/includes/header.php'; ?>

<!-- Page Header -->
<div class="events-hero">
    <div class="container">
        <h1><i class="fas fa-calendar-alt mr-2"></i>Εκδηλώσεις</h1>
        <p class="lead">Ενημερωθείτε για τις επερχόμενες εκδηλώσεις του σχολείου</p>
    </div>
</div>

<!-- Events Grid -->
<div class="container">
    <?php if (empty($events)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Δεν υπάρχουν προγραμματισμένες εκδηλώσεις</h3>
            <p>Δεν έχουν προγραμματιστεί εκδηλώσεις αυτή τη στιγμή.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($events as $event): ?>
                <?php 
                    // Get first image or use default
                    $image = !empty($event['images']) ? $event['images'][0] : $defaultImage;
                    
                    // Format date and time
                    $eventDateTime = new DateTime($event['event_date']);
                    $dateFormatted = $eventDateTime->format('d/m/Y');
                    $timeFormatted = $eventDateTime->format('H:i');
                    $dayMonth = $eventDateTime->format('d M');
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="event-card">
                        <!-- Card Image -->
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($event['event_title']); ?>"
                                 onerror="this.src='<?php echo $defaultImage; ?>'">
                            <div class="event-date-badge">
                                <?php echo $dayMonth; ?>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($event['event_title']); ?>
                            </h5>
                            
                            <div class="event-info">
                                <i class="far fa-calendar"></i>
                                <span><?php echo $dateFormatted; ?></span>
                            </div>
                            
                            <div class="event-info">
                                <i class="far fa-clock"></i>
                                <span><?php echo $timeFormatted; ?></span>
                            </div>
                            
                            <p class="card-text mt-3">
                                <?php 
                                    $description = $event['event_description'] ?? '';
                                    echo htmlspecialchars(mb_substr($description, 0, 120));
                                    if (mb_strlen($description) > 120) echo '...';
                                ?>
                            </p>
                            
                            <button class="btn btn-primary-custom btn-sm" 
                               data-toggle="modal" 
                               data-target="#eventModal<?php echo $event['event_id']; ?>">
                                <i class="fas fa-info-circle mr-1"></i>Περισσότερα
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal for full event details -->
                <div class="modal fade" id="eventModal<?php echo $event['event_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="background: #0057a8; color: white;">
                                <h5 class="modal-title">
                                    <?php echo htmlspecialchars($event['event_title']); ?>
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <!-- Image Carousel if multiple images -->
                                <?php if (!empty($event['images'])): ?>
                                    <?php if (count($event['images']) > 1): ?>
                                        <div id="carousel<?php echo $event['event_id']; ?>" class="carousel slide mb-4" data-ride="carousel">
                                            <div class="carousel-inner">
                                                <?php foreach ($event['images'] as $index => $img): ?>
                                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100" 
                                                             style="max-height: 400px; object-fit: cover; border-radius: 8px;"
                                                             alt="Image <?php echo $index + 1; ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <a class="carousel-control-prev" href="#carousel<?php echo $event['event_id']; ?>" role="button" data-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            </a>
                                            <a class="carousel-control-next" href="#carousel<?php echo $event['event_id']; ?>" role="button" data-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($event['images'][0]); ?>" 
                                             class="img-fluid mb-4" 
                                             style="max-height: 400px; width: 100%; object-fit: cover; border-radius: 8px;"
                                             alt="<?php echo htmlspecialchars($event['event_title']); ?>">
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <div class="event-info mb-2">
                                        <i class="far fa-calendar"></i>
                                        <strong>Ημερομηνία:</strong> <?php echo $dateFormatted; ?>
                                    </div>
                                    <div class="event-info">
                                        <i class="far fa-clock"></i>
                                        <strong>Ώρα:</strong> <?php echo $timeFormatted; ?>
                                    </div>
                                </div>
                                
                                <div class="event-content">
                                    <h6>Περιγραφή:</h6>
                                    <?php echo nl2br(htmlspecialchars($event['event_description'] ?? '')); ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
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
