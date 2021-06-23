<?php
    session_start();

    // mengimport koneksi database ($conn) dan functions
    require './includes/db-connect.php';
    require './includes/function.php';

    $redirect = (isset($_GET['redirect']) && !empty($_GET['redirect'])) ? $_GET['redirect'] : null;

    // mengalihkan ke dashboard jika sesi login aktif
    if (isset($_SESSION['login']) && $_SESSION['login']) {
        if (!empty($redirect)) header("location: $redirect");
        else header('location: dashboard.php');
        exit;
    }

    // mengirim data form jika tombol submit diklik
    if (isset($_POST['submit'])) {
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        // mencari data dari tabel Mahasiswa menggunakan Primary Key (PK)
        $queryRespons = $conn->query("SELECT * FROM Akun WHERE username='$username'");

        // jika ditemukan data dengan PK yang sesuai
        if ($queryRespons && $queryRespons->num_rows === 1) {
            $credential = $queryRespons->fetch_assoc();

            // verifikasi kata sandi
            if (password_verify($password, $credential['password'])) {
                $mhsResult = call_procedure($conn, "get_biodata_mhs('$username')");
                $mhs = (sizeof($mhsResult)) ? $mhsResult[0] : null;

                // jika kata sandi terverifikasi, membuat sesi login
                $_SESSION['login'] = TRUE;
                $_SESSION['user'] = array(
                    'id' => $credential['username'],
                    'name' => $mhs['nama']
                );

                // mengarahkan ke halaman tertentu atau ke halaman dashboard
                if (!empty($redirect)) header("location: $redirect");
                else header('location: dashboard.php');
                exit;
            }
        }

        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Login gagal! Username atau kata sandi tidak sesuai."
        );

        // jika terjadi error selain karena username / password yang salah (MySQL Error)
        if (last_query_error($conn)) $_SESSION['alert'] = last_query_error($conn);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require './components/head.php'; ?>
    <?php require './components/head-page.php'; ?>
    <link rel="preload" href="./styles/field.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/field.css"></noscript>
    <link rel="preload" href="./styles/login.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/login.css"></noscript>
    <title>Masuk | SPOT RPL</title>
</head>
<body class="fullpage">
    <?php if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])) : ?>
        <script>alert("<?=$_SESSION['alert']['message']?>")</script>
    <?php
        $_SESSION['alert'] = null;
        endif;
    ?>
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
