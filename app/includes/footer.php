<?php
require_once __DIR__ . '/site_context.php';

$footer_links = [
    ['label' => 'Αρχική', 'href' => site_section_url('home.php')],
    ['label' => 'Σύνδεσμος Γονέων', 'href' => site_section_url('parents.php')],
    ['label' => 'Ανακοινώσεις', 'href' => site_section_url('announcements.php')],
    ['label' => 'Εκδηλώσεις', 'href' => site_section_url('events.php')],
    ['label' => 'Χρήσιμες Πληροφορίες', 'href' => site_section_url('useful-information.php')],
    ['label' => 'Αιτήσεις', 'href' => site_section_url('applications.php')],
];

if (site_is_parent()) {
    $footer_links[] = ['label' => 'Κατάστημα', 'href' => site_section_url('eshop.php')];
    $footer_links[] = ['label' => 'Φωτογραφίες', 'href' => site_section_url('photos.php')];
}

$footer_links[] = ['label' => 'Επικοινωνία', 'href' => site_section_url('epikoinonia.php')];
?>
<style>
   /* Scoped βάση για να απομονώνεται το footer από global main.css κανόνες. */
   .site-footer,
   .site-footer * {
       box-sizing: border-box;
   }

   /* Κύριο footer block (όλο το κάτω μέρος της σελίδας). */
   .site-footer {
       margin-top: 56px;
       background: #f7f9fc;
       border-top: 1px solid #dde5ef;
       position: relative;
       font-family: 'Lato', sans-serif;
   }


   /* Μικρή μπλε γραμμή πάνω από το footer για σύνδεση με το header. */
   .site-footer::before {
       content: "";
       position: absolute;
       top: 0;
       left: 0;
       right: 0;
       height: 2px;
       background: linear-gradient(90deg, #1a3a5c 0%, #2f6ea0 50%, #1a3a5c 100%);
       opacity: 0.55;
   }


   /* Περιορίζει το πλάτος για να μην "απλώνει" πολύ σε μεγάλες οθόνες. */
   .site-footer .container {
       max-width: 1320px;
   }


   /* Επάνω ζώνη footer (οι 3 στήλες). */
   .footer-main {
       padding: 38px 0 22px;
   }


   /* Τίτλοι στηλών (π.χ. Γρήγοροι Σύνδεσμοι, Επικοινωνία). */
   .footer-title {
       font-family: 'Montserrat', sans-serif;
       font-weight: 700;
       font-size: 1.04rem;
       margin-bottom: 14px;
       color: #1a3a5c;
       letter-spacing: .2px;
   }


   /* Γενικό κείμενο footer. */
   .footer-text {
       font-size: 0.93rem;
       color: #475569;
       margin-bottom: 10px;
       line-height: 1.45;
   }


   /* Block για όνομα σχολείου. */
   .footer-brand {
       margin-bottom: 20px;
   }


   /* Κύριος τίτλος σχολείου στο footer. */
   .footer-brand-title {
       font-family: 'Montserrat', sans-serif;
       font-size: 1.35rem;
       font-weight: 800;
       color: #183555;
       margin-bottom: 6px;
   }


   /* Το badge δεν χρησιμοποιείται τώρα, το κρατάμε κρυφό για μελλοντική χρήση. */
   .footer-badge {
       display: none;
   }


   /* Κάθε γραμμή με icon + κείμενο (διεύθυνση, email, τηλέφωνο). */
   .footer-line {
       display: flex;
       align-items: flex-start;
       margin-bottom: 8px;
   }


   /* Σταθερό πλάτος icon για σωστή στοίχιση. */
   .footer-line i {
       width: 18px;
       margin-right: 8px;
       margin-top: 3px;
       color: #1f4a74;
   }


   /* Grid για γρήγορους συνδέσμους (2 στήλες σε desktop). */
   .footer-links {
       list-style: none;
       padding: 0;
       margin: 0;
       display: grid;
       grid-template-columns: repeat(2, minmax(140px, 1fr));
       gap: 8px 12px;
       max-width: 320px;
   }

   /* Εμφάνιση links στο footer. */
   .footer-links a {
       font-family: 'Lato', sans-serif;
       color: #475569;
       text-decoration: none;
       font-size: 0.93rem;
       transition: color .2s ease, transform .2s ease;
       display: inline-flex;
       align-items: center;
   }


   /* Hover στο link: πιο σκούρο και ελάχιστη μετακίνηση. */
   .footer-links a:hover {
       color: #1a3a5c;
       transform: translateX(2px);
   }


   /* Κάτω άσπρη μπάρα με copyright/trademark. */
   .footer-bottom {
       font-family: 'Lato', sans-serif;
       border-top: 1px solid #dbe3ec;
       padding: 8px 0 9px;
       color: #5f6f82;
       font-size: 0.84rem;
       background: #fdfefe;
   }


   /* 2 στοιχεία στην κάτω μπάρα: αριστερά κείμενο, δεξιά policy. */
   .footer-bottom-wrap {
       display: flex;
       justify-content: space-between;
       align-items: center;
       gap: 12px;
   }


   /* Κουμπί About στο footer. */
   .footer-about-btn {
       font-family: 'Lato', sans-serif;
       background: none;
       border: 1px solid #b0bec5;
       border-radius: 4px;
       color: #5f6f82;
       font-size: 0.84rem;
       padding: 2px 10px;
       cursor: pointer;
       transition: color .2s, border-color .2s;
   }
   .footer-about-btn:hover {
       color: #1a3a5c;
       border-color: #1a3a5c;
   }


   /* Link "All rights reserved.". */
   .footer-policy {
       color: #5f6f82;
       text-decoration: none;
   }


   /* Hover στο policy link. */
   .footer-policy:hover {
       color: #1a3a5c;
       text-decoration: none;
   }

   /* Ο τίτλος του About modal μένει σταθερά λευκός. */
   #aboutModal .modal-title {
       font-family: 'Montserrat', sans-serif;
       color: #ffffff !important;
   }

   #aboutModal .modal-body,
   #aboutModal .modal-footer,
   #aboutModal .btn {
       font-family: 'Lato', sans-serif;
   }

   #aboutModal .about-people {
       padding-left: 1.1rem;
       margin-bottom: 1rem;
   }

   #aboutModal .about-people li {
       margin-bottom: .45rem;
   }

   #aboutModal .about-people a {
       color: #1a3a5c;
       font-weight: 700;
       text-decoration: none;
   }

   #aboutModal .about-people a:hover {
       color: #0f5ea8;
       text-decoration: underline;
   }

   #aboutModal .modal-header .close {
       color: #ffffff;
       opacity: 1;
       transition: color .2s ease, transform .2s ease;
   }

   #aboutModal .modal-header .close:hover {
       color: #dc3545;
       transform: scale(1.05);
   }

   #aboutModal .modal-footer .btn-secondary {
       border-color: #c7d1db;
       background: #eef2f6;
       color: #344055;
       transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
   }

   #aboutModal .modal-footer .btn-secondary:hover,
   #aboutModal .modal-footer .btn-secondary:focus {
       background: #dc3545;
       border-color: #dc3545;
       color: #ffffff;
       transform: translateY(-1px);
   }


   /* Mobile βελτιώσεις footer. */
   @media (max-width: 991.98px) {
       /* Λίγο πιο μικρό padding πάνω/κάτω σε κινητό. */
       .footer-main {
           padding: 32px 0 16px;
       }


       /* Οι σύνδεσμοι γίνονται 1 στήλη για να διαβάζονται εύκολα. */
       .footer-links {
           grid-template-columns: 1fr;
           gap: 7px;
           max-width: 100%;
       }


       /* Το κάτω row σπάει κάθετα για να μη στριμώχνεται. */
       .footer-bottom-wrap {
           flex-direction: column;
           text-align: center;
       }
   }
</style>


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
               <div class="d-flex align-items-center" style="gap:12px;">
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
           <div class="modal-header" style="background:#1a3a5c; color:#fff;">
               <h5 class="modal-title" id="aboutModalLabel">Σχετικά με την Ιστοσελίδα</h5>
               <button type="button" class="close" data-dismiss="modal" style="color:#fff;"><span>&times;</span></button>
           </div>
           <div class="modal-body" style="font-size:0.95rem; line-height:1.7; color:#344055;">
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
