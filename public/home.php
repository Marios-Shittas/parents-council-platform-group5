<!DOCTYPE html>
<html lang="el">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <!-- Google fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

        <!-- Costom CSS -->
        <link rel="stylesheet" href="assets/css/main.css">
    </head>
    
    <body>
    <!-- Header -->
    <?php include '../app/includes/header.php'; ?>

    <div class="paragraph-container px-3">
        <h1>Welcome to the Parent Association of Gymnasioum Agiou Athanasiou </h1>
        <p>This is the home page of our website. Here you can find the latest announcements, calendar events, and upcoming events.</p>
    </div>

    <div class="row g-0 px-3">
        <div class="col-12 col-md-4 px-1">
            <div class="block-content">
                <h5>Announcements</h5>
            </div>
        </div>
        <div class="col-12 col-md-4 px-1">
            <div class="block-content" id="calendar-block">
                <h5>Calendar</h5>
            </div>
        </div>
        <div class="col-12 col-md-4 px-1">
            <div class="block-content">
                <h5>Upcoming Events</h5>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../app/includes/footer.php'; ?>
    </body>
</html>