<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    $redirect = (isset($_GET['redirect']) && !empty($_GET['redirect'])) ? $_GET['redirect'] : null;
    $urlOfThisPage = get_url_of_this_page();

    // register
    if (isset($_POST['register'])) {
        $username = htmlspecialchars($_POST['username']);
        $name = htmlspecialchars($_POST['name']);
        $rootPassword = htmlspecialchars($_POST['root_password']);
        $password = htmlspecialchars($_POST['password']);
        $cpassword = htmlspecialchars($_POST['cpassword']);

        $trueRoot = ($conn->query("SELECT password FROM Root WHERE id = 1"))->fetch_row()[0];

        if ($password !== $cpassword) {
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Konfirmasi Kata Sandi <b>tidak cocok</b> dengan Kata Sandi yang Anda buat!"
            );

        } elseif (!password_verify($rootPassword, $trueRoot)) {
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Kata Sandi Root Salah!"
            );

        } else {
            $password = password_hash($password, PASSWORD_BCRYPT);    // mengenkripsi password

            // mengirimkan data ke tabel Dosen
            $queryRespons = $conn->query("INSERT INTO Dosen (kode, nama, kata_sandi) VALUES ('$username', '$name', '$password')");

            if ($queryRespons) {
                $_SESSION['alert'] = array('error' => FALSE, 'message' => "Pendaftaran berhasil.");
                header('location: ./admin.php');
                exit;

            } else {
                $duplicatePKErr = 1062;
                $_SESSION['alert'] = ($conn->errno === $duplicatePKErr) ? $_SESSION['alert'] = last_query_error($conn, "Username <b>$username</b> sudah terdaftar. Silahkan login menggunakan Username tersebut.") : last_query_error($conn);
            }
        }

        header("location: $urlOfThisPage");
        exit;
    }


    // login
    if (isset($_POST['login'])) {
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        // mencari data dari tabel Dosen menggunakan Primary Key (PK)
        $queryRespons = $conn->query("SELECT * FROM Dosen WHERE kode='$username'");

        // jika ditemukan data dengan PK yang sesuai
        if ($queryRespons && $queryRespons->num_rows === 1) {
            $userData = $queryRespons->fetch_assoc();

            // verifikasi kata sandi
            if (password_verify($password, $userData['kata_sandi'])) {
                // jika kata sandi terverifikasi, membuat sesi login
                $_SESSION['admin'] = TRUE;
                $_SESSION['credentials'] = array(
                    'id' => $userData['kode'],
                    'name' => $userData['nama']
                );

                // mengarahkan ke halaman tertentu atau ke halaman beranda admin
                if (!empty($redirect)) header("location: $redirect");
                else header('location: admin.php');
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


    // jika sesi admin aktif
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {
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
        <?php if(isset($_GET['action']) && $_GET['action'] === 'register') : ?>
            <header class="mb-4">
                <h1>Daftar | Admin</h1>
                <p>Halaman ini dirancang seolah-olah sebagai halaman dashboard dosen dimana dosen mengelola pertemuan, memberi materi, tugas, dan lainnya.</p>
            </header>
            <form action="" method="post">
                <section class="mb-3">
                    <label for="rootPassword" class="form-label">Kata Sandi Root</label>
                    <input class="form-control" type="password" name="root_password" id="rootPassword" placeholder="Kata Sandi Panel Admin" required>
                    <p class="form-text">Masukkan kata sandi yang sama dengan kata sandi untuk mengakses panel admin.</p>
                </section>
                <section class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Buat Username" pattern="[0-9]{4}" title="Masukkan 4 digit angka" required>
                    <div class="form-text">Username harus terdiri dari 4 karakter angka (Contoh: 1234).</div>
                </section>
                <section class="mb-3">
                    <label for="name" class="form-label">Nama</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Masukkan Nama Anda" required>
                </section>
                <section class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input class="form-control" type="password" name="password" id="password" placeholder="Buat Kata Sandi" required>
                </section>
                <section class="mb-4">
                    <label for="cpassword" class="form-label">Konfirmasi Kata Sandi</label>
                    <input class="form-control" type="password" name="cpassword" id="cpassword" placeholder="Ketik Ulang Kata Sandi" required>
                </section>
                <section class="mb-3 d-flex gap-2">
                    <button id="register" type="submit" name="register" class="btn btn-primary d-flex flex-fill align-items-center justify-content-center gap-2">
                        <span class="material-icons">login</span>
                        <span>Dafter</span>
                    </button>
                </section>
            </form>
            <hr>
            <a href="./admin.php" class="mt-3 btn btn-success d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">arrow_back</span>
                <span>Kembali</span>
            </a>

        <?php elseif (!isset($_SESSION['admin']) || !$_SESSION['admin']) : ?>
            <header class="mb-4">
                <h1>Masuk | Admin</h1>
                <p>Halaman ini dirancang seolah-olah sebagai halaman dashboard dosen dimana dosen mengelola pertemuan, memberi materi, tugas, dan lainnya.</p>
            </header>
            <form action="" method="post">
                <section class="form-floating mb-3">
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan Username" required>
                    <label for="username">Username</label>
                </section>
                <section class="form-floating mb-4">
                    <input class="form-control" type="password" name="password" id="password" placeholder="Masukkan Kata Sandi" required>
                    <label for="password">Kata Sandi</label>
                </section>
                <section class="mb-3 d-flex gap-2">
                    <button id="login" type="submit" name="login" class="btn btn-primary d-flex flex-fill align-items-center justify-content-center gap-2">
                        <span class="material-icons">login</span>
                        <span>Masuk</span>
                    </button>
                </section>
            </form>
            <a href="./admin.php?action=register" class="mt-3 btn btn-secondary d-flex align-items-center justify-content-center gap-2">
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
                <p>Selamat datang, <b><?=$_SESSION['credentials']['name']?></b></p>
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
