<?php
    session_start();

    // mengimport koneksi database ($conn) dan functions
    require './includes/db-connect.php';
    require './includes/function.php';

    // mengalihkan ke dashboard jika sesi login aktif
    if (isset($_SESSION['login']) && $_SESSION['login']) {
        header('location: dashboard.php');
        exit;
    }

    $urlOfThisPage = get_url_of_this_page();

    // mengirim data form jika tombol submit diklik
    if (isset($_POST['submit'])) {
        $nim = htmlspecialchars($_POST['nim']);
        $fullname = htmlspecialchars($_POST['fullname']);
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
        $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_BCRYPT);   // mengenkripsi password

        // cek validasi nim
        $result = $conn->query("SELECT id FROM Akun WHERE username = '$nim'");

        // jika terdapat MySQL error
        if ($result === false) {
            $_SESSION['alert'] = last_query_error($conn);
            header('location: $urlOfThisPage');
            exit;
        }
        // jika nim sudah terpakai
        if ($result->num_rows > 0) {
            $_SESSION['alert'] = array('error' => true, 'message' => "NIM '$nim' sudah terpakai! Silahkan login menggunakan NIM tersebut.");
            header('location: ./login.php');
            exit;
        }

        $nextSteps = true;

        // menambahkan data akun
        if ($nextSteps) {
            try {
                $idAkun = get_valid_PK($conn, 'Akun', 'id', code_generator(12));
                $nextSteps = query_statement(
                    $conn,
                    "INSERT INTO Akun (`id`, `username`, `password`) VALUES (?, ?, ?)",
                    'sss',
                    $idAkun, $nim, $password
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
                    $conn,
                    "INSERT INTO Alamat (kode, jalan, rt, rw, desa_kel, kec, kab_kota, provinsi, kode_pos, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    'ssiissssidd',
                    $kodeAlamat, $jalan, $rt, $rw, $desaKel, $kec, $kabKota, $provinsi, $kodePos, $latitude, $longitude
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
                    $kodeBiodata, $fullname, $gender, $tmptLahir, $tglLahir, $agama, $kodeAlamat, $telp, $email
                );
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }
        }

        // menambahkan data mahasiswa
        if ($nextSteps) {
            try {
                $nextSteps = query_statement(
                    $conn,
                    "INSERT INTO Mahasiswa (nim, akun, biodata) VALUES (?, ?, ?)",
                    'sss',
                    $nim, $idAkun, $kodeBiodata
                );
            } catch (Exception $ex) {
                $nextSteps = false;
                print_console($ex->__toString(), true);
            }
        }

        // memberikan respons berhasil
        if ($nextSteps) {
            $_SESSION['alert'] = array('error' => true, 'message' => "Pendaftaran berhasil.");
            header('location: ./login.php');
            exit;
        } else {
            $_SESSION['alert'] = array('error' => false, 'message' => "Pendaftaran gagal.");
        }
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
        <p>Silahkan lengkapi kolom-kolom di bawah ini.</p>
        <hr/>
        <form action="" method="POST">
            <section>
                <h2>Biodata</h2>
                <div class="row">
                    <div class="col-25">
                        <label for="nim">Nomor Induk Mahasiswa</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" id="nim" name="nim" placeholder="Contoh: 1234567" required pattern="[0-9]{7}" title="Masukkan 7 digit angka">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="fullname">Nama Lengkap</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" id="fullname" name="fullname" placeholder="Nama Lengkap Anda" required>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="gender">Jenis Kelamin</label>
                    </div>
                    <div class="col-75 field check-inline" id="gender">
                        <div class="input-check">
                            <input type="radio" name="gender" id="genderLk" value="1" required>
                            <label for="genderLk">Laki-Laki</label>
                        </div>
                        <div class="input-check">
                            <input type="radio" name="gender" id="genderPr" value="0">
                            <label for="genderPr">Perempuan</label>
                        </div>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="tmptLahir">Tempat Lahir</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" id="tmptLahir" name="tmpt_lahir" placeholder="Kota Tempat Anda Lahir (Contoh: Jakarta)" required>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="tglLahir">Tanggal Lahir</label>
                    </div>
                    <div class="col-75 field">
                        <input type="date" id="tglLahir" name="tgl_lahir" required>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="agama">Agama</label>
                    </div>
                    <div class="col-75 field">
                        <select name="agama" id="agama" required>
                            <option value="" selected disabled>-- Pilih Agama --</option>
                            <option value="Islam">Islam</option>
                            <option value="Katholik">Katholik</option>
                            <option value="Kristen Protestan">Kristen Protestan</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghuchu">Konghuchu</option>
                        </select>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="telp">Nomor Telepon (opsional)</label>
                    </div>
                    <div class="col-75 field">
                        <input type="tel" id="telp" name="telp" placeholder="Contoh: 08123456789">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="email">Email (opsional)</label>
                    </div>
                    <div class="col-75 field">
                        <input type="email" id="email" name="email" placeholder="Contoh: email@upi.edu">
                        <span class="alert-message"></span>
                    </div>
                </div>
            </section>
            <section>
                <h2>Alamat Rumah</h2>
                <div class="row">
                    <div class="col-25">
                        <label for="provinsi">Provinsi</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" list="daftarProvinsi" name="provinsi" id="provinsi" placeholder="Nama Provinsi" required>
                        <datalist id="daftarProvinsi"></datalist>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="kabKota">Kabupaten / Kota</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" list="daftarKabKota" name="kab_kota" id="kabKota" placeholder="Nama Kabupaten / Kota" required  data-prerequisite="#provinsi">
                        <datalist id="daftarKabKota"></datalist>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="kec">Kecamatan</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" list="daftarKec" name="kec" id="kec" placeholder="Nama Kecamatan" required data-prerequisite="#kabKota">
                        <datalist id="daftarKec"></datalist>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="desaKel">Desa / Kelurahan</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" list="daftarDesaKel" name="desa_kel" id="desaKel" placeholder="Nama Desa / Kelurahan" required data-prerequisite="#kec">
                        <datalist id="daftarDesaKel"></datalist>
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="jalan">Jalan</label>
                    </div>
                    <div class="col-75 field">
                        <input type="text" id="jalan" name="jalan" placeholder="Nama Jalan, Nomor Rumah, Kompleks, dsb" required data-prerequisite="#desaKel">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="rt">RT</label>
                    </div>
                    <div class="col-75 field">
                        <input type="number" id="rt" name="rt" min="1" step="1" placeholder="Nomor RT" required data-prerequisite="#desaKel">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="rw">RW</label>
                    </div>
                    <div class="col-75 field">
                        <input type="number" id="rw" name="rw" min="1" step="1" placeholder="Nomor RW" required data-prerequisite="#desaKel">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="kodePos">Kode Pos</label>
                    </div>
                    <div class="col-75 field">
                        <input type="number" id="kodePos" name="kode_pos" min="1" step="1" placeholder="Kode Pos" required data-prerequisite="#desaKel">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="latitude">Latitude (opsional)</label>
                    </div>
                    <div class="col-75 field">
                        <input type="number" id="latitude" name="latitude" step="0.000001" placeholder="Latitude" data-prerequisite="#desaKel">
                        <span class="alert-message"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-25">
                        <label for="longitude">Longitude (opsional)</label>
                    </div>
                    <div class="col-75 field">
                        <input type="number" id="longitude" name="longitude" step="0.000001" placeholder="Longitude" data-prerequisite="#desaKel">
                        <span class="alert-message"></span>
                    </div>
                </div>
            </section>
            <section>
                <h2>Data Akun</h2>
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
            </section>
            <section>
                <div class="row justify-end">
                    <button id="registerBtn" type="submit" name="submit" class="btn btn-submit" disabled>Daftar</button>
                </div>
            </section>
        </form>
    </div>
    <script src="./script/navbar.js"></script>
    <script src="./script/form-validation.js"></script>
    <script src="./script/list-region.js"></script>
    <script src="./script/register.js"></script>
</body>
</html>
