<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/register.css">

    <title>Εγγραφή</title>
</head>
<body>

    <!-- Header -->
    <?php include '../app/includes/header.php'; ?>

    <!-- React will render here -->
    <div id="root"></div>

    <!-- Footer -->
    <?php include '../app/includes/footer.php'; ?>

    <!-- React / ReactDOM -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>

    <!-- Babel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>

    <!-- Your React JSX -->
    <script type="text/babel" src="assets/js/register.jsx"></script>

</body>
</html>