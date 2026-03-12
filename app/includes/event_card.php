<?php
$images     = !empty($event['images']) ? $event['images'] : [$defaultImage];
$imageCount = count($event['images'] ?? []);
$eventDateTime = new DateTime($event['event_date']);
$dateFormatted = $eventDateTime->format('d/m/Y');
$timeFormatted = $eventDateTime->format('H:i');
$dayMonth      = strtoupper($eventDateTime->format('d M'));
$isPast        = $eventDateTime < new DateTime();
?>
<div class="col-xl-4 col-md-6 mb-4">
    <div class="event-card">

        <!-- Image area -->
        <div class="event-card-img-wrap">

            <?php if ($imageCount > 1): ?>
                <!-- Mini carousel για πολλές φωτογραφίες -->
                <div id="cardCarousel<?php echo $event['event_id']; ?>" class="carousel slide" data-ride="carousel" data-interval="3500">
                    <div class="carousel-inner">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                     alt="<?php echo htmlspecialchars($event['event_title']); ?>"
                                     onerror="this.src='<?php echo $defaultImage; ?>'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a class="carousel-control-prev" href="#cardCarousel<?php echo $event['event_id']; ?>" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </a>
                    <a class="carousel-control-next" href="#cardCarousel<?php echo $event['event_id']; ?>" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </a>
                </div>
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($images[0]); ?>"
                     alt="<?php echo htmlspecialchars($event['event_title']); ?>"
                     onerror="this.src='<?php echo $defaultImage; ?>'">
            <?php endif; ?>

            <!-- Ημερομηνία badge -->
            <div class="event-date-badge">
                <i class="far fa-calendar-alt mr-1"></i><?php echo $dayMonth; ?>
            </div>

            <!-- Photo count badge (μόνο αν >1 φωτογραφία) -->
            <?php if ($imageCount > 1): ?>
                <div class="event-photo-count">
                    <i class="fas fa-images mr-1"></i><?php echo $imageCount; ?>
                </div>
            <?php endif; ?>

            <!-- Past label -->
            <?php if ($isPast): ?>
                <div class="event-past-label">Ολοκληρώθηκε</div>
            <?php endif; ?>

        </div>

        <!-- Card content -->
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($event['event_title']); ?></h5>

            <div class="event-info">
                <i class="far fa-calendar"></i>
                <span><?php echo $dateFormatted; ?></span>
            </div>
            <div class="event-info">
                <i class="far fa-clock"></i>
                <span><?php echo $timeFormatted; ?></span>
            </div>

            <p class="card-text">
                <?php
                    $description = $event['event_description'] ?? '';
                    echo htmlspecialchars(mb_substr($description, 0, 110));
                    if (mb_strlen($description) > 110) echo '…';
                ?>
            </p>

            <button class="btn btn-event-details btn-sm"
                    data-toggle="modal"
                    data-target="#eventModal<?php echo $event['event_id']; ?>">
                <i class="fas fa-info-circle mr-1"></i>Περισσότερα
            </button>
        </div>

    </div>
</div>

<!-- Modal -->
<div class="modal fade event-modal" id="eventModal<?php echo $event['event_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt mr-2"></i><?php echo htmlspecialchars($event['event_title']); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <?php if (!empty($event['images'])): ?>
                    <?php if (count($event['images']) > 1): ?>
                        <!-- Full carousel με indicators -->
                        <div id="modalCarousel<?php echo $event['event_id']; ?>" class="carousel slide mb-4" data-ride="carousel">
                            <ol class="carousel-indicators">
                                <?php foreach ($event['images'] as $i => $img): ?>
                                    <li data-target="#modalCarousel<?php echo $event['event_id']; ?>"
                                        data-slide-to="<?php echo $i; ?>"
                                        class="<?php echo $i === 0 ? 'active' : ''; ?>"></li>
                                <?php endforeach; ?>
                            </ol>
                            <div class="carousel-inner">
                                <?php foreach ($event['images'] as $index => $img): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100"
                                             alt="Φωτογραφία <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a class="carousel-control-prev" href="#modalCarousel<?php echo $event['event_id']; ?>" role="button" data-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </a>
                            <a class="carousel-control-next" href="#modalCarousel<?php echo $event['event_id']; ?>" role="button" data-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </a>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($event['images'][0]); ?>"
                             class="img-fluid mb-4 w-100"
                             style="border-radius:12px; max-height:380px; object-fit:cover;"
                             alt="<?php echo htmlspecialchars($event['event_title']); ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Ημερομηνία / Ώρα -->
                <div class="modal-event-meta">
                    <div class="event-info">
                        <i class="far fa-calendar"></i>
                        <strong>Ημερομηνία:</strong>&nbsp;<?php echo $dateFormatted; ?>
                    </div>
                    <div class="event-info">
                        <i class="far fa-clock"></i>
                        <strong>Ώρα:</strong>&nbsp;<?php echo $timeFormatted; ?>
                    </div>
                </div>

                <!-- Περιγραφή -->
                <div class="event-content">
                    <h6>Περιγραφή</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($event['event_description'] ?? '')); ?></p>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
            </div>

        </div>
    </div>
</div>
