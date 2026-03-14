<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/user_css/public-page-header.css">
    <link rel="stylesheet" href="assets/css/user_css/useful-information.css">

    <title>Χρήσιμες Πληροφορίες - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body>
<?php include __DIR__ . '/../app/includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Χρήσιμες Πληροφορίες';
$pageHeaderSubtitle = 'Συγκεντρωμένες βασικές πληροφορίες για τη σχολική χρονιά, τις αργίες, τη στολή, την ασφάλεια και τα χρήσιμα έντυπα.';
$pageHeaderIcon = 'fas fa-info-circle';
$pageHeaderEyebrow = 'Οδηγός Γονέων Και Μαθητών';
include __DIR__ . '/../app/includes/public_page_header.php';
?>

<main class="useful-info-page pb-5">
    <div class="container">
        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-link"></i></span>
                <div>
                    <h2>Γρήγοροι Σύνδεσμοι</h2>
                    <p>Άμεση πρόσβαση στις πιο χρήσιμες επίσημες σελίδες.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a class="info-link-card" href="https://gym-ag-athanasios-lem.schools.ac.cy/" target="_blank" rel="noopener noreferrer">
                        <span class="info-link-card__icon"><i class="fas fa-school"></i></span>
                        <h3>Ιστοσελίδα Σχολείου</h3>
                        <p>Η επίσημη ιστοσελίδα του Γυμνασίου Αγίου Αθανασίου.</p>
                    </a>
                </div>
                <div class="col-lg-4 mb-4">
                    <a class="info-link-card" href="https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations" target="_blank" rel="noopener noreferrer">
                        <span class="info-link-card__icon"><i class="fas fa-file-download"></i></span>
                        <h3>Έντυπα & Εγγραφές</h3>
                        <p>Σελίδα με χρήσιμα έντυπα εγγραφών, μετακινήσεων και ανακοινώσεων.</p>
                    </a>
                </div>
                <div class="col-lg-4 mb-4">
                    <a class="info-link-card" href="https://www.moec.gov.cy/politiki_amyna/ay_entypa.html" target="_blank" rel="noopener noreferrer">
                        <span class="info-link-card__icon"><i class="fas fa-shield-alt"></i></span>
                        <h3>Έντυπα Ασφάλειας</h3>
                        <p>Επίσημα έντυπα του ΥΠΑΝ για θέματα ασφάλειας και καταγραφής ατυχημάτων.</p>
                    </a>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-calendar-check"></i></span>
                <div>
                    <h2>Σχολική Χρονιά 2025-2026</h2>
                    <p>Βασικές ημερομηνίες για τα δημόσια γυμνάσια στην Κύπρο.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="highlight-card">
                        <span class="highlight-card__label">Έναρξη Α' Τετραμήνου</span>
                        <strong>5 Σεπτεμβρίου 2025</strong>
                        <p>Έναρξη της σχολικής χρονιάς για τη Μέση Εκπαίδευση.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="highlight-card">
                        <span class="highlight-card__label">Λήξη Α' Τετραμήνου</span>
                        <strong>15 Ιανουαρίου 2026</strong>
                        <p>Ολοκλήρωση του πρώτου τετραμήνου.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="highlight-card">
                        <span class="highlight-card__label">Β' Τετράμηνο</span>
                        <strong>16 Ιανουαρίου 2026</strong>
                        <p>Συνεχίζεται μέχρι το τέλος των προαγωγικών εξετάσεων.</p>
                    </div>
                </div>
            </div>

            <div class="note-card">
                <i class="fas fa-info-circle"></i>
                <p>Η ακριβής τελευταία ημέρα φοίτησης εξαρτάται από το πρόγραμμα των προαγωγικών εξετάσεων και τις ανακοινώσεις της σχολικής μονάδας.</p>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-calendar-alt"></i></span>
                <div>
                    <h2>Επίσημες Αργίες</h2>
                    <p>Οι βασικές σχολικές αργίες που ισχύουν για τα δημόσια γυμνάσια.</p>
                </div>
            </div>

            <div class="table-responsive holiday-table-wrap">
                <table class="table holiday-table mb-0">
                    <thead>
                        <tr>
                            <th>Ημερομηνία</th>
                            <th>Αργία</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>1 Οκτωβρίου 2025</td><td>Ημέρα Ανεξαρτησίας της Κύπρου</td></tr>
                        <tr><td>28 Οκτωβρίου 2025</td><td>Εθνική Επέτειος</td></tr>
                        <tr><td>11 Δεκεμβρίου 2025</td><td>Ημέρα Εκπαιδευτικού</td></tr>
                        <tr><td>24 Δεκεμβρίου 2025 - 6 Ιανουαρίου 2026</td><td>Διακοπές Χριστουγέννων</td></tr>
                        <tr><td>30 Ιανουαρίου 2026</td><td>Τριών Ιεραρχών και Ελληνικών Γραμμάτων</td></tr>
                        <tr><td>10 Φεβρουαρίου 2026</td><td>Ημέρα Εκπαιδευτικού</td></tr>
                        <tr><td>23 Φεβρουαρίου 2026</td><td>Καθαρά Δευτέρα</td></tr>
                        <tr><td>25 Μαρτίου 2026</td><td>Εθνική Επέτειος</td></tr>
                        <tr><td>1 Απριλίου 2026</td><td>Εθνική Επέτειος ΕΟΚΑ</td></tr>
                        <tr><td>6 Απριλίου - 19 Απριλίου 2026</td><td>Διακοπές Πάσχα</td></tr>
                        <tr><td>23 Απριλίου 2026</td><td>Ονομαστήρια Αρχιεπισκόπου Κύπρου</td></tr>
                        <tr><td>1 Μαΐου 2026</td><td>Πρωτομαγιά</td></tr>
                        <tr><td>1 Ιουνίου 2026</td><td>Αγίου Πνεύματος</td></tr>
                        <tr><td>11 Ιουνίου 2026</td><td>Αποστόλου Βαρνάβα</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-user-shield"></i></span>
                <div>
                    <h2>Ασφάλεια Παιδιών & Χρήσιμα Έντυπα</h2>
                    <p>Χρήσιμη ενημέρωση για ασφάλεια στο σχολείο και επίσημες λήψεις εντύπων.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="content-card h-100">
                        <ul class="feature-list">
                            <li>Για θέματα πρόληψης, ασφάλειας και υγείας στο σχολείο, αρμόδιο είναι το Γραφείο Πολιτικής Άμυνας, Ασφάλειας και Υγείας του ΥΠΑΝ.</li>
                            <li>Σε περίπτωση περιστατικού ή ατυχήματος, η ενημέρωση της σχολικής μονάδας πρέπει να γίνεται άμεσα, ώστε να ακολουθηθεί η προβλεπόμενη διαδικασία.</li>
                            <li>Για επίσημα έντυπα καταγραφής ατυχημάτων και άλλα σχετικά έγγραφα, χρησιμοποιείτε τα έντυπα του ΥΠΑΝ.</li>
                            <li>Για ετήσιες ανακοινώσεις σχετικά με πιθανή ασφαλιστική κάλυψη μαθητών, οι γονείς θα πρέπει να παρακολουθούν τις ανακοινώσεις του σχολείου και του Συνδέσμου Γονέων.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-5 mb-4">
                    <div class="content-card h-100">
                        <h3>Χρήσιμες Λήψεις</h3>
                        <div class="stack-links">
                            <a class="action-link" href="https://www.moec.gov.cy/politiki_amyna/ay_entypa.html" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-download"></i>
                                Έντυπα Ασφάλειας και Καταγραφής Ατυχημάτων
                            </a>
                            <a class="action-link" href="https://www.moec.gov.cy/politiki_amyna/ay_epimorfotiko_yliko.html" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-book-open"></i>
                                Επιμορφωτικό Υλικό Ασφάλειας και Υγείας
                            </a>
                            <a class="action-link" href="https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-folder-open"></i>
                                Έντυπα και ανακοινώσεις του σχολείου
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-tshirt"></i></span>
                <div>
                    <h2>Μαθητική Στολή</h2>
                    <p>Συνοπτική παρουσίαση με βάση τους εσωτερικούς κανονισμούς του σχολείου.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="content-card h-100">
                        <h3>Αγόρια</h3>
                        <ul class="feature-list compact">
                            <li>Γκρίζο παντελόνι</li>
                            <li>Άσπρο πουκάμισο, T-shirt ή polo</li>
                            <li>Μπλε σκούρο πουλόβερ</li>
                            <li>Δεν επιτρέπονται jeans ή αθλητικές φόρμες στην καθημερινή στολή</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="content-card h-100">
                        <h3>Κορίτσια</h3>
                        <ul class="feature-list compact">
                            <li>Γκρίζα φούστα ή γκρίζο παντελόνι</li>
                            <li>Άσπρο πουκάμισο, T-shirt ή polo</li>
                            <li>Μπλε σκούρο πουλόβερ</li>
                            <li>Δεν επιτρέπονται jeans ή κολάν στην καθημερινή στολή</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="content-card h-100">
                        <h3>Στολή Γυμναστικής</h3>
                        <ul class="feature-list compact">
                            <li>Μαύρο ή μπλε παντελόνι φόρμας</li>
                            <li>Άσπρη, γκρίζα ή σχολική φανέλα</li>
                            <li>Αθλητικά παπούτσια</li>
                            <li>Πρακτική και ασφαλής ενδυμασία για το μάθημα Φυσικής Αγωγής</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="note-card">
                <i class="fas fa-external-link-alt"></i>
                <p>Για τις πλήρεις λεπτομέρειες της στολής και των κανονισμών, δείτε τους επίσημους εσωτερικούς κανονισμούς του σχολείου.</p>
                <a class="btn btn-primary btn-sm ml-sm-3 mt-3 mt-sm-0" href="https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/september/esoterikoi-kanonismoi-2025-2026.pdf" target="_blank" rel="noopener noreferrer">Προβολή Κανονισμών</a>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
