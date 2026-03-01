<?php
require_once __DIR__ . '/../app/includes/header.php';
?>

<!-- Page-specific CSS (μόνο για τη σελίδα Applications) -->
<link rel="stylesheet" href="assets/css/applications.css?v=1">

<div class="applications-page">

    <!-- Hero -->
    <section class="applications-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-file-alt mr-2"></i> Αιτήσεις</h1>
                    <p class="mb-0">Γυμνάσιο Αγίου Αθανασίου &mdash; Διαχείριση αιτήσεων γονέων &amp; κηδεμόνων</p>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <a href="#"
                       class="btn btn-warning btn-new-application text-dark"
                       data-toggle="modal"
                       data-target="#newApplicationModal">
                        <i class="fas fa-plus-circle mr-1"></i> Νέα Αίτηση
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <div class="container mb-5">

        <!-- Stat cards -->
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

        <!-- Quick links -->
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

        <!-- Table header + search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0"><i class="fas fa-list mr-2"></i>Οι Αιτήσεις Μου</h5>
            <div class="input-group input-group-sm applications-search">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                </div>
                <input type="text" class="form-control border-left-0" placeholder="Αναζήτηση αίτησης...">
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm applications-table-card">
            <div class="table-responsive mb-0">
                <table class="table table-hover applications-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Τύπος Αίτησης</th>
                            <th>Ημερομηνία Υποβολής</th>
                            <th>Κατάσταση</th>
                            <th class="text-center">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>

                        <tr>
                            <td><strong>1</strong></td>
                            <td>
                                <span class="app-type-icon bg-primary text-white"><i class="fas fa-user-plus"></i></span>
                                Αίτηση Εγγραφής
                            </td>
                            <td><i class="far fa-calendar-alt text-muted mr-1"></i> 28/02/2026</td>
                            <td>
                                <span class="badge badge-warning badge-status">
                                    <i class="fas fa-clock mr-1"></i>Υποβλήθηκε
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary mr-1" type="button" title="Προβολή">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Λήψη PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>2</strong></td>
                            <td>
                                <span class="app-type-icon bg-success text-white"><i class="fas fa-calendar-check"></i></span>
                                Αίτηση Συμμετοχής Εκδήλωσης
                            </td>
                            <td><i class="far fa-calendar-alt text-muted mr-1"></i> 27/02/2026</td>
                            <td>
                                <span class="badge badge-success badge-status">
                                    <i class="fas fa-check-circle mr-1"></i>Εγκρίθηκε
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary mr-1" type="button" title="Προβολή">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Λήψη PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>3</strong></td>
                            <td>
                                <span class="app-type-icon bg-info text-white"><i class="fas fa-hand-holding-usd"></i></span>
                                Αίτηση Οικονομικής Στήριξης
                            </td>
                            <td><i class="far fa-calendar-alt text-muted mr-1"></i> 25/02/2026</td>
                            <td>
                                <span class="badge badge-danger badge-status">
                                    <i class="fas fa-times-circle mr-1"></i>Απορρίφθηκε
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary mr-1" type="button" title="Προβολή">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Λήψη PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

        <!-- Note -->
        <div class="alert alert-info mt-4 d-flex align-items-center applications-note">
            <i class="fas fa-info-circle fa-lg mr-3"></i>
            <div>
                <strong>Σημείωση:</strong> Οι αιτήσεις εξετάζονται εντός 5 εργάσιμων ημερών.
                Για οποιαδήποτε απορία, επικοινωνήστε με τη γραμματεία του Γυμνασίου Αγίου Αθανασίου.
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/../app/includes/footer.php';
?>