<?php
$site_title = "Γυμνάασιο Αγίου Αθανασίου";
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $site_title; ?></title>

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Header -->
    <header>
        <!-- Navigation bar-->
        <nav class="navbar navbar-expand-lg navbar-light">
            <!-- Logo -->
            <a href="../public/home.php">
                <img src="assets/img/logo-icon.png" class="logo">
            </a>

            <!-- Search bar -->
            <form class="form-inline">
                <input class="form-control" type="search" placeholder="Search">
            </form>

            <!-- Hamburger menu for mobile view -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation links (Contents) -->
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ml-auto text-center">
                    <li class="nav-item active">
                        <a class="nav-link" href="../public/home.php">Home<span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/announcements.php">Announcements</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/applications.php">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/payments.php">Payments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
</body>
</html>