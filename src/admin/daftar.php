<?php
    session_start();

    // mengimport koneksi database ($conn)
    require '../includes/db-connect.php';

    // mengimport user-defined functions
    include '../includes/function.php';

    $redirect = (isset($_GET['redirect']) && !empty($_GET['redirect'])) ? $_GET['redirect'] : null;
    $urlOfThisPage = get_url_of_this_page();

    // register
    if (isset($_POST['register'])) {
        $nama = htmlspecialchars($_POST['nama']);
        $gender = htmlspecialchars($_POST['gender']);
        $tmptLahir = htmlspecialchars($_POST['tmpt_lahir']);
        $tglLahir = htmlspecialchars($_POST['tgl_lahir']);
        $agama = htmlspecialchars($_POST['agama']);
        $telp = (!empty($_POST['telp'])) ? htmlspecialchars($_POST['telp']) : null;
        $email = (!empty($_POST['email'])) ? htmlspecialchars($_POST['email']) : null;
        $provinsi = htmlspecialchars($_POST['provinsi']);
        $kabKota = htmlspecialchars($_POST['kab_kota']);
        $kec = htmlspecialchars($_POST['kec']);
        $desaKel = htmlspecialchars($_POST['desa_kel']);
        $jalan = htmlspecialchars($_POST['jalan']);
        $rt = htmlspecialchars($_POST['rt']);
        $rw = htmlspecialchars($_POST['rw']);
        $kodePos = htmlspecialchars($_POST['kode_pos']);
        $latitude = (!empty($_POST['latitude'])) ? htmlspecialchars($_POST['latitude']) : null;
        $longitude = (!empty($_POST['longitude'])) ? htmlspecialchars($_POST['longitude']) : null;

        $rootPassword = htmlspecialchars($_POST['root_password']);
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);
        $cpassword = htmlspecialchars($_POST['cpassword']);

        $nextSteps = true;

        // validasi kecocokan kata sandi dengan konfirmasi kata sandi
        if ($password !== $cpassword) {
            $nextSteps = false;
            $_SESSION['alert'] = array('error' => TRUE, 'message' => "Konfirmasi Kata Sandi <b>tidak cocok</b> dengan Kata Sandi yang Anda buat!");
        }

        // validasi kata sandi root
        if ($nextSteps) {
            try {
                $rootPasswordResult = query_statement($conn, "SELECT `password` FROM Akun WHERE username = ?", 's', 'root');
                if ($rootPasswordResult->num_rows !== 1)
                    throw new Exception('Root Password is not found');
                $trueRoot = $rootPasswordResult->fetch_row()[0];
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }

            if (!password_verify($rootPassword, $trueRoot)) {
                $nextSteps = false;
                $_SESSION['alert'] = array('error' => TRUE, 'message' => "Kata Sandi Root Salah!");
            }
        }

        // validasi ketersediaan username
        if ($nextSteps) {
            $checkUsername = $conn->query("SELECT kode FROM Dosen WHERE kode = '$username'");

            if ($checkUsername === false) {
                $nextSteps = false;
                print_console(last_query_error($conn)['message'], true);

            } elseif ($checkUsername->num_rows !== 0) {
                $nextSteps = false;
                $_SESSION['alert'] = array('error' => TRUE, 'message' => "Username '$username' sudah terdaftar. Silahkan login menggunakan Username tersebut.");
            }
        }

        // menambahkan data akun
        if ($nextSteps) {
            $password = password_hash($password, PASSWORD_BCRYPT);    // mengenkripsi password
            try {
                $idAkun = get_valid_PK($conn, 'Akun', 'id', code_generator(12));
                $nextSteps = query_statement(
                    $conn, "INSERT INTO Akun (`id`, `username`, `password`) VALUES (?, ?, ?)",
                    'sss', $idAkun, $username, $password
                );
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }
        }

        // menambahkan data alamat
        if ($nextSteps) {
            try {
                $kodeAlamat = get_valid_PK($conn, 'Alamat', 'kode', code_generator(9, 'ADR'));
                $nextSteps = query_statement(
                    $conn, "INSERT INTO Alamat (kode, jalan, rt, rw, desa_kel, kec, kab_kota, provinsi, kode_pos, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    'ssiissssidd', $kodeAlamat, $jalan, $rt, $rw, $desaKel, $kec, $kabKota, $provinsi, $kodePos, $latitude, $longitude
                );
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }
        }

        // menambahkan data biodata
        if ($nextSteps) {
            try {
                $kodeBiodata = get_valid_PK($conn, 'Biodata', 'kode',  code_generator(9, 'BIO'));
                $nextSteps = query_statement(
                    $conn,
                    "INSERT INTO Biodata (kode, nama, lk, tmpt_lahir, tgl_lahir, agama, alamat, telp, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    'ssissssss',
                    $kodeBiodata, $nama, $gender, $tmptLahir, $tglLahir, $agama, $kodeAlamat, $telp, $email
                );
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }
        }

        // menambahkan data dosen
        if ($nextSteps) {
            try {
                $nextSteps = query_statement(
                    $conn, "INSERT INTO Dosen (kode, akun, biodata) VALUES (?, ?, ?)",
                    'sss', $username, $idAkun, $kodeBiodata
                );
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }
        }

        // memberikan respons berhasil
        if ($nextSteps) {
            $_SESSION['alert'] = array('error' => false, 'message' => "Pendaftaran berhasil.");
            header('location: ./admin.php');
            exit;
        } else {
            $_SESSION['alert'] = array('error' => true, 'message' => "Pendaftaran gagal.");
        }
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
    <?php require '../components/head.php'; ?>
    <?php require '../components/head-admin.php'; ?>
    <title>Daftar | Admin | SPOT RPL</title>
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
        <header class="mb-4">
            <h1>Daftar | Admin</h1>
            <p>Halaman ini dirancang seolah-olah sebagai halaman dashboard dosen dimana dosen mengelola pertemuan, memberi materi, tugas, dan lainnya.</p>
        </header>
        <form action="" method="post">
            <section class="mb-5">
                <h2>Biodata</h2>
                <hr>
                <article class="mb-3">
                    <label for="nama" class="form-label">Nama</label>
                    <input type="text" name="nama" id="nama" class="form-control" placeholder="Masukkan Nama Anda" required>
                </article>
                <article class="mb-3">
                    <label for="gender">Jenis Kelamin</label>
                    <div class="mt-2" id="gender">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="genderLk" value="1" required>
                            <label class="form-check-label" for="genderLk">Laki-Laki</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="genderPr" value="0">
                            <label for="genderPr">Perempuan</label>
                        </div>
                    </div>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="tmptLahir">Tempat Lahir</label>
                    <input class="form-control" type="text" id="tmptLahir" name="tmpt_lahir" placeholder="Kota Tempat Anda Lahir (Contoh: Jakarta)" required>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="tglLahir">Tanggal Lahir</label>
                    <input class="form-control" type="date" id="tglLahir" name="tgl_lahir" required>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="agama">Agama</label>
                    <select class="form-control" name="agama" id="agama" required>
                        <option value="" selected disabled>-- Pilih Agama --</option>
                        <option value="Islam">Islam</option>
                        <option value="Katholik">Katholik</option>
                        <option value="Kristen Protestan">Kristen Protestan</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghuchu">Konghuchu</option>
                    </select>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="telp">Nomor Telepon (opsional)</label>
                    <input class="form-control" type="tel" id="telp" name="telp" placeholder="Contoh: 08123456789">
                </article>
                <article class="mb-3">
                    <label class="form-label" for="email">Email (opsional)</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="Contoh: email@upi.edu">
                </article>
            </section>
            <section class="mb-5">
                <h2>Alamat Rumah</h2>
                <hr>
                <article class="mb-3">
                    <label class="form-label" for="provinsi">Provinsi</label>
                    <input class="form-control" type="text" list="daftarProvinsi" name="provinsi" id="provinsi" placeholder="Nama Provinsi" required>
                    <datalist id="daftarProvinsi"></datalist>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="kabKota">Kabupaten / Kota</label>
                    <input class="form-control" type="text" list="daftarKabKota" name="kab_kota" id="kabKota" placeholder="Nama Kabupaten / Kota" required  data-prerequisite="#provinsi">
                    <datalist id="daftarKabKota"></datalist>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="kec">Kecamatan</label>
                    <input class="form-control" type="text" list="daftarKec" name="kec" id="kec" placeholder="Nama Kecamatan" required data-prerequisite="#kabKota">
                    <datalist id="daftarKec"></datalist>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="desaKel">Desa / Kelurahan</label>
                    <input class="form-control" type="text" list="daftarDesaKel" name="desa_kel" id="desaKel" placeholder="Nama Desa / Kelurahan" required data-prerequisite="#kec">
                    <datalist id="daftarDesaKel"></datalist>
                </article>
                <article class="mb-3">
                    <label class="form-label" for="jalan">Jalan</label>
                    <input class="form-control" type="text" id="jalan" name="jalan" placeholder="Nama Jalan, Nomor Rumah, Kompleks, dsb" required data-prerequisite="#desaKel">
                </article>
                <article class="mb-3">
                    <label class="form-label" for="rt">RT</label>
                    <input class="form-control" type="number" id="rt" name="rt" min="1" step="1" placeholder="Nomor RT" required data-prerequisite="#desaKel">
                </article>
                <article class="mb-3">
                    <label class="form-label" for="rw">RW</label>
                    <input class="form-control" type="number" id="rw" name="rw" min="1" step="1" placeholder="Nomor RW" required data-prerequisite="#desaKel">
                </article>
                <article class="mb-3">
                    <label class="form-label" for="kodePos">Kode Pos</label>
                    <input class="form-control" type="number" id="kodePos" name="kode_pos" min="1" step="1" placeholder="Kode Pos" required data-prerequisite="#desaKel">
                </article>
                <article class="mb-3">
                    <label class="form-label" for="latitude">Latitude (opsional)</label>
                    <input class="form-control" type="number" id="latitude" name="latitude" step="0.000001" placeholder="Latitude" data-prerequisite="#desaKel">
                </article>
                <article class="mb-3">
                    <label class="form-label" for="longitude">Longitude (opsional)</label>
                    <input class="form-control" type="number" id="longitude" name="longitude" step="0.000001" placeholder="Longitude" data-prerequisite="#desaKel">
                </article>
            </section>
            <section class="mb-5">
                <h2>Data Akun</h2>
                <hr>
                <article class="mb-3">
                    <label for="rootPassword" class="form-label">Kata Sandi Root</label>
                    <input class="form-control" type="password" name="root_password" id="rootPassword" placeholder="Kata Sandi Panel Admin" required>
                    <p class="form-text">Masukkan kata sandi yang sama dengan kata sandi untuk mengakses panel admin.</p>
                </article>
                <article class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Buat Username" pattern="[0-9]{4}" title="Masukkan 4 digit angka" required>
                    <div class="form-text">Username harus terdiri dari 4 karakter angka (Contoh: 1234).</div>
                </article>
                <article class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input class="form-control" type="password" name="password" id="password" placeholder="Buat Kata Sandi" required>
                </article>
                <article class="mb-4">
                    <label for="cpassword" class="form-label">Konfirmasi Kata Sandi</label>
                    <input class="form-control" type="password" name="cpassword" id="cpassword" placeholder="Ketik Ulang Kata Sandi" required>
                </article>
            </section>
            <section class="mb-3 d-flex gap-2">
                <button id="register" type="submit" name="register" class="btn btn-primary d-flex flex-fill align-items-center justify-content-center gap-2">
                    <span class="material-icons">login</span>
                    <span>Daftar</span>
                </button>
            </section>
        </form>
        <a href="../admin.php" class="mt-3 btn btn-success d-flex align-items-center justify-content-center gap-2">
            <span class="material-icons">arrow_back</span>
            <span>Kembali</span>
        </a>
    </main>
    <script src="../script/form-validation.js"></script>
    <script src="../script/list-region.js"></script>
    <script src="../script/print-list-region.js"></script>
</body>
</html>
