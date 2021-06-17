<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengalihkan ke dashboard jika sesi login aktif
    if(isset($_SESSION['login']) && $_SESSION['login']) {
        header('location: dashboard.php');
        exit;
    }

    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        $redirect = $_GET['redirect'];
    }

    // mengirim data form jika tombol submit diklik
    if (isset($_POST['submit'])) {

        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        // mencari data dari tabel Mahasiswa menggunakan Primary Key (PK)
        $searchQuery = "SELECT * FROM Mahasiswa WHERE nim='$username'";
        $queryRespons = mysqli_query($conn, $searchQuery);

        // jika ditemukan data dengan PK yang sesuai
        if (mysqli_num_rows($queryRespons) === 1) {

            // menyimpan data user ke bentuk array asosiatif
            $userData = mysqli_fetch_assoc($queryRespons);

            // verifikasi kata sandi
            if (password_verify($password, $userData['kata_sandi'])) {

                // jika kata sandi terverifikasi
                // membuat sesi login
                $_SESSION['login'] = TRUE;
                $_SESSION['user'] = array(
                    'id' => $userData['nim'],
                    'name' => $userData['nama_lengkap']
                );

                if (isset($redirect)) {
                    // mengarahkan ke halaman tertentu
                    header("location: $redirect");
                    exit;
                } else {
                    // mengarahkan ke halaman dashboard
                    header('location: dashboard.php');
                    exit;
                }

            } else {

                // jika kata sandi tidak sesuai
                echo "<script>alert('Login gagal! Harap cek kembali Kata Sandi yang Anda masukkan.')</script>";
            }
        } else {

            // jika data tidak ditemukan
            $errCode = mysqli_errno($conn);
            $PKNotFoundCode = 0;

            if ($errCode === $PKNotFoundCode) {
                echo "<script>alert('Login gagal! Username yang Anda masukkan tidak ditemukan.')</script>";
            } else {
                echo "<script>alert('Login gagal! (Kode Error: $errCode)')</script>";
            }
        }
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
    <link rel="preload" href="./styles/field.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/field.css"></noscript>
    <link rel="preload" href="./styles/login.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/login.css"></noscript>
    <title>Masuk | SPOT RPL</title>
</head>
<body class="fullpage">
    <div class="responsive-center-box">
        <section class="desc-box">
            <div class="title">
                <h2>Single Sign On</h2>
                <h3>Universitas Pendidikan Indonesia</h3>
            </div class="title">
            <a href="./index.php" title="Ke Beranda">
                <img src="./assets/login.png" width="195" height="100" alt="ilustrasi spot upi" role="img">
            </a>
        </section>
        <section>
            <form action="" method="POST">
                <ul class="form-fields">
                    <li class="field">
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username" placeholder="Masukkan username" required>
                        <span class="alert-message"></span>
                    </li>
                    <li class="field">
                        <label for="password">Kata Sandi</label>
                        <div class="password-field">
                            <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required>
                            <button class="icon-btn password-toggle" type="button" title="Tampilkan Kata Sandi">
                                <i class="material-icons-outlined">visibility_off</i>
                            </button>
                        </div>
                        <span class="alert-message"></span>
                    </li>
                    <li class="btn-group">
                        <input id="loginBtn" class="btn btn-submit btn-member" type="submit" name="submit" value="Masuk" disabled>
                        <input id="clearBtn" class="btn btn-member" type="reset" value="Hapus">
                    </li>
                </ul>
            </form>
        </section>
    </div>
    <script src="./script/form-validation.js"></script>
    <script src="./script/login.js"></script>
</body>
</html>
