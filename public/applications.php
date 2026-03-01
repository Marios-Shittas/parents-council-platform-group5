<!-- Εδώ λέμε στον browser ότι αυτή είναι μια HTML σελίδα και η γλώσσα είναι ελληνικά -->
<!DOCTYPE html>
<html lang="el">
    <head>
        <!-- Ρυθμίσεις για να εμφανίζονται σωστά οι ελληνικοί χαρακτήρες -->
        <meta charset="UTF-8">
        <!-- Ρύθμιση για να φαίνεται σωστά η σελίδα σε κινητά -->
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <!-- Φορτώνουμε ωραίες γραμματοσειρές από το Google (Montserrat και Lato) -->
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    
        <!-- Φορτώνουμε το Bootstrap - είναι ένα έτοιμο εργαλείο που μας βοηθάει να φτιάξουμε όμορφες σελίδες εύκολα -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

        <!-- Φορτώνουμε το δικό μας αρχείο CSS - εδώ βάζουμε τα δικά μας στυλ (χρώματα, μεγέθη κτλ) -->
        <link rel="stylesheet" href="assets/css/main.css">
    </head>
    
    <!-- Εδω ξεκινάει το σώμα της σελίδας - ό,τι βλέπει ο χρήστης -->
    <body>
    <!-- Φορτώνουμε τα εικονίδια Font Awesome (π.χ. το ματάκι, το ρολόι, το + κτλ) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- ============================================= -->
    <!-- ΣΤΥΛ ΣΕΛΙΔΑΣ: Εδώ καθορίζουμε πώς θα φαίνονται τα διάφορα στοιχεία -->
    <!-- Τα χρώματα, τα μεγέθη, οι σκιές, τα εφέ κτλ -->
    <!-- ============================================= -->
    <style>
        /* --- Στυλ για την μπλε μπάρα στην κορυφή (hero) με τον τίτλο "Αιτήσεις" --- */
        .applications-hero {
            background: linear-gradient(135deg, #1a3a5c 0%, #2a6496 100%);
            color: #fff;
            padding: 40px 0 30px;
            margin-bottom: 30px;
        }
        .applications-hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2rem;
        }
        .applications-hero p {
            font-family: 'Lato', sans-serif;
            font-weight: 300;
            opacity: 0.9;
        }
        /* --- Στυλ για τις 4 κάρτες στατιστικών (Σύνολο, Αναμονή, Εγκρίθηκαν, Απορρίφθηκαν) --- */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card .card-body {
            padding: 20px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .stat-number {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
        }
        /* --- Στυλ για τον πίνακα με τις αιτήσεις (επικεφαλίδες και κελιά) --- */
        .applications-table th {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f8f9fa;
            border-top: none;
        }
        .applications-table td {
            vertical-align: middle;
        }
        /* --- Στυλ για τις ετικέτες κατάστασης (Υποβλήθηκε, Εγκρίθηκε, Απορρίφθηκε) --- */
        .badge-status {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
        }
        /* --- Στυλ για το κίτρινο κουμπί "Νέα Αίτηση" --- */
        .btn-new-application {
            background: #ffc107;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            color: #1a3a5c !important;
        }
        .btn-new-application:hover {
            background: #e0a800;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,193,7,0.4);
            color: #1a3a5c !important;
        }
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #1a3a5c;
            font-size: 1.3rem;
        }
        /* --- Στυλ για τα μικρά χρωματιστά εικονίδια δίπλα στον τύπο αίτησης στον πίνακα --- */
        .app-type-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 0.9rem;
        }
        /* --- Στυλ για τις 3 κάρτες γρήγορης υποβολής (Εγγραφή, Εκδήλωση, Οικονομική) --- */
        .quick-links .card {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .quick-links .card:hover {
            border-color: #2a6496;
            background-color: #f0f7ff;
        }
    </style>

    <!-- ============================================= -->
    <!-- HEADER: Φορτώνει το πάνω μέρος της σελίδας (logo, μενού πλοήγησης, αναζήτηση) -->
    <!-- Ο κώδικας βρίσκεται στο αρχείο header.php και είναι κοινός σε όλες τις σελίδες -->
    <!-- ============================================= -->
    <?php include '../app/includes/header.php'; ?>

    <!-- ============================================= -->
    <!-- ΜΠΛΕ ΜΠΑΡΑ ΤΙΤΛΟΥ (Hero Section) -->
    <!-- Εδώ εμφανίζεται ο τίτλος "Αιτήσεις" και το κουμπί "Νέα Αίτηση" -->
    <!-- ============================================= -->
    <section class="applications-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-file-alt mr-2"></i> Αιτήσεις</h1>
                    <p class="mb-0">Γυμνάσιο Αγίου Αθανασίου &mdash; Διαχείριση αιτήσεων γονέων &amp; κηδεμόνων</p>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <a href="#" class="btn btn-warning btn-new-application text-dark" data-toggle="modal" data-target="#newApplicationModal">
                        <i class="fas fa-plus-circle mr-1"></i> Νέα Αίτηση
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Κεντρικό περιεχόμενο σελίδας - το container κεντράρει τα πάντα στη μέση -->
    <div class="container mb-5">

        <!-- ============================================= -->
        <!-- ΚΑΡΤΕΣ ΣΤΑΤΙΣΤΙΚΩΝ: 4 κάρτες που δείχνουν πόσες αιτήσεις έχουμε σε κάθε κατάσταση -->
        <!-- ============================================= -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary text-white mr-3">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <div class="stat-number text-primary">3</div>
                            <small class="text-muted">Σύνολο</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning text-white mr-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="stat-number text-warning">1</div>
                            <small class="text-muted">Σε Αναμονή</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success text-white mr-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div class="stat-number text-success">1</div>
                            <small class="text-muted">Εγκρίθηκαν</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-danger text-white mr-3">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <div class="stat-number text-danger">1</div>
                            <small class="text-muted">Απορρίφθηκαν</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================= -->
        <!-- ΓΡΗΓΟΡΗ ΥΠΟΒΟΛΗ: 3 κάρτες-συντομεύσεις για να κάνεις αίτηση γρήγορα -->
        <!-- Κάθε κάρτα είναι κλικάρισμα και σε πάει στη φόρμα αίτησης -->
        <!-- ============================================= -->
        <h5 class="section-title mb-3"><i class="fas fa-bolt mr-2"></i>Γρήγορη Υποβολή</h5>
        <div class="row quick-links mb-4">
            <div class="col-md-4 mb-3">
                <a href="#" class="text-decoration-none">
                    <div class="card text-center p-3">
                        <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
                        <h6 class="mb-1 text-dark">Εγγραφή Μαθητή</h6>
                        <small class="text-muted">Αίτηση εγγραφής νέου μαθητή</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="#" class="text-decoration-none">
                    <div class="card text-center p-3">
                        <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                        <h6 class="mb-1 text-dark">Συμμετοχή σε Εκδήλωση</h6>
                        <small class="text-muted">Δήλωση συμμετοχής σε σχολική εκδήλωση</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="#" class="text-decoration-none">
                    <div class="card text-center p-3">
                        <i class="fas fa-hand-holding-usd fa-2x text-info mb-2"></i>
                        <h6 class="mb-1 text-dark">Οικονομική Στήριξη</h6>
                        <small class="text-muted">Αίτηση οικονομικής βοήθειας</small>
                    </div>
                </a>
            </div>
        </div>

        <!-- ============================================= -->
        <!-- ΠΙΝΑΚΑΣ ΑΙΤΗΣΕΩΝ: Εδώ εμφανίζονται όλες οι αιτήσεις σε πίνακα -->
        <!-- Κάθε γραμμή = μία αίτηση, με τα στοιχεία της (τύπος, ημερομηνία, κατάσταση) -->
        <!-- ============================================= -->

        <!-- Τίτλος πίνακα αριστερά + Μπάρα αναζήτησης δεξιά -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0"><i class="fas fa-list mr-2"></i>Οι Αιτήσεις Μου</h5>
            <div>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" class="form-control border-left-0" placeholder="Αναζήτηση αίτησης...">
                </div>
            </div>
        </div>

        <!-- Ο πίνακας μέσα σε κάρτα με σκιά για ωραία εμφάνιση -->
        <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <!-- table-responsive = αν η οθόνη είναι μικρή, μπορείς να κάνεις scroll δεξιά-αριστερά -->
            <div class="table-responsive mb-0">
                <table class="table table-hover applications-table mb-0">
                    <!-- Επικεφαλίδες στηλών πίνακα -->
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Τύπος Αίτησης</th>
                            <th>Ημερομηνία Υποβολής</th>
                            <th>Κατάσταση</th>
                            <th class="text-center">Ενέργειες</th>
                        </tr>
                    </thead>
                    <!-- Εδώ μπαίνουν οι γραμμές του πίνακα - κάθε <tr> είναι μία αίτηση -->
                    <tbody>
                        <!-- === ΑΙΤΗΣΗ 1: Εγγραφή (κατάσταση: Υποβλήθηκε) === -->
                        <tr>
                            <td><strong>1</strong></td>
                            <td>
                                <span class="app-type-icon bg-primary text-white"><i class="fas fa-user-plus"></i></span>
                                Αίτηση Εγγραφής
                            </td>
                            <td>
                                <i class="far fa-calendar-alt text-muted mr-1"></i> 28/02/2026
                            </td>
                            <td>
                                <span class="badge badge-warning badge-status">
                                    <i class="fas fa-clock mr-1"></i>Υποβλήθηκε
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary mr-1" title="Προβολή">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" title="Λήψη PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- === ΑΙΤΗΣΗ 2: Συμμετοχή Εκδήλωσης (κατάσταση: Εγκρίθηκε) === -->
                        <tr>
                            <td><strong>2</strong></td>
                            <td>
                                <span class="app-type-icon bg-success text-white"><i class="fas fa-calendar-check"></i></span>
                                Αίτηση Συμμετοχής Εκδήλωσης
                            </td>
                            <td>
                                <i class="far fa-calendar-alt text-muted mr-1"></i> 27/02/2026
                            </td>
                            <td>
                                <span class="badge badge-success badge-status">
                                    <i class="fas fa-check-circle mr-1"></i>Εγκρίθηκε
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary mr-1" title="Προβολή">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" title="Λήψη PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- === ΑΙΤΗΣΗ 3: Οικονομική Στήριξη (κατάσταση: Απορρίφθηκε) === -->
                        <tr>
                            <td><strong>3</strong></td>
                            <td>
                                <span class="app-type-icon bg-info text-white"><i class="fas fa-hand-holding-usd"></i></span>
                                Αίτηση Οικονομικής Στήριξης
                            </td>
                            <td>
                                <i class="far fa-calendar-alt text-muted mr-1"></i> 25/02/2026
                            </td>
                            <td>
                                <span class="badge badge-danger badge-status">
                                    <i class="fas fa-times-circle mr-1"></i>Απορρίφθηκε
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary mr-1" title="Προβολή">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" title="Λήψη PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================= -->
        <!-- ΣΗΜΕΙΩΣΗ: Ένα μπλε κουτάκι με πληροφορίες για τον χρήστη -->
        <!-- ============================================= -->
        <div class="alert alert-info mt-4 d-flex align-items-center" style="border-radius: 10px;">
            <i class="fas fa-info-circle fa-lg mr-3"></i>
            <div>
                <strong>Σημείωση:</strong> Οι αιτήσεις εξετάζονται εντός 5 εργάσιμων ημερών. 
                Για οποιαδήποτε απορία, επικοινωνήστε με τη γραμματεία του Γυμνασίου Αγίου Αθανασίου.
            </div>
        </div>

    </div>

    <!-- ============================================= -->
    <!-- FOOTER: Φορτώνει το κάτω μέρος της σελίδας (copyright κτλ) -->
    <!-- Ο κώδικας βρίσκεται στο αρχείο footer.php και είναι κοινός σε όλες τις σελίδες -->
    <!-- ============================================= -->
    <?php include '../app/includes/footer.php'; ?>
    </body>
</html>