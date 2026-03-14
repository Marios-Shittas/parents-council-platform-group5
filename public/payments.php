<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/user_css/payments.css">

    <title>Αγορές</title>
</head>
<body>
    <!-- Header -->
    <?php include '../app/includes/header.php'; ?>

    <!-- React will render here -->
    <div id="payments"></div>

    <!-- Footer -->
    <?php include '../app/includes/footer.php'; ?>

    <!-- React / ReactDOM -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Your React JSX -->
    <script type="text/babel" src="assets/js/payments.jsx"></script>

</body>
</html>