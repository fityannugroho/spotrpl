<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    // memastikan URL valid
    if (!isset($_GET['kelas']) || empty($_GET['kelas'])) {
        // mengarahkan kembali ke halaman utama
        header("location: not-found.php");
        exit;
    }

    // mendapatkan kode kelas yang akan diakses
    $kodeKelas = $_GET['kelas'];

    // mendapatkan nomor pertemuan yang hendak diakses (default: 1)
    $noPertemuan = (isset($_GET['pertemuan']) && !empty($_GET['pertemuan'])) ? (int)$_GET['pertemuan'] : 1;

    // daftar menu
    $menus = array('Materi', 'Tugas', 'Evaluasi', 'Monitoring');

    // mendapatkan data menu yang ingin diakses pengguna
    $menu = (isset($_GET['menu']) && !empty($_GET['menu'])) ? $_GET['menu'] : 'Materi';

    // jika sesi login tidak aktif atau user tidak valid
    if (!isset($_SESSION['login']) || !$_SESSION['login'] || !isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // mengarahkan ke halaman login
        $redirectLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        header("location: login.php?redirect=$redirectLink");
        exit;
    }

    // mendapatkan NIM dari user yang sedang login
    $nim = $_SESSION['user']['id'];

    // mendapatkan data pertemuan.
    $pertemuan = call_procedure($conn, "get_meeting('$kodeKelas', $noPertemuan)");

    if (sizeof($pertemuan) !== 1) {
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Kode kelas <b>'$kodeKelas'</b> tidak dapat ditemukan!"
        );

        header("location: not-found.php");
        exit;
    }

    $pertemuan = $pertemuan[0];

    // memastikan jika pertemuan yang dicari ada dan sudah dapat diakses
    if ($pertemuan['is_exist'] && $pertemuan['is_accessible']) {

        // mendapatkan daftar pertemuan yang sudah terbuka (dapat diakses)
        $meetingsOpened = call_procedure($conn, "daftar_pertemuan_dibuka('$kodeKelas')");

    } else {

        if (isset($pertemuan['is_accessible']) && !$pertemuan['is_accessible']) {
            // jika pertemuan belum bisa diakses saat ini
            $waktu_akses = $pertemuan['access_time'];
            $_SESSION['error'] = "Pertemuan ke-$noPertemuan masih terkunci! <br> Anda baru dapat mengaksesnya pada tanggal <b>$waktu_akses</b>";

        } else {
            // jika pertemuan yang dicari tidak ada
            $_SESSION['error'] = "<b>Pertemuan ke-$noPertemuan belum dibuat!</b><br> Silahkan tanyakan kepada dosen Anda.";
        }
    }


    // mendapatkan data rps mata kuliah
    $kodeMK = $pertemuan['kode_mk'];
    $rpsResult = mysqli_query($conn, "SELECT * FROM RPS WHERE mata_kuliah='$kodeMK'");
    $rps = mysqli_fetch_assoc($rpsResult);

    // mendapatkan data silabus mata kuliah
    $silabusResult = mysqli_query($conn, "SELECT * FROM Silabus WHERE mata_kuliah='$kodeMK'");
    $silabus = mysqli_fetch_assoc($silabusResult);

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
    <link rel="preload" href="./styles/meeting.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/meeting.css"></noscript>
    <title><?=$pertemuan['nama_mk']?> <?=$pertemuan['nama_kelas']?> | SPOT RPL</title>
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
            <?php if (!empty($silabus['nama_file'])) : ?>
                <li><a href="./db/<?=$silabus['nama_file']?>" target="_blank">Silabus</a></li>
            <?php else : ?>
                <li><a href="#" target="">Silabus</a></li>
            <?php endif; ?>
            <?php if (!empty($rps['nama_file'])) : ?>
                <li><a href="./db/<?=$rps['nama_file']?>" target="_blank">RPS</a></li>
            <?php else : ?>
                <li><a href="#" target="">RPS</a></li>
            <?php endif; ?>
        </ul>
        <div class="right-group">
            <div id="profileToggle" class="profile-toggle">
                <img class="avatar" src="./assets/profile-avatar.png" alt="avatar" title="Lihat Profil">
                <i class="material-icons-outlined">arrow_drop_down</i>
            </div>
            <div class="profile-box">
                <div class="profile-content">
                    <img src="./assets/profile-avatar.png" alt="avatar">
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
    <?php if (!empty($pertemuan) && $pertemuan['is_exist'] && $pertemuan['is_accessible']) : ?>
        <main>
            <section class="head">
                <h1><?=$pertemuan['kode_mk']?> - <?=$pertemuan['nama_mk']?></h1>
                <?php if (isset($meetingsOpened) && !empty($meetingsOpened)) : ?>
                    <select name="meeting" id="meetingID">
                        <option disabled>-- Pilih Pertemuan --</option>
                        <?php foreach ($meetingsOpened as $meeting) : ?>
                            <?php if ($meeting['nomor_pert'] == $noPertemuan) : ?>
                                <option data-link="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$meeting['nomor_pert']?>" selected>Pertemuan <?=$meeting['nomor_pert']?></option>
                            <?php else : ?>
                                <option data-link="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$meeting['nomor_pert']?>">Pertemuan <?=$meeting['nomor_pert']?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </section>
            <section>
                <div class="jumbotron">
                    <h2>Pertemuan <?=$pertemuan['nomor_pert']?></h2>
                    <h3><?=$pertemuan['topik']?></h3>
                    <span>Waktu akses : <?=$pertemuan['waktu_akses']?></span>
                </div>
                <div class="menus">
                    <ul>
                        <li class="sticky">
                            <a href="./dashboard.php" class="menu icon-btn" title="Ke Dashboard">
                                <i class="material-icons-outlined">arrow_back</i>
                            </a>
                        </li>
                        <?php if (isset($menus) && !empty($menus)) : ?>
                            <?php foreach ($menus as $mn) : ?>
                                <?php if (strtoupper($mn) === strtoupper($menu)) : ?>
                                    <li><a href="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$noPertemuan?>&menu=<?=$mn?>" class="menu active"><?=$mn?></a></li>
                                    <?php else : ?>
                                        <li><a href="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$noPertemuan?>&menu=<?=$mn?>" class="menu"><?=$mn?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>
            <?php if (isset($menu) && $menu === 'Materi') : ?>
                <section class="content" id="materi">
                    <section class="desc">
                        <article>
                            <h3>Deskripsi Pertemuan</h3>
                            <p><?=$pertemuan['deskripsi']?></p>
                        </article>
                    </section>
                    <?php require './includes/materi.php'; ?>
                </section>
            <?php elseif (isset($menu) && $menu === 'Tugas') : ?>
                <section class="content" id="tugas">
                    <?php require './includes/tugas.php'; ?>
                </section>
            <?php elseif (isset($menu) && $menu === 'Evaluasi') : ?>
                <section class="content" id="evaluasi">
                    <?php require './includes/evaluasi.php'; ?>
                </section>
            <?php elseif (isset($menu) && $menu === 'Monitoring') : ?>
                <section class="content" id="monitoring">
                    <?php require './includes/monitoring.php'; ?>
                </section>
            <?php else : ?>
                <section class="content">
                    <b>ERROR 404</b>
                    <p>Menu "<b><?=$menu?></b>" yang ingin anda akses tidak ditemukan!</p>
                </section>
            <?php endif; ?>
        </main>
    <?php else : ?>
        <main>
            <section class="content">
                <article>
                    <h3>Error!</h3>
                    <?php if (isset($_SESSION['error'])) : ?>
                        <p><?=$_SESSION['error']?></p>
                    <?php endif; ?>
                </article>
                <a href="./dashboard.php" id="logoutBtn" class="btn primary-btn">
                    <i class="material-icons-outlined">home</i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </section>
        </main>
    <?php endif; ?>
    <script src="./script/navbar.js"></script>
    <script src="./script/meeting.js"></script>
</body>
</html>
