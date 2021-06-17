<?php
    session_start();

    // mengecek apakah sesi login aktif
    if (isset($_SESSION['login']) && $_SESSION['login']) {

        // mengarahkan ke halaman dashboard
        header('location: ./dashboard.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/logomark.png" type="image/x-icon">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap"></noscript>
    <link rel="preload" href="./styles/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/style.css"></noscript>
    <link rel="preload" href="./styles/navbar.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/navbar.css"></noscript>
    <link rel="preload" href="./styles/main.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/main.css"></noscript>
    <title>Beranda | SPOT RPL</title>
</head>
<body>
    <nav>
        <a href="index.php" class="logo" title="SPOT RPL">
            <img src="./assets/logomark.png" alt="logo" height="40" role="img">
            <div class="logo-name">
                <span class="name1">SPOT RPL</span>
                <span class="name2">Sistem Pembelajaran Online Terpadu</span>
            </div>
        </a>
        <div class="right-group">
            <a href="./login.php" id="loginBtn" type="menu" class="btn primary-btn">Masuk</a>
            <a href="./register.php" id="regisBtn" type="menu" class="btn secondary-btn">Daftar</a>
        </div>
    </nav>
    <main>
        <section id="banner">
            <img src="./assets/beranda.png" alt="Logo RPL" role="img">
        </section>
    </main>
    <script src="./script/index.js"></script>
    <script src="./script/navbar.js"></script>
</body>
</html>
