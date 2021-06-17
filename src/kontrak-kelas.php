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

    $nim = $_SESSION['user']['id'];

    // mengambil data kelas
    $listKls = call_procedure($conn, "available_class('$nim')");

    if (mysqli_errno($conn) !== 0) {
        $error = mysqli_error($conn);

        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Terjadi kesalahan saat mengambil data pilihan kelas <i>$error</i>"
        );
    }

    // handle form
    if (isset($_POST['kontrak_kls_baru'])) {

        $kodeKelas = htmlspecialchars($_POST['kode_kelas']);
        $kodeKontrak = '';

        // mencari kode yang belum terpakai
        do {
            $kodeKontrak = code_generator(5, 'KKS');
            $checkPK = mysqli_query($conn, "SELECT * FROM Kontrak_Kelas WHERE kode=$kodeKontrak");
        } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);

        // menambahkan data kontrak kuliah
        $insertRespon = mysqli_query($conn, "INSERT INTO Kontrak_Kelas VALUES ('$kodeKontrak', '$nim', '$kodeKelas')");

        if ($insertRespon) {
            header("location: ./dashboard.php");
            exit;

        } else {
            $errCode = mysqli_errno($conn);
            $duplicatePKErrCode = 1062;

            if ($errCode === $duplicatePKErrCode) {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Kode Kontrak <b>$kodeKontrak</b> sudah terpakai! Harap gunakan kode lain."
                );
            } else {
                $error = mysqli_error($conn);

                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Kontrak Kelas gagal!<br><i>$error ($errCode)</i>"
                );
            }
        }
    }


    // mengecek jika ada suatu peringatan (alert)
    $alert = '';

    if (isset($_SESSION['alert']) && $_SESSION['alert']) {
        $alert = array(
            'error' => $_SESSION['alert']['error'],
            'message' => $_SESSION['alert']['message']
        );

        $_SESSION['alert'] = '';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap"></noscript>
    <title>Kontrak Kelas</title>
</head>
<body>
    <?php if ($alert) : ?>
        <?php if($alert['error']) : ?>
            <div class="alert alert-warning alert-dismissible fade show position-absolute top-2 start-50 translate-middle-x" role="alert">
                <?=$alert['message']?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else : ?>
            <div class="alert alert-success alert-dismissible fade show position-absolute top-2 start-50 translate-middle-x" role="alert">
                <?=$alert['message']?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php
        $alert = '';
        endif;
    ?>
    <main class="container-md p-5">
        <header class="mb-4">
            <h1>Kontrak Kelas</h1>
        </header>
        <form action="./kontrak-kelas.php?nim=<?=$nim?>" method="post">
            <article class="mb-3">
                <label class="form-label" for="kodeKelas">Pilih Kelas :</label>
                <select class="form-select" name="kode_kelas" id="kodeKelas" required>
                    <option value="" selected disabled>-- Pilih Kelas --</option>
                    <?php if ($listKls > 0) : ?>
                        <?php foreach ($listKls as $kls) : ?>
                            <option value="<?=$kls['kode_kls']?>">Kelas <?=$kls['nama_kls']?> - <?=$kls['nama_mk']?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="invalid-feedback">Pilih salah satu dari opsi yang tersedia.</div>
            </article>
            <article class="mb-3 d-flex gap-2">
                <button id="tambahBrg" type="submit" name="kontrak_kls_baru" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1">
                    <span class="material-icons">add</span>
                    <span>Kontrak Kelas Baru</span>
                </button>
            </article>
        </form>
        <hr>
        <a href="./dashboard.php" class="mt-3 btn btn-success d-flex align-items-center justify-content-center gap-1">
            <span class="material-icons">arrow_back</span>
            <span>Kembali</span>
        </a>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
</body>
</html>
