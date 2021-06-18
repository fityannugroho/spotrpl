<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    // jika sesi login tidak aktif atau user tidak valid
    if (!isset($_SESSION['login']) || !$_SESSION['login'] || !isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // mengarahkan ke halaman login
        $redirectLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        header("location: login.php?redirect=$redirectLink");
        exit;
    }

    // mendapatkan NIM dari user yang sedang login
    $nim = $_SESSION['user']['id'];

    // mengambil daftar kelas yang dikontrak oleh user
    $listKelas = call_procedure($conn, "daftar_kelas_saya('$nim')");

    // memberikan respons jika terjadi error
    if ($codeErr = mysqli_errno($conn) !== 0) {
        $error = mysqli_error($conn);
        echo "<script>alert('ERROR: $error (code: $codeErr)')</script>";
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/logomark.ico" type="image/x-icon">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap"></noscript>
    <link rel="preload" href="./styles/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/style.css"></noscript>
    <link rel="preload" href="./styles/navbar.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/navbar.css"></noscript>
    <link rel="preload" href="./styles/table-responsive.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/table-responsive.css"></noscript>
    <link rel="preload" href="./styles/dashboard.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/dashboard.css"></noscript>
    <title>Dashboard | SPOT RPL</title>
</head>
<body>
    <nav>
        <a href="./index.php" class="logo" title="SPOT RPL">
            <img src="./assets/logomark.png" alt="logo" height="40" role="img">
            <div class="logo-name">
                <span class="name1">SPOT RPL</span>
                <span class="name2">Sistem Pembelajaran Online Terpadu</span>
            </div>
        </a>
        <ul class="main-menus">
            <li><a href="./kontrak-kelas.php">Kontrak Kelas</a></li>
        </ul>
        <div class="right-group">
            <div id="profileToggle" class="profile-toggle">
                <img class="avatar" src="./assets/profile-avatar.png" alt="avatar" title="Lihat Profil">
                <i class="material-icons-outlined">arrow_drop_down</i>
            </div>
            <div class="profile-box">
                <div class="profile-content">
                    <img src="./assets/profile-avatar.png" alt="avatar" width="64">
                    <div class="info">
                        <p><b><?=$_SESSION['user']['name']?></b></p>
                        <p><?=$_SESSION['user']['id']?></p>
                    </div>
                </div>
                <hr>
                <div class="profile-btn">
                    <a href="" id="logoutBtn" type="menu" class="btn secondary-btn">
                        <i class="material-icons-outlined">manage_accounts</i>
                        <span>Atur Profil</span>
                    </a>
                    <a href="logout.php" id="logoutBtn" type="menu" class="btn primary-btn">
                        <i class="material-icons-outlined">logout</i>
                        <span>Keluar</span>
                    </a>
                </div>
            </div>
            <button class="icon-btn burger-icon"><i class="material-icons-outlined">menu</i></button>
        </div>
    </nav>

    <section class="container">
        <h1>Daftar Mata Kuliah</h1>
        <?php if (sizeof($listKelas) > 0) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Kode MK</th>
                        <th>Nama MK</th>
                        <th>SKS</th>
                        <th>Dosen Pengampu 1</th>
                        <th>Dosen Pengampu 2</th>
                        <th>Tahun Akademik</th>
                        <th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listKelas as $kelas) : ?>
                        <tr data-link="./meeting.php?kelas=<?=$kelas['kode_kelas']?>">
                            <td data-label="Kode MK" class="text-center"><?=$kelas['kode_mk']?></td>
                            <td data-label="Nama MK"><?=$kelas['nama_mk']?></td>
                            <td data-label="SKS" class="text-center"><?=$kelas['sks']?></td>
                            <td data-label="Dosen Pengampu 1"><?=$kelas['nama_dosen1']?></td>
                            <td data-label="Dosen Pengampu 2"><?=$kelas['nama_dosen2']?></td>
                            <td data-label="Tahun Akademik" class="text-center"><?=$kelas['thn_mulai']?>/<?=$kelas['thn_selesai']?></td>
                            <td data-label="Kelas" class="text-center"><?=$kelas['nama_kelas']?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Tidak ditemukan satupun data Mata Kuliah yang Anda kontrak.</p>
        <?php endif; ?>
    </section>
    <script src="./script/navbar.js"></script>
    <script src="./script/dashboard.js"></script>
</body>
</html>
