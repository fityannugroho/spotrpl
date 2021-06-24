<?php
    session_start();

    // mengimport koneksi database ($conn) dan functions
    require './includes/db-connect.php';
    require './includes/function.php';
    require './includes/constants.php';

    $redirect = (isset($_GET['redirect']) && !empty($_GET['redirect'])) ? $_GET['redirect'] : null;

    // mengalihkan ke halaman tertentu jika sesi login aktif
    if (isset($_SESSION['login']) && $_SESSION['login'] && isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        $accType = $_SESSION['user']['type'];

        if (!empty($redirect)) header("location: $redirect");
        elseif ($accType === ACC_MHS) header("location: ./dashboard.php");
        elseif ($accType === ACC_DOSEN) header("location: ./admin.php");
        else header("location: ./index.php");
        exit;
    }

    // mengirim data form jika tombol submit diklik
    if (isset($_POST['submit'])) {
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        $nextStep = true;

        // validasi password
        if ($nextStep) {
            try {
                $credentials = query_statement($conn, "SELECT password FROM Akun WHERE username = ?", 's', $username);

                if (!$credentials) throw new mysqli_sql_exception(last_query_error($conn)['message']);
                if ($credentials->num_rows !== 1) throw new mysqli_sql_exception('Accounts with this username is not found');

                $credentials = $credentials->fetch_assoc();
                if (!password_verify($password, $credentials['password'])) $nextStep = false;

            } catch (Exception $ex) {
                $nextStep = false;
                print_console($ex->__toString(), true);
            }
        }

        // mendapatkan data user
        if ($nextStep) {
            try {
                $accType = query_statement($conn, "SELECT account_type(?)", 's', $username);
                $accType = $accType->fetch_row()[0];

                if ($accType == 0) throw new Exception('User data is not found');

                $verifiedUser = null;
                if ($accType == 1) {
                    $verifiedUser = call_procedure($conn, "get_biodata_mhs('$username')");
                    if (sizeof($verifiedUser) !== 1) throw new mysqli_sql_exception('User data is not found');
                    $verifiedUser = $verifiedUser[0];

                    $_SESSION['user'] = array(
                        'type' => ACC_MHS,
                        'id' => $verifiedUser['nim'],
                        'name' => $verifiedUser['nama']
                    );
                }
                elseif ($accType == 2) {
                    $verifiedUser = call_procedure($conn, "get_biodata_dosen('$username')");
                    if (sizeof($verifiedUser) !== 1) throw new mysqli_sql_exception('User data is not found');
                    $verifiedUser = $verifiedUser[0];

                    $_SESSION['user'] = array(
                        'type' => ACC_DOSEN,
                        'id' => $verifiedUser['kode_dosen'],
                        'name' => $verifiedUser['nama']
                    );
                }

                // login berhasil, membuat sesi login
                $_SESSION['login'] = TRUE;

                // mengarahkan ke halaman tertentu atau ke halaman beranda admin
                if (!empty($redirect)) header("location: $redirect");
                elseif ($accType == 1) header("location: ./dashboard.php");
                elseif ($accType == 2) header("location: ./admin.php");
                exit;

            } catch (Exception $ex) {
                $nextStep = false;
                print_console($ex->__toString(), true);
            }
        }

        if (!$nextStep) {
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Login gagal! Username atau kata sandi tidak sesuai."
            );
        }
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
