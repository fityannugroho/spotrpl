<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    // mendapatkan url dari laman saat ini
    $urlOfThisPage = get_url_of_this_page();

    // mengarahkan ke halaman login jika sesi login tidak aktif atau user tidak valid
    if (!isset($_SESSION['login']) || !$_SESSION['login'] || !isset($_SESSION['user']) || empty($_SESSION['user'])) {
        header("location: login.php?redirect=$urlOfThisPage");
        exit;
    }

    // mendapatkan NIM dari user yang sedang login
    $nim = $_SESSION['user']['id'];

    // mengambil daftar kelas yang dikontrak oleh user
    $listKelas = call_procedure($conn, "daftar_kelas_saya('$nim')");

    // memberikan respons jika terjadi error
    if (last_query_error($conn)) $_SESSION['alert'] = last_query_error($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require './components/head.php'; ?>
    <?php require './components/head-page.php' ?>
    <link rel="preload" href="./styles/table-responsive.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/table-responsive.css"></noscript>
    <link rel="preload" href="./styles/dashboard.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/dashboard.css"></noscript>
    <title>Dashboard | SPOT RPL</title>
</head>
<body>
    <?php if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])) : ?>
        <script>alert("<?=$_SESSION['alert']['message']?>")</script>
    <?php
        $_SESSION['alert'] = null;
        endif;
    ?>
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
        <?php if ($listKelas && sizeof($listKelas) > 0) : ?>
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
