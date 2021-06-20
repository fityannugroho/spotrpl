<?php
    session_start();

    // mengimport koneksi database ($conn) dan functions
    require './includes/db-connect.php';
    require './includes/function.php';

    // mengalihkan ke dashboard jika sesi login aktif
    if(isset($_SESSION['login']) && $_SESSION['login']) {
        header('location: dashboard.php');
        exit;
    }

    $urlOfThisPage = get_url_of_this_page();

    // mengirim data form jika tombol submit diklik
    if (isset($_POST['submit'])) {
        $fullname = htmlspecialchars($_POST['fullname']);
        $nim = htmlspecialchars($_POST['nim']);
        $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_BCRYPT);    // mengenkripsi password

        // mengirimkan data ke database
        $queryRespons = $conn->query("INSERT INTO Mahasiswa (nim, nama_lengkap, kata_sandi) VALUES ('$nim', '$fullname', '$password')");

        // memberikan respons berhasil
        if ($queryRespons) {
            $_SESSION['alert'] = array('error' => true, 'message' => "Pendaftaran berhasil.");
            header('location: ./login.php');
            exit;
        }

        $duplicatePKErr = 1062;
        $_SESSION['alert'] = ($conn->errno === $duplicatePKErr) ? last_query_error($conn, "NIM $nim sudah terdaftar. Silahkan login menggunakan NIM tersebut.") : last_query_error($conn);

        header("location: $urlOfThisPage");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require './components/head.php'; ?>
    <?php require './components/head-page.php'; ?>
    <link rel="preload" href="./styles/navbar.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/navbar.css"></noscript>
    <link rel="preload" href="./styles/field.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/field.css"></noscript>
    <link rel="preload" href="./styles/register.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/register.css"></noscript>
    <title>Pendaftaran | SPOT RPL</title>
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
        <div class="right-group"></div>
    </nav>
    <div class="container">
        <h1 class="mt-5">Form Pendaftaran</h1>
        <p>Silahkan isi identitas Anda dan buat kata sandi.</p>
        <hr/>
        <form action="" method="POST">
            <div class="row">
                <div class="col-25">
                    <label for="fullname">Nama Lengkap</label>
                </div>
                <div class="col-75 field">
                    <input type="text" id="fullname" name="fullname" placeholder="Masukkan Nama Lengkap" required>
                    <span class="alert-message"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-25">
                    <label for="nim"><abbr title="Nomor Induk Mahasiswa">NIM</abbr></label>
                </div>
                <div class="col-75 field">
                    <input type="text" id="nim" name="nim" placeholder="Masukkan NIM" required>
                    <span class="alert-message"></span>
                </div>
              </div>
            <div class="row">
                <div class="col-25">
                    <label for="password">Kata Sandi</label>
                </div>
                <div class="col-75 field">
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Buat Kata Sandi" required>
                        <button class="icon-btn password-toggle" type="button" title="Tampilkan Kata Sandi">
                            <i class="material-icons-outlined">visibility_off</i>
                        </button>
                    </div>
                    <span class="alert-message"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-25">
                    <label for="cpassword">Konfirmasi Kata Sandi</label>
                </div>
                <div class="col-75 field">
                    <div class="password-field">
                        <input type="password" id="cpassword" name="cpassword" placeholder="Ketik Ulang Kata Sandi" required>
                        <button class="icon-btn password-toggle" type="button" title="Tampilkan Kata Sandi">
                            <i class="material-icons-outlined">visibility_off</i>
                        </button>
                    </div>
                    <span class="alert-message"></span>
                </div>
            </div>
            <div class="row justify-end">
                <button id="registerBtn" type="submit" name="submit" class="btn btn-submit" disabled>Daftar</button>
            </div>
        </form>
    </div>
    <script src="./script/navbar.js"></script>
    <script src="./script/form-validation.js"></script>
    <script src="./script/register.js"></script>
</body>
</html>
