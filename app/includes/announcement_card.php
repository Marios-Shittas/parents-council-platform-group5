<?php
require_once __DIR__ . '/site_context.php';
$annImages     = !empty($announcement['images']) ? $announcement['images'] : [$defaultImage];
$annImageCount = count($announcement['images'] ?? []);
$annAttachments = is_array($announcement['attachments'] ?? null) ? $announcement['attachments'] : [];
$attachmentCount = count($annAttachments);
$annDateSource = $announcement['announcement_date'] ?? $announcement['publish_date'];
$annDate       = new DateTime($annDateSource);
$annDateFmt    = $annDate->format('d/m/Y');
$annDayMonth   = strtoupper($annDate->format('d M'));
$publishDateFmt = date('d/m/Y', strtotime($announcement['publish_date']));

$pdfAttachmentCount = 0;
$imageAttachmentCount = 0;

foreach ($annAttachments as $attachment) {
    $attachmentPath = (string)($attachment['file_path'] ?? '');
    $attachmentExt = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));

    if ($attachmentExt === 'pdf') {
        $pdfAttachmentCount++;
        continue;
    }

    if (in_array($attachmentExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $imageAttachmentCount++;
    }
}

$attachmentSummary = '';
if ($attachmentCount === 1 && $pdfAttachmentCount === 1) {
    $attachmentSummary = 'Περιέχει συνημμένο PDF';
} elseif ($attachmentCount === 1 && $imageAttachmentCount === 1) {
    $attachmentSummary = 'Περιέχει συνημμένη εικόνα';
} elseif ($attachmentCount > 1 && $pdfAttachmentCount === $attachmentCount) {
    $attachmentSummary = 'Περιέχει ' . $attachmentCount . ' συνημμένα PDF';
} elseif ($attachmentCount > 1 && $imageAttachmentCount === $attachmentCount) {
    $attachmentSummary = 'Περιέχει ' . $attachmentCount . ' συνημμένες εικόνες';
} elseif ($attachmentCount > 0) {
    $attachmentSummary = 'Περιέχει ' . $attachmentCount . ' συνημμένα αρχεία';
}
?>
<div class="col-xl-4 col-md-6 mb-4">
    <div class="announcement-card">

        <!-- Image area -->
        <div class="announcement-card-img-wrap">

            <?php if ($annImageCount > 1): ?>
                <!-- Mini carousel για πολλές φωτογραφίες -->
                <div id="cardCarouselAnn<?php echo $announcement['announcement_id']; ?>" class="carousel slide" data-ride="carousel" data-interval="3500">
                    <div class="carousel-inner">
                        <?php foreach ($annImages as $i => $img): ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                     alt="<?php echo htmlspecialchars($announcement['announcement_title']); ?>"
                                     onerror="this.src='<?php echo $defaultImage; ?>'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a class="carousel-control-prev" href="#cardCarouselAnn<?php echo $announcement['announcement_id']; ?>" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </a>
                    <a class="carousel-control-next" href="#cardCarouselAnn<?php echo $announcement['announcement_id']; ?>" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </a>
                </div>
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($annImages[0]); ?>"
                     alt="<?php echo htmlspecialchars($announcement['announcement_title']); ?>"
                     onerror="this.src='<?php echo $defaultImage; ?>'">
            <?php endif; ?>

            <!-- Ημερομηνία badge -->
            <div class="announcement-date-badge">
                <i class="far fa-calendar-alt mr-1"></i><?php echo $annDayMonth; ?>
            </div>

            <!-- Photo count badge (μόνο αν >1 φωτογραφία) -->
            <?php if ($annImageCount > 1): ?>
                <div class="announcement-photo-count">
                    <i class="fas fa-images mr-1"></i><?php echo $annImageCount; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Card content -->
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($announcement['announcement_title']); ?></h5>

            <div class="announcement-info">
                <i class="far fa-calendar"></i>
                <span><?php echo $annDateFmt; ?></span>
            </div>

            <?php if ($attachmentSummary !== ''): ?>
                <div class="announcement-attachment-hint">
                    <i class="fas fa-paperclip"></i>
                    <span><?php echo htmlspecialchars($attachmentSummary); ?></span>
                </div>
            <?php endif; ?>

            <p class="card-text">
                <?php
                    $annDesc = $announcement['announcement_description'] ?? '';
                    echo htmlspecialchars(mb_substr($annDesc, 0, 110));
                    if (mb_strlen($annDesc) > 110) echo '…';
                ?>
            </p>

            <button class="btn btn-announcement-details btn-sm"
                    data-toggle="modal"
                    data-target="#announcementModal<?php echo $announcement['announcement_id']; ?>">
                <i class="fas fa-info-circle mr-1"></i><?php echo $attachmentCount > 0 ? 'Περισσότερα & Αρχεία' : 'Περισσότερα'; ?>
            </button>
        </div>

    </div>
</div>

<!-- Modal -->
<div class="modal fade announcement-modal" id="announcementModal<?php echo $announcement['announcement_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header" style="background: linear-gradient(135deg, #0057a8, #003d7a); border: none;">
                <h5 class="modal-title" style="color:#ffffff !important; font-weight:700;">
                    <i class="fas fa-bullhorn mr-2" style="color:#ffffff !important;"></i><?php echo htmlspecialchars($announcement['announcement_title']); ?>
                </h5>
                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                        style="color:#ffffff !important; opacity:1; text-shadow:none; border:none; background:transparent;">
                    <span aria-hidden="true" style="color:#ffffff !important;">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <?php if (!empty($announcement['images'])): ?>
                    <?php if (count($announcement['images']) > 1): ?>
                        <!-- Full carousel με indicators -->
                        <div id="modalCarouselAnn<?php echo $announcement['announcement_id']; ?>" class="carousel slide mb-4" data-ride="carousel">
                            <ol class="carousel-indicators">
                                <?php foreach ($announcement['images'] as $i => $img): ?>
                                    <li data-target="#modalCarouselAnn<?php echo $announcement['announcement_id']; ?>"
                                        data-slide-to="<?php echo $i; ?>"
                                        class="<?php echo $i === 0 ? 'active' : ''; ?>"></li>
                                <?php endforeach; ?>
                            </ol>
                            <div class="carousel-inner">
                                <?php foreach ($announcement['images'] as $index => $img): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100"
                                             alt="Φωτογραφία <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a class="carousel-control-prev" href="#modalCarouselAnn<?php echo $announcement['announcement_id']; ?>" role="button" data-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </a>
                            <a class="carousel-control-next" href="#modalCarouselAnn<?php echo $announcement['announcement_id']; ?>" role="button" data-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </a>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($announcement['images'][0]); ?>"
                             class="img-fluid mb-4 w-100"
                             style="border-radius:12px; max-height:380px; object-fit:cover;"
                             alt="<?php echo htmlspecialchars($announcement['announcement_title']); ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Ημερομηνία -->
                <div class="modal-announcement-meta">
                    <div class="announcement-info">
                        <i class="far fa-calendar"></i>
                        <strong>Ημερομηνία Ανακοίνωσης:</strong>&nbsp;<?php echo $annDateFmt; ?>
                    </div>
                    <div class="announcement-info">
                        <i class="far fa-clock"></i>
                        <strong>Ημ. Δημοσίευσης:</strong>&nbsp;<?php echo $publishDateFmt; ?>
                    </div>
                </div>

                <!-- Περιγραφή -->
                <div class="announcement-content">
                    <h6>Περιγραφή</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($announcement['announcement_description'] ?? '')); ?></p>
                </div>

                <?php if (trim((string)($announcement['gdpr_notice'] ?? '')) !== ''): ?>
                    <div class="announcement-gdpr-note">
                        <strong>Ενημέρωση GDPR:</strong>
                        <?php echo nl2br(htmlspecialchars((string)$announcement['gdpr_notice'])); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($annAttachments)): ?>
                    <div class="announcement-content">
                        <h6>Συνημμένες Επιστολές</h6>
                        <div class="stack-links">
                            <?php foreach ($annAttachments as $attachment): ?>
                                <?php
                                $attachmentName = trim((string)($attachment['original_name'] ?? '')) !== ''
                                    ? (string)$attachment['original_name']
                                    : basename((string)($attachment['file_path'] ?? ''));
                                $attachmentUrl = site_resolve_content_url((string)($attachment['file_path'] ?? ''));
                                ?>
                                <a class="action-link" href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-paperclip"></i>
                                    <?php echo htmlspecialchars($attachmentName); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
            </div>

        </div>
    </div>
</div>
