<?php
    session_start();

    require './includes/db-connect.php';
    require './includes/constants.php';
    require './includes/function.php';

    $redirect = (isset($_GET['redirect']) && !empty($_GET['redirect'])) ? $_GET['redirect'] : null;
    $urlOfThisPage = get_url_of_this_page();

    // cek tipe akun
    if (isset($_SESSION['login']) && $_SESSION['user']['type'] !== ACC_DOSEN) {
        header('location: ./index.php');
        exit;
    }

    // jika sesi admin aktif
    if (isset($_SESSION['login']) && $_SESSION['login']) {
        // mengambil daftar mata kuliah
        $listMatkul = call_procedure($conn, "daftar_matkul");

        // memberikan respons jika terjadi error
        if (last_query_error($conn)) $_SESSION['alert'] = last_query_error($conn);
    }


    // mengecek jika ada suatu peringatan (alert)
    $alert = '';
    if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])) {
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
    <?php require './components/head.php'; ?>
    <link rel="shortcut icon" href="./assets/logomark.ico" type="image/x-icon">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css"></noscript>
    <link rel="preload" href="./styles/bootstrap-override.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/bootstrap-override.css"></noscript>
    <title>Admin | SPOT RPL</title>
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
    <main class="container-md">
        <?php if (!isset($_SESSION['login']) || !$_SESSION['login']) : ?>
            <header class="mb-4">
                <h1>Masuk | Admin</h1>
                <p>Halaman ini dirancang seolah-olah sebagai halaman dashboard dosen dimana dosen mengelola pertemuan, memberi materi, tugas, dan lainnya.</p>
            </header>
            <a href="./login.php" class="btn btn-primary d-flex flex-fill align-items-center justify-content-center gap-2">
                <span class="material-icons">login</span>
                <span>Masuk</span>
            </a>
            <a href="./admin/daftar.php" class="mt-3 btn btn-secondary d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">person_add</span>
                <span>Daftar</span>
            </a>
            <hr>
            <a href="./index.php" class="mt-3 btn btn-success d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">home</span>
                <span>Ke Beranda</span>
            </a>

        <?php else : ?>
            <header class="mb-5">
                <div class="mb-2 d-flex justify-content-between align-items-center gap-1 flex-wrap">
                    <h1>Halaman Admin</h1>
                    <a href="./logout.php" id="addBrgBtn" class="btn btn-danger d-flex align-items-center gap-1">
                        <span class="material-icons">logout</span>
                        <span>Keluar</span>
                    </a>
                </div>
                <p>Halaman ini dirancang seolah-olah sebagai halaman dashboard dosen dimana dosen mengelola pertemuan, memberi materi, tugas, dan lainnya.</p>
                <p>Selamat datang, <b><?=$_SESSION['user']['name']?></b></p>
            </header>
            <section class="mb-5">
                <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
                    <h2>Daftar Mata Kuliah</h2>
                    <a href="./admin/tambah-matkul.php" title="Buat Mata Kuliah" class="btn btn-success d-inline-flex align-items-center gap-1">
                        <span class="material-icons">add</span>
                    </a>
                </div>
                <?php if ($listMatkul && sizeof($listMatkul) > 0) : ?>
                    <div class="responsive-table">
                        <table class="mt-3 table table-bordered table-striped table-hover">
                            <thead class="text-center">
                                <tr>
                                    <th scope="col">Kode</th>
                                    <th scope="col">Nama</th>
                                    <th scope="col">Semester</th>
                                    <th scope="col">SKS</th>
                                    <th scope="col">Dosen Pengampu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listMatkul as $mataKuliah) : ?>
                                    <tr class="mata-kuliah" data-link="./admin/matkul.php?kode=<?=$mataKuliah['kode']?>">
                                        <td data-label="Kode MK"><?=$mataKuliah['kode']?></td>
                                        <td data-label="Nama MK"><?=$mataKuliah['nama']?></td>
                                        <td class="text-center" data-label="Semester"><?=$mataKuliah['semester']?></td>
                                        <td class="text-center" data-label="SKS"><?=$mataKuliah['sks']?></td>
                                        <td data-label="Dosen Pengampu">
                                            <ul>
                                                <li><?=$mataKuliah['nama_dosen1']?></li>
                                                <li><?=$mataKuliah['nama_dosen2']?></li>
                                            </ul>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <p class="p-3 border bg-light">Tidak ditemukan data mata kuliah apapun.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
    <script>
        const matkulRows = document.querySelectorAll('.mata-kuliah');
        matkulRows.forEach((row) => {
            row.addEventListener('click', () => {
                window.location.href = row.getAttribute('data-link');
            })
        });
    </script>
</body>
</html>
