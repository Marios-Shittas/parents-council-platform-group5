<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">

    <title>Ανακοινώσεις</title>
</head>

<body>

<!-- Header -->
<?php include '../app/includes/header.php'; ?>

<div class="container mt-5">

    <h1 class="mb-4">Ανακοινώσεις</h1>

    <div class="row">

        <!-- Announcement 1 -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Συνάντηση Γονέων</h5>
                    <p class="text-muted">01/03/2026</p>
                    <p class="card-text">
                        Σας προσκαλούμε στη γενική συνέλευση του συνδέσμου γονέων
                        που θα πραγματοποιηθεί στην αίθουσα εκδηλώσεων του σχολείου.
                    </p>
                    <a href="#" class="btn btn-primary btn-sm">Δες περισσότερα</a>
                </div>
            </div>
        </div>

        <!-- Announcement 2 -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Εκδρομή Σχολείου</h5>
                    <p class="text-muted">15/03/2026</p>
                    <p class="card-text">
                        Η σχολική εκδρομή θα πραγματοποιηθεί την επόμενη εβδομάδα.
                        Παρακαλούνται οι γονείς να συμπληρώσουν τη σχετική φόρμα.
                    </p>
                    <a href="#" class="btn btn-primary btn-sm">Δες περισσότερα</a>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Footer -->
<?php include '../app/includes/footer.php'; ?>

</body>
</html>