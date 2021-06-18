<?php
    session_start();

    // mengimport koneksi database ($conn)
    require '../includes/db-connect.php';

    // mengimport user-defined functions
    include '../includes/function.php';

    // memastikan URL valid
    if (!isset($_GET['kode']) || empty($_GET['kode'])) {
        // jika tidak valid
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "URL tidak valid."
        );

        // mengarahkan kembali ke halaman utama admin
        header("location: ../admin.php");
        exit;
    }

    // jika sesi admin tidak aktif, mengarahkan ke halaman utama admin.
    if (!isset($_SESSION['admin']) && !$_SESSION['admin']) {
        $redirectLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        header("location: ../admin.php?redirect=$redirectLink");
        exit;
    }


    // mendapatkan kode dari mata kuliah yang akan diakses
    $kodeMatkul = $_GET['kode'];

    // mengeksekusi query untuk mendapatkan data mata kuliah
    $matkul = call_procedure($conn, "get_subject('$kodeMatkul')");

    if (sizeof($matkul) === 1) {
        // mendapatkan data mata kuliah
        $matkul = $matkul[0];

    } else {
        if (sizeof($matkul) === 0) {
            // jika data kelas tidak ditemukan pada database
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Mata Kuliah dengan kode <b>$kodeMatkul</b> tidak dapat ditemukan."
            );
        } else {
            // memberikan respon gagal lainnya
            $errMsg = mysqli_error($conn);

            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan! <i>$errMsg</i>"
            );
        }
        // mengarahkan ke halaman utama admin
        header("location: ../admin.php");
        exit;
    }


    // menangani form hapus mata kuliah
    if (isset($_POST['hapus_matkul'])) {

        $kode = htmlspecialchars($_POST['kode']);

        $deleteRespons = mysqli_query($conn, "DELETE FROM Mata_Kuliah WHERE kode='$kode'");

        if ($deleteRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Data Mata Kuliah berhasil dihapus."
            );

            header('location: ../admin.php');
            exit;
        } else {
            // memberikan respon gagal
            $errorMsg = mysqli_error($conn);

            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat menghapus data.<br><i>$errorMsg!</i>"
            );
        }
    }


    // menangani form ubah mata kuliah
    if (isset($_POST['ubah_matkul'])) {
        $kode = htmlspecialchars(strtoupper($matkul['kode']));
        $nama = htmlspecialchars($_POST['nama']);
        $semester = htmlspecialchars($_POST['semester']);
        $sks = htmlspecialchars($_POST['sks']);
        $thnMulai = htmlspecialchars($_POST['thn_mulai']);
        $thnSelesai = htmlspecialchars($_POST['thn_selesai']);
        $jmlPertemuan = (empty($_POST['jml_pertemuan'])) ? 16 : htmlspecialchars($_POST['jml_pertemuan']);
        $kodeDosen1 = htmlspecialchars($_POST['dosen_pengampu1']);
        $kodeDosen2 = (empty($_POST['dosen_pengampu2'])) ? null : htmlspecialchars($_POST['dosen_pengampu2']);

        $stmt = mysqli_prepare($conn, "UPDATE Mata_Kuliah SET nama=?, semester=?, sks=?, thn_mulai=?, thn_selesai=?, jml_pertemuan=?, dosen_pengampu1=?, dosen_pengampu2=?
            WHERE kode=?
        ");
        mysqli_stmt_bind_param($stmt, 'siississs', $nama, $semester, $sks, $thnMulai, $thnSelesai, $jmlPertemuan, $kodeDosen1, $kodeDosen2, $kode);

        // memberikan respon berhasil
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Perubahan berhasil dilakukan."
            );
        } else {
            // memberikan respon gagal
            $errorMsg = mysqli_error($conn);

            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat merubah data.<br><i>$errorMsg!</i>"
            );
        }

        // memuat ulang halaman agar perubahan dapat dimunculkan
        header("location: ./matkul.php?kode=$kode");
        exit;
    }


    // menangani form buat kelas baru
    if (isset($_POST['buat_kelas'])) {

        $nama = $matkul['semester'].htmlspecialchars($_POST['nama']);
        $kapasitas = htmlspecialchars($_POST['kapasitas']);
        $kode = '';

        // mencari kode yang belum terpakai
        do {
            $kode = code_generator(5, 'KLS');
            $checkPK = mysqli_query($conn, "SELECT * FROM Kelas WHERE kode='$kode'");
        } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);


        $insertQuery = "INSERT INTO Kelas (kode, mata_kuliah, nama, kapasitas)
            VALUES ('$kode', '$kodeMatkul', '$nama', $kapasitas)
        ";

        $insertRespons = mysqli_query($conn, $insertQuery);

        // memberikan respon berhasil
        if ($insertRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Kelas baru berhasil ditambahkan."
            );
        } else {
            // memberikan respon gagal
            $errCode = mysqli_errno($conn);
            $errorMsg = mysqli_error($conn);
            $duplicatePKErr = 1062;

            if ($errCode === $duplicatePKErr) {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Kode Kelas <b>'$kode'</b> sudah terpakai! Harap gunakan kode lain."
                );
            } else {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat menambahkan data kelas.<br><i>$errorMsg!</i>"
                );
            }
        }
    }


    // menangani form upload RPS
    if (isset($_POST['upload_rps'])) {

        $kodeMK = htmlspecialchars($_POST['kode_mk']);
        $kodeRPS = 'RPS'.$kodeMK;

        // mengecek jika kode benar-benar belum terpakai
        $isKodeExist = mysqli_query($conn, "SELECT EXISTS(SELECT kode FROM RPS WHERE kode='$kodeRPS') AS is_exists");

        if ($isKodeExist && mysqli_fetch_row($isKodeExist)[0]) {
            // jika kode materi sudah terpakai
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Kode RPS <b>'$kodeRPS'</b> sudah terpakai! Harap gunakan kode lain."
            );

        } else {
            $allowedExt = array('jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar');
            $maxAllowedSize = 5000000; // 5 MB

            // mendapatkan nama file & ekstensinya
            $rps = $_FILES['rps'];
            $breakFileName = explode('.', $rps['name']);
            $fileName = $breakFileName[0];
            $fileExt = strtolower(end($breakFileName));

            // mendapatkan informasi file lainnya
            $mimetype = $rps['type'];
            $fileSize = $rps['size'];
            $fileTmp = $rps['tmp_name'];
            $fileError = $rps['error'];


            // memastikan tidak ada error pada file & file yang diupload sesuai persyaratan
            if ($fileError !== 0 || !in_array($fileExt, $allowedExt) || $fileSize > $maxAllowedSize) {
                // jika file tidak sesuai persyaratan atau terjadi error
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat mengupload file! Pastikan file yang akan diupload sudah memenuhi persyaratan."
                );
            } else {

                $newFileName = $kodeRPS.'_'.$fileName.'.'.$fileExt;        // nama file baru
                $fileDestination = '../db/'.$newFileName;            // lokasi tujuan penyimpanan file

                // mengupload file direktori server & menginsert data materi ke database mysql
                if (move_uploaded_file($fileTmp, $fileDestination)) {
                    // query untuk menyimpan data materi ke database mysql
                    $insertQuery = "INSERT INTO RPS (kode, mata_kuliah, nama_file, mimetype)
                        VALUES ('$kodeRPS', '$kodeMK', '$newFileName', '$mimetype')
                    ";

                    $uploadRespons = mysqli_query($conn, $insertQuery);

                    // memberikan respon berhasil
                    if ($uploadRespons) {
                        $_SESSION['alert'] = array(
                            'error' => FALSE,
                            'message' => "File RPS berhasil ditambahkan."
                        );
                    }
                }
            }
        }
    }


    // menangani form upload silabus
    if (isset($_POST['upload_silabus'])) {

        $kodeMK = htmlspecialchars($_POST['kode_mk']);
        $kodeSilabus = 'SLB'.$kodeMK;

        // mengecek jika kode benar-benar belum terpakai
        $isKodeExist = mysqli_query($conn, "SELECT EXISTS(SELECT kode FROM Silabus WHERE kode='$kodeSilabus') AS is_exists");

        if ($isKodeExist && mysqli_fetch_row($isKodeExist)[0]) {
            // jika kode materi sudah terpakai
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Kode Silabus <b>'$kodeSilabus'</b> sudah terpakai! Harap gunakan kode lain."
            );

        } else {
            $allowedExt = array('jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar');
            $maxAllowedSize = 5000000; // 5 MB

            // mendapatkan nama file & ekstensinya
            $silabus = $_FILES['silabus'];
            $breakFileName = explode('.', $silabus['name']);
            $fileName = $breakFileName[0];
            $fileExt = strtolower(end($breakFileName));

            // mendapatkan informasi file lainnya
            $mimetype = $silabus['type'];
            $fileSize = $silabus['size'];
            $fileTmp = $silabus['tmp_name'];
            $fileError = $silabus['error'];


            // memastikan tidak ada error pada file & file yang diupload sesuai persyaratan
            if ($fileError !== 0 || !in_array($fileExt, $allowedExt) || $fileSize > $maxAllowedSize) {
                // jika file tidak sesuai persyaratan atau terjadi error
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat mengupload file! Pastikan file yang akan diupload sudah memenuhi persyaratan."
                );
            } else {

                $newFileName = $kodeSilabus.'_'.$fileName.'.'.$fileExt;        // nama file baru
                $fileDestination = '../db/'.$newFileName;            // lokasi tujuan penyimpanan file

                // mengupload file direktori server & menginsert data materi ke database mysql
                if (move_uploaded_file($fileTmp, $fileDestination)) {
                    // query untuk menyimpan data materi ke database mysql
                    $insertQuery = "INSERT INTO Silabus (kode, mata_kuliah, nama_file, mimetype)
                        VALUES ('$kodeSilabus', '$kodeMK', '$newFileName', '$mimetype')
                    ";

                    $uploadRespons = mysqli_query($conn, $insertQuery);

                    // memberikan respon berhasil
                    if ($uploadRespons) {
                        $_SESSION['alert'] = array(
                            'error' => FALSE,
                            'message' => "File Silabus berhasil ditambahkan."
                        );
                    }
                }
            }
        }
    }


    // mendapatkan data kelas dari mata kuliah ini
    $klsResult = mysqli_query($conn, "SELECT * FROM Kelas WHERE mata_kuliah='$kodeMatkul' ORDER BY nama ASC");

    if ($klsResult === FALSE) {
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => mysqli_error($conn)
        );
    }

    // query untuk mendapatkan semua data dosen yang ada
    $dosenResult = mysqli_query($conn, "SELECT kode, nama FROM Dosen");

    if ($dosenResult === FALSE) {
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => mysqli_error($conn)
        );
    } else {
        // mendapatkan list dosen dari hasil query ke dalam array
        $listDosen = array();

        while ($dosen = mysqli_fetch_assoc($dosenResult)) {
            array_push($listDosen, $dosen);
        }
    }


    // mendapatkan data rps mata kuliah
    $rpsResult = mysqli_query($conn, "SELECT * FROM RPS WHERE mata_kuliah='$kodeMatkul'");

    // mendapatkan data silabus mata kuliah
    $silabusResult = mysqli_query($conn, "SELECT * FROM Silabus WHERE mata_kuliah='$kodeMatkul'");


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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap"></noscript>
    <link rel="preload" href="../styles/bootstrap-override.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="../styles/bootstrap-override.css"></noscript>
    <title><?=$matkul['nama']?> | Admin | SPOT RPL</title>
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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../admin.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?=$matkul['nama']?></li>
            </ol>
        </nav>
        <section class="mb-4">
            <h1><?=$matkul['kode']?> - <?=$matkul['nama']?></h1>
        </section>
        <section class="mb-4">
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <span class="material-icons">description</span>
                            <span>Detail Mata Kuliah</span>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <table class="table">
                                <tr>
                                    <th>Kode Mata Kuliah</th>
                                    <td><?=$matkul['kode']?></td>
                                </tr>
                                <tr>
                                    <th>Nama Mata Kuliah</th>
                                    <td><?=$matkul['nama']?></td>
                                </tr>
                                <tr>
                                    <th>Semester</th>
                                    <td><?=$matkul['semester']?></td>
                                </tr>
                                <tr>
                                    <th>Bobot SKS</th>
                                    <td><?=$matkul['sks']?></td>
                                </tr>
                                <tr>
                                    <th>Tahun Mulai</th>
                                    <td><?=$matkul['thn_mulai']?></td>
                                </tr>
                                <tr>
                                    <th>Tahun Selesai</th>
                                    <td><?=$matkul['thn_selesai']?></td>
                                </tr>
                                <tr>
                                    <th>Jumlah Pertemuan</th>
                                    <td><?=$matkul['jml_pertemuan']?> pertemuan</td>
                                </tr>
                                <tr>
                                    <th>Dosen Pengampu 1</th>
                                    <td><?=$matkul['nama_dosen1']?></td>
                                </tr>
                                <tr>
                                    <th>Dosen Pengampu 2</th>
                                    <td><?=$matkul['nama_dosen2']?></td>
                                </tr>
                            </table>
                            <hr>
                            <form action="./matkul.php?kode=<?=$matkul['kode']?>" method="post">
                                <input type="hidden" name="kode" value="<?=$matkul['kode']?>">
                                <button class="btn btn-danger d-inline-flex align-items-center gap-1" type="submit" name="hapus_matkul" onclick="return confirm('Anda yakin ingin menghapus Mata Kuliah ini beserta seluruh isinya (seperti kelas, pertemuan, dll) ?')">
                                    <span class="material-icons">delete</span>
                                    <span>Hapus Mata Kuliah & Seluruh Isinya</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <span class="material-icons">edit</span>
                            <span>Ubah Mata Kuliah</span>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Silahkan ubah data di bawah ini jika memang diperlukan perubahan, lalu klik tombol <b>"Simpan Perubahan"</b>.</p>
                            <hr>
                            <form action="./matkul.php?kode=<?=$kodeMatkul?>" method="post">
                                <article class="mb-3">
                                    <label class="form-label" for="kodeMataKuliah">Kode Mata Kuliah :</label>
                                    <input class="form-control" type="text" name="kode" id="kodeMataKuliah" value="<?=$matkul['kode']?>" required disabled>
                                    <div class="invalid-feedback">Kode Mata Kuliah tidak sesuai! (Contoh : RL201)</div>
                                    <div class="form-text">Kode Mata Kuliah tidak dapat diubah.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="namaMataKuliah">Nama Mata Kuliah :</label>
                                    <input class="form-control" type="text" name="nama" id="namaMataKuliah" value="<?=$matkul['nama']?>" required>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="semester">Semester :</label>
                                    <input class="form-control" type="number" min="1" name="semester" id="semester" value="<?=$matkul['semester']?>" required>
                                    <div class="invalid-feedback">Semester harus diisi dengan angka yang lebih dari 0 (nol).</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="sks">SKS :</label>
                                    <input class="form-control" type="number" min="1" name="sks" id="sks" value="<?=$matkul['sks']?>" required>
                                    <div class="invalid-feedback">SKS harus diisi dengan angka yang lebih dari 0 (nol).</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="thnMulai">Tahun Mulai :</label>
                                    <input class="form-control" type="number" min="1" name="thn_mulai" id="thnMulai" value="<?=$matkul['thn_mulai']?>" required>
                                    <div class="invalid-feedback">Tahun Mulai harus diisi dengan 4 digit angka.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="thnSelesai">Tahun Selesai :</label>
                                    <input class="form-control" type="number" min="1" name="thn_selesai" id="thnSelesai" value="<?=$matkul['thn_selesai']?>" required>
                                    <div class="invalid-feedback">Tahun Selesai harus diisi dengan 4 digit angka.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="jmlPertemuan">Jumlah Pertemuan :</label>
                                    <input class="form-control" type="number" min="1" max="18" name="jml_pertemuan" id="jmlPertemuan" value="<?=$matkul['jml_pertemuan']?>">
                                    <div class="form-text">Berapa banyak alokasi pertemuan maksimal untuk mata kuliah ini. Default: 16 pertemuan</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="dosenPengampu1">Dosen Pengampu 1 :</label>
                                    <select class="form-select" name="dosen_pengampu1" id="dosenPengampu1" required>
                                        <option value="" disabled>-- Pilih Dosen --</option>
                                        <?php if (sizeof($listDosen) > 0) : ?>
                                            <?php foreach ($listDosen as $dosen) : ?>
                                                <?php if ($dosen['kode'] === $matkul['kode_dosen1']) : ?>
                                                    <option value="<?=$dosen['kode']?>" selected><?=$dosen['kode']?> - <?=$dosen['nama']?></option>
                                                <?php else : ?>
                                                    <option value="<?=$dosen['kode']?>"><?=$dosen['kode']?> - <?=$dosen['nama']?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="invalid-feedback">Pilih salah satu dari opsi yang tersedia.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="dosenPengampu2">Dosen Pengampu 2 (opsional) :</label>
                                    <select class="form-select" name="dosen_pengampu2" id="dosenPengampu2">
                                        <option value="" disabled>-- Pilih Dosen --</option>
                                        <option value=""></option>
                                        <?php if (isset($listDosen) && sizeof($listDosen) > 0) : ?>
                                            <?php foreach ($listDosen as $dosen) : ?>
                                                <?php if ($dosen['kode'] === $matkul['kode_dosen2']) : ?>
                                                    <option value="<?=$dosen['kode']?>" selected><?=$dosen['kode']?> - <?=$dosen['nama']?></option>
                                                <?php else : ?>
                                                    <option value="<?=$dosen['kode']?>"><?=$dosen['kode']?> - <?=$dosen['nama']?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="invalid-feedback">Pilih salah satu dari opsi yang tersedia.</div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="ubah_matkul" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">save</span>
                                        <span>Simpan Perubahan</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                            <span class="material-icons">add</span>
                            <span>Buat Kelas Baru</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Silahkan <b>lengkapi kolom-kolom di bawah ini</b> sesuai petunjuk yang tersedia.</p>
                            <hr>
                            <form action="./matkul.php?kode=<?=$kodeMatkul?>" method="post">
                                <article class="mb-3">
                                    <label class="form-label" for="namaKelas">Nama Kelas :</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?=$matkul['semester']?></span>
                                        <input class="form-control" type="text" name="nama" id="namaKelas" pattern="[A-Z]{1}" title="Masukkan 1 karakter huruf kapital" required>
                                    </div>
                                    <div class="form-text">Nama Kelas terdiri dari 2 karakter yang diawali dengan nomor semester. Masukkan 1 karakter huruf (A-Z).</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="kapasitas">Kapasitas :</label>
                                    <input class="form-control" type="number" min="1" name="kapasitas" id="kapasitas" required>
                                    <div class="invalid-feedback">Kapasitas harus diisi dengan angka yang lebih dari 0 (nol).</div>
                                    <div class="form-text">Kapasitas kelas diisi dengan jumlah maksimal mahasiswa yang dapat ditampung.</div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="buat_kelas" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">add</span>
                                        <span>Buat Kelas Baru</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="mb-5">
            <h2>Daftar Kelas</h2>
            <?php if (mysqli_num_rows($klsResult) > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped table-hover">
                        <thead class="text-center">
                            <tr>
                                <th scope="col">Kode Kelas</th>
                                <th scope="col">Nama Kelas</th>
                                <th scope="col">Kapasitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($kelas = mysqli_fetch_assoc($klsResult)) : ?>
                                <tr class="kelas" data-link="./kelas.php?kode=<?=$kelas['kode']?>">
                                    <td><?=$kelas['kode']?></td>
                                    <td><?=$kelas['nama']?></td>
                                    <td><?=$kelas['kapasitas']?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Anda belum membuat kelas apapun untuk mata kuliah ini.</p>
            <?php endif; ?>
        </section>
        <section class="mb-5">
            <h2>RPS</h2>
            <?php if (mysqli_num_rows($rpsResult) > 0) : ?>
                <div class="p-3 border bg-light">
                    <?php while ($rps = mysqli_fetch_assoc($rpsResult)) :?>
                        <a href="../db/<?=$rps['nama_file']?>" target="_blank" class="btn btn-success d-flex align-items-center justify-content-center gap-1">
                            <span class="material-icons">download</span>
                            <span>Unduh file RPS</span>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="p-3 border bg-light">
                    <p><b>Anda belum memasukkan RPS untuk mata kuliah ini.</b> Silahkan upload file RPS di bawah ini.</p>
                    <form action="./matkul.php?kode=<?=$kodeMatkul?>" method="post" enctype="multipart/form-data">
                        <article class="mb-3">
                            <label for="rps" class="form-label">File RPS :</label>
                            <div class="input-group">
                                <input class="form-control" type="file" name="rps" id="rps" required>
                                <input type="hidden" name="kode_mk" value="<?=$kodeMatkul?>" required>
                                <div class="input-group-text" title="Ukuran file maksimal yang diperbolehkan">Maks. 5 MB</div>
                            </div>
                            <div class="form-text">
                                Silahkan upload file terkait instruksi untuk tugas ini jika diperlukan.<br>
                                <b>Ekstensi yang diperbolehkan: 'jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar'</b>
                            </div>
                        </article>
                        <article class="mb-3 d-flex gap-2">
                            <button id="tambahBrg" type="submit" name="upload_rps" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                <span class="material-icons">upload</span>
                                <span>Upload RPS</span>
                            </button>
                        </article>
                    </form>
                </div>
            <?php endif; ?>
        </section>
        <section class="mb-4">
            <h2>Silabus</h2>
            <?php if (mysqli_num_rows($silabusResult) > 0) : ?>
                <div class="p-3 border bg-light">
                    <?php while ($silabus = mysqli_fetch_assoc($silabusResult)) :?>
                        <a href="../db/<?=$silabus['nama_file']?>" target="_blank" class="btn btn-success d-flex align-items-center justify-content-center gap-1">
                            <span class="material-icons">download</span>
                            <span>Unduh file Silabus</span>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="p-3 border bg-light">
                    <p><b>Anda belum memasukkan Silabus untuk mata kuliah ini.</b> Silahkan upload file Silabus di bawah ini.</p>
                    <form action="./matkul.php?kode=<?=$kodeMatkul?>" method="post" enctype="multipart/form-data">
                        <article class="mb-3">
                            <label for="silabus" class="form-label">File Silabus :</label>
                            <div class="input-group">
                                <input class="form-control" type="file" name="silabus" id="silabus" required>
                                <div class="input-group-text" title="Ukuran file maksimal yang diperbolehkan">Maks. 5 MB</div>
                                <input type="hidden" name="kode_mk" value="<?=$kodeMatkul?>" required>
                            </div>
                            <div class="form-text">
                                Silahkan upload file terkait instruksi untuk tugas ini jika diperlukan.<br>
                                <b>Ekstensi yang diperbolehkan: 'jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar'</b>
                            </div>
                        </article>
                        <article class="mb-3 d-flex gap-2">
                            <button id="tambahBrg" type="submit" name="upload_silabus" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                <span class="material-icons">upload</span>
                                <span>Upload Silabus</span>
                            </button>
                        </article>
                    </form>
                </div>
            <?php endif; ?>
        </section>
        <hr>
        <section>
            <a href="../admin.php" class="mt-3 btn btn-primary d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">arrow_back</span>
                <span>Kembali</span>
            </a>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
    <script>
        const kelasRows = document.querySelectorAll('.kelas');
        kelasRows.forEach((row) => {
            row.addEventListener('click', () => {
                window.location.href = row.getAttribute('data-link');
            })
        });
    </script>
</body>
</html>
