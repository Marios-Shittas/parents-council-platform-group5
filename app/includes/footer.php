<?php
// Arxeio: app\includes\footer.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
require_once __DIR__ . '/site_context.php';
require_once __DIR__ . '/../viewmodels/FooterViewModel.php';

$footerViewModel = new FooterViewModel();
$footer_links = $footerViewModel->buildLinks();
?>
<link rel="stylesheet" href="<?php echo site_asset_url('css/site-footer.css'); ?>">

<!-- ÎšÎµÎ½Ï„ÏÎ¹ÎºÏŒ footer Ï„Î¿Ï… site. -->
<footer class="site-footer">
   <div class="footer-main">
       <div class="container">
           <div class="row">

               <!-- 1Î· ÏƒÏ„Î®Î»Î·: brand + Î²Î±ÏƒÎ¹ÎºÎ¬ ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…. -->
               <div class="col-lg-5 col-md-12 mb-4 mb-lg-3 pr-lg-4">
                   <div class="footer-badge">Parents Council</div>
                   <div class="footer-brand">
                       <h5 class="footer-brand-title">Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½ &amp; ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Ï‰Î½<br>Î“Ï…Î¼Î½Î±ÏƒÎ¯Î¿Ï… Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï…</h5>
                   </div>
                   <div class="footer-line footer-text"><i class="fas fa-map-marker-alt"></i><span>Î§Î¡Î™Î£Î¤ÎŸÎ¥ Î Î‘Î Î‘Î”ÎŸÎ¥Î¡Î— 50, 4105 Î‘Î“Î™ÎŸÎ£ Î‘Î˜Î‘ÎÎ‘Î£Î™ÎŸÎ£, Î›ÎµÎ¼ÎµÏƒÏŒÏ‚</span></div>
                   <div class="footer-line footer-text"><i class="fas fa-envelope"></i><span>sg.ag.athanasiou@gmail.com</span></div>
               </div>

               <!-- 2Î· ÏƒÏ„Î®Î»Î·: Î³ÏÎ®Î³Î¿ÏÎ± links Ï€Î»Î¿Î®Î³Î·ÏƒÎ·Ï‚. -->
               <div class="col-lg-4 col-md-6 mb-4 mb-lg-3">
                   <h5 class="footer-title">Î“ÏÎ®Î³Î¿ÏÎ¿Î¹ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹</h5>
                   <ul class="footer-links">
                       <?php foreach ($footer_links as $footer_link): ?>
                           <li><a href="<?php echo htmlspecialchars($footer_link['href']); ?>"><?php echo htmlspecialchars($footer_link['label']); ?></a></li>
                       <?php endforeach; ?>
                   </ul>
               </div>

               <!-- 3Î· ÏƒÏ„Î®Î»Î·: ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± ÎµÏ€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚. -->
               <div class="col-lg-3 col-md-6 mb-3">
                   <h5 class="footer-title">Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±</h5>
                   <div class="footer-line footer-text"><i class="fas fa-envelope"></i><span>Î¤Î·Î»ÎµÎ¿Î¼Î¿Î¹ÏŒÏ„Ï…Ï€Î¿: 25694755</span></div>
                   <div class="footer-line footer-text"><i class="fas fa-phone"></i><span>25694750 , 25694752</span></div>
                   <div class="footer-line footer-text"><i class="fas fa-clock"></i><span>Î”ÎµÏ… - Î Î±Ï: 07:30 - 13:35</span></div>
               </div>

           </div>
       </div>
   </div>

   <!-- ÎšÎ¬Ï„Ï‰ Î³ÏÎ±Î¼Î¼Î® copyright / trademark. -->
   <div class="footer-bottom">
       <div class="container">
           <div class="footer-bottom-wrap">
               <!-- Sxolio: voithitiko HTML tmima gia tin parakato provoli. -->
               <div>&copy; <?php echo date("Y"); ?> Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½ &amp; ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Ï‰Î½ Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï…</div>
               <div class="d-flex align-items-center footer-about-actions">
                   <button class="footer-about-btn" data-toggle="modal" data-target="#aboutModal">About</button>
               </div>
           </div>
       </div>
   </div>
</footer>

<!-- About Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="aboutModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" role="document">
       <div class="modal-content">
           <div class="modal-header about-modal-header">
               <h5 class="modal-title" id="aboutModalLabel">Î£Ï‡ÎµÏ„Î¹ÎºÎ¬ Î¼Îµ Ï„Î·Î½ Î™ÏƒÏ„Î¿ÏƒÎµÎ»Î¯Î´Î±</h5>
               <button type="button" class="close about-modal-close" data-dismiss="modal"><span>&times;</span></button>
           </div>
           <div class="modal-body about-modal-body">
               <p class="mb-1">Î— Î¹ÏƒÏ„Î¿ÏƒÎµÎ»Î¯Î´Î± Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î®Î¸Î·ÎºÎµ Î±Ï€ÏŒ Ï„Î¿Ï…Ï‚ Ï†Î¿Î¹Ï„Î·Ï„Î­Ï‚:</p>
               <ul class="about-people">
                   <li>ÎšÏ‰Î½ÏƒÏ„Î±Î½Ï„Î¯Î½Î¿Ï‚ Î‘Î²ÏÎ±Î¼Î¯Î´Î·Ï‚ - <a href="https://www.linkedin.com/in/konstandinos-avramidis-15b517303/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>ÎœÎ¬ÏÎ¹Î¿Ï‚ Î£Î¹Î®Ï„Ï„Î±Ï‚ - <a href="https://www.linkedin.com/in/mariosshittas/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>ÎœÎ¹Ï‡Î¬Î»Î·Ï‚ Î¤ÏƒÎ±Î´Î¹ÏŽÏ„Î·Ï‚ - <a href="https://www.linkedin.com/in/michalis-tsadiotis-b7152a309/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>Î¡Î¬ÏƒÏƒÎµÎ»Î» Î’Î¹ÎºÏÎ±Î¼Î±ÏƒÎ¯Î³ÎºÎ±Î¼ - <a href="https://www.linkedin.com/in/russell-vickramasingam-3b970835b/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>Î£Î¿Ï†Î¯Î± ÎšÏ…ÏÎ¹Î¬ÎºÎ¿Ï… - <a href="https://www.linkedin.com/in/sophia-kyriacou19/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
               </ul>
               <p class="mb-3">Î— Î±Î½Î¬Ï€Ï„Ï…Î¾Î· Ï„Î·Ï‚ Ï€ÏÎ±Î³Î¼Î±Ï„Î¿Ï€Î¿Î¹Î®Î¸Î·ÎºÎµ ÏƒÏ„Î¿ Ï€Î»Î±Î¯ÏƒÎ¹Î¿ Î±ÎºÎ±Î´Î·Î¼Î±ÏŠÎºÎ®Ï‚ ÎµÏÎ³Î±ÏƒÎ¯Î±Ï‚ Ï„Î¿Ï… Ï„Î¼Î®Î¼Î±Ï„Î¿Ï‚ ÎœÎ·Ï‡Î±Î½Î¹ÎºÏŽÎ½ Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÏŽÎ½ Î¥Ï€Î¿Î»Î¿Î³Î¹ÏƒÏ„ÏŽÎ½ ÎºÎ±Î¹ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¹ÎºÎ®Ï‚ Ï„Î¿Ï… Î¤ÎµÏ‡Î½Î¿Î»Î¿Î³Î¹ÎºÎ¿Ï Î Î±Î½ÎµÏ€Î¹ÏƒÏ„Î·Î¼Î¯Î¿Ï… ÎšÏÏ€ÏÎ¿Ï… (Î¤Î•Î Î‘Îš), Ï…Ï€ÏŒ Ï„Î·Î½ ÎµÏ€Î¯Î²Î»ÎµÏˆÎ· Ï„Î¿Ï… ÎºÎ±Î¸Î·Î³Î·Ï„Î® Îº. Î‘Î½Î´ÏÎ­Î± Î‘Î½Î´ÏÎ­Î¿Ï….</p>
               <p class="mt-3">Â© 2026 Cyprus University of Technology. All rights reserved.</p>
           </div>
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">ÎšÎ»ÎµÎ¯ÏƒÎ¹Î¼Î¿</button>
           </div>
       </div>
   </div>
</div>

<!-- Bootstrap JS (loaded here once for all pages) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
