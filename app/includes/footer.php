<?php
require_once __DIR__ . '/site_context.php';
require_once __DIR__ . '/../viewmodels/FooterViewModel.php';

$footerViewModel = new FooterViewModel();
$footer_links = $footerViewModel->buildLinks();
?>
<link rel="stylesheet" href="<?php echo site_asset_url('css/site-footer.css'); ?>">


<!-- Κεντρικό footer του site. -->
<footer class="site-footer">
   <div class="footer-main">
       <div class="container">
           <div class="row">


               <!-- 1η στήλη: brand + βασικά στοιχεία σχολείου. -->
               <div class="col-lg-5 col-md-12 mb-4 mb-lg-3 pr-lg-4">
                   <div class="footer-badge">Parents Council</div>
                   <div class="footer-brand">
                       <h5 class="footer-brand-title">Σύνδεσμος Γονέων &amp; Κηδεμόνων<br>Γυμνασίου Αγίου Αθανασίου</h5>
                   </div>
                   <div class="footer-line footer-text"><i class="fas fa-map-marker-alt"></i><span>ΧΡΙΣΤΟΥ ΠΑΠΑΔΟΥΡΗ 50, 4105 ΑΓΙΟΣ ΑΘΑΝΑΣΙΟΣ, Λεμεσός</span></div>
                   <div class="footer-line footer-text"><i class="fas fa-envelope"></i><span>sg.ag.athanasiou@gmail.com</span></div>
               </div>


               <!-- 2η στήλη: γρήγορα links πλοήγησης. -->
               <div class="col-lg-4 col-md-6 mb-4 mb-lg-3">
                   <h5 class="footer-title">Γρήγοροι Σύνδεσμοι</h5>
                   <ul class="footer-links">
                       <?php foreach ($footer_links as $footer_link): ?>
                           <li><a href="<?php echo htmlspecialchars($footer_link['href']); ?>"><?php echo htmlspecialchars($footer_link['label']); ?></a></li>
                       <?php endforeach; ?>
                   </ul>
               </div>


               <!-- 3η στήλη: στοιχεία επικοινωνίας. -->
               <div class="col-lg-3 col-md-6 mb-3">
                   <h5 class="footer-title">Επικοινωνία</h5>
                   <div class="footer-line footer-text"><i class="fas fa-envelope"></i><span>Τηλεομοιότυπο: 25694755</span></div>
                   <div class="footer-line footer-text"><i class="fas fa-phone"></i><span>25694750 , 25694752</span></div>
                   <div class="footer-line footer-text"><i class="fas fa-clock"></i><span>Δευ - Παρ: 07:30 - 13:35</span></div>
               </div>


           </div>
       </div>
   </div>


   <!-- Κάτω γραμμή copyright / trademark. -->
   <div class="footer-bottom">
       <div class="container">
           <div class="footer-bottom-wrap">
               <!-- Αυτό ενημερώνεται αυτόματα κάθε χρόνο με PHP date(\"Y\"). -->
               <div>&copy; <?php echo date("Y"); ?> Σύνδεσμος Γονέων &amp; Κηδεμόνων Αγίου Αθανασίου</div>
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
               <h5 class="modal-title" id="aboutModalLabel">Σχετικά με την Ιστοσελίδα</h5>
               <button type="button" class="close about-modal-close" data-dismiss="modal"><span>&times;</span></button>
           </div>
           <div class="modal-body about-modal-body">
               <p class="mb-1">Η ιστοσελίδα δημιουργήθηκε από τους φοιτητές:</p>
               <ul class="about-people">
                   <li>Κωνσταντίνος Αβραμίδης - <a href="https://www.linkedin.com/in/konstandinos-avramidis-15b517303/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>Μάριος Σιήττας - <a href="https://www.linkedin.com/in/mariosshittas/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>Μιχάλης Τσαδιώτης - <a href="https://www.linkedin.com/in/michalis-tsadiotis-b7152a309/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>Ράσσελλ Βικραμασίγκαμ - <a href="https://www.linkedin.com/in/russell-vickramasingam-3b970835b/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                   <li>Σοφία Κυριάκου - <a href="https://www.linkedin.com/in/sophia-kyriacou19/" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
               </ul>
               <p class="mb-3">Η ανάπτυξη της πραγματοποιήθηκε στο πλαίσιο ακαδημαϊκής εργασίας του τμήματος Μηχανικών Ηλεκτρονικών Υπολογιστών και Πληροφορικής του Τεχνολογικού Πανεπιστημίου Κύπρου (ΤΕΠΑΚ), υπό την επίβλεψη του καθηγητή κ. Ανδρέα Ανδρέου.</p>
               <p class="mt-3">© 2026 Cyprus University of Technology. All rights reserved.</p>
           </div>
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Κλείσιμο</button>
           </div>
       </div>
   </div>
</div>


<!-- Bootstrap JS (loaded here once for all pages) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>




</body>
</html>
