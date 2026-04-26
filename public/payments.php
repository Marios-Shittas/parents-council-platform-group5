<?php
// Arxeio: public\payments.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/payments.css">

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
