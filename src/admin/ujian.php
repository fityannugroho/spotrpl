<?php
    session_start();

    // mengimport koneksi database ($conn)
    require '../includes/db-connect.php';

    // mengimport user-defined functions
    include '../includes/function.php';

    // memastikan URL valid
    if (!isset($_GET['kode']) || empty($_GET['kode'])) {
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

    // mendapatkan kode dari pertemuan yang akan diakses
    $kodeUjian = $_GET['kode'];

    // mengeksekusi query untuk mendapatkan data ujian
    $ujian = call_procedure($conn, "detail_ujian('$kodeUjian')")[0];

    $durasi = strtotime($ujian['durasi']);
    $durasi = date('G', $durasi).' Jam '.date('i', $durasi).' Menit';


    // menghandle form buat soal baru
    if (isset($_POST['tambah_soal'])) {

        $pertanyaan = htmlspecialchars($_POST['pertanyaan']);
        $poinBenar = htmlspecialchars($_POST['poin_benar']);
        $poinSalah = htmlspecialchars($_POST['poin_salah']);
        $isPG = htmlspecialchars($_POST['is_pg']);
        $fileSoal = $_FILES['file_soal'];
        $opsiBenar = htmlspecialchars($_POST['opsi_benar']);
        $opsiSalah1 = htmlspecialchars($_POST['opsi_salah1']);
        $opsiSalah2 = htmlspecialchars($_POST['opsi_salah2']);
        $opsiSalah3 = htmlspecialchars($_POST['opsi_salah3']);
        $opsiSalah4 = htmlspecialchars($_POST['opsi_salah4']);
        $kodeSoal = '';
        $kodePaket = '';

        // mencari kode soal yang belum terpakai
        do {
            $kodeSoal = code_generator(5, 'SOL');
            $checkPK = mysqli_query($conn, "SELECT * FROM Soal WHERE kode='$kodeSoal'");
        } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);

        // mencari kode paket yang belum terpakai
        do {
            $kodePaket = code_generator(5, 'PKT');
            $checkPK = mysqli_query($conn, "SELECT * FROM Paket_Soal WHERE kode='$kodePaket'");
        } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);


        // mendapatkan nama file & ekstensinya
        $breakFileName = explode('.', $fileSoal['name']);
        $fileName = $breakFileName[0];
        $fileExt = strtolower(end($breakFileName));
        // mendapatkan informasi file lainnya
        $mimetype = $fileSoal['type'];
        $fileSize = $fileSoal['size'];
        $fileTmp = $fileSoal['tmp_name'];
        $fileError = $fileSoal['error'];

        // mengecek jika ada file soal yang diupload
        $emptyFileErrCode = 4;

        if ($fileError !== $emptyFileErrCode) {
            // validasi file soal
            $allowedExt = array('jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar');
            $maxAllowedSize = 5000000; // 5 MB

            // memastikan tidak ada error pada file & file yang diupload sesuai persyaratan
            if ($fileError !== 0 || !in_array($fileExt, $allowedExt) || $fileSize > $maxAllowedSize) {
                // jika file tidak sesuai persyaratan atau terjadi error
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat mengupload file lampiran! Pastikan file lampiran yang akan diupload sudah memenuhi persyaratan."
                );
                header("location: ./ujian.php?kode=$kodeUjian");
                exit;
            }
        }

        $uploadRespons = FALSE;

        // mengecek jenis soal apakah ini, PG atau esai
        if (!$isPG) {
            // jika esai
            if ($fileError === $emptyFileErrCode) {
                // menambahkan soal esai tanpa file lampiran
                $uploadRespons = mysqli_query($conn, "INSERT INTO Soal (kode, pertanyaan, poin_benar, poin_salah)
                    VALUES ('$kodeSoal', '$pertanyaan', $poinBenar, $poinSalah)
                ");

            } else {
                // menambahkan soal esai yang memiliki file lampiran
                $newFileName = $kodeSoal.'_'.$fileName.'.'.$fileExt;        // nama file baru
                $fileDestination = '../db/'.$newFileName;            // lokasi tujuan penyimpanan file

                // mengupload file direktori server & menginsert data materi ke database mysql
                if (move_uploaded_file($fileTmp, $fileDestination)) {

                    // query untuk menyimpan data materi ke database mysql
                    $insertQuery = "INSERT INTO Soal (kode, pertanyaan, poin_benar, poin_salah, nama_file, mimetype)
                        VALUES ('$kodeSoal', '$pertanyaan', $poinBenar, $poinSalah, '$newFileName', '$mimetype')
                    ";

                    $uploadRespons = mysqli_query($conn, $insertQuery);
                }
            }

        } else {
            // jika PG
            if ($fileError === $emptyFileErrCode) {
                // menambahkan soal PG tanpa file lampiran
                $uploadRespons = mysqli_query($conn, "INSERT INTO Soal (kode, pilihan_ganda, pertanyaan, poin_benar, poin_salah)
                    VALUES ('$kodeSoal', $isPG, '$pertanyaan', $poinBenar, $poinSalah)
                ");

            } else {
                // menambahkan soal PG yang memiliki lampiran
                $newFileName = $kodeSoal.'_'.$fileName.'.'.$fileExt;        // nama file baru
                $fileDestination = '../db/'.$newFileName;            // lokasi tujuan penyimpanan file

                // mengupload file direktori server & menginsert data materi ke database mysql
                if (move_uploaded_file($fileTmp, $fileDestination)) {

                    // query untuk menyimpan data materi ke database mysql
                    $insertQuery = "INSERT INTO Soal (kode, pilihan_ganda, pertanyaan, poin_benar, poin_salah, nama_file, mimetype)
                        VALUES ('$kodeSoal', $isPG, '$pertanyaan', $poinBenar, $poinSalah, '$newFileName', '$mimetype')
                    ";

                    $uploadRespons = mysqli_query($conn, $insertQuery);
                }
            }

            // menambahkan opsi PG untuk soal ini
            $kodePG = '';

            // mencari kode PG yang belum terpakai
            do {
                $kodePG = code_generator(5, 'PGS');
                $checkPK = mysqli_query($conn, "SELECT * FROM Opsi_PG WHERE kode='$kodePG'");
            } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);

            $uploadRespons = $uploadRespons && mysqli_query($conn, "INSERT INTO Opsi_PG (kode, soal, opsi_benar, opsi_salah1, opsi_salah2, opsi_salah3, opsi_salah4)
                VALUES ('$kodePG', '$kodeSoal', '$opsiBenar', '$opsiSalah1', '$opsiSalah2', '$opsiSalah3', '$opsiSalah4')
            ");
        }

        // menambahkan data ke tabel paket soal
        $uploadRespons = $uploadRespons && mysqli_query($conn, "INSERT INTO Paket_Soal (kode, ujian, soal)
            VALUES ('$kodePaket', '$kodeUjian', '$kodeSoal')
        ");

        // memberikan respon berhasil
        if ($uploadRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Soal baru berhasil ditambahkan (kode soal: <b>$kodeSoal</b>)."
            );
        } else {
            $errCode = mysqli_errno($conn);
            $errMsg = mysqli_error($conn);
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi Kesalahan! <i>Last Error: $errMsg (Code: $errCode)</i>."
            );
        }

        header("location: ./ujian.php?kode=$kodeUjian");
        exit;
    }

    // mengambil semua soal pg
    $listSoalPG = call_procedure($conn, "ambil_soal_pg('$kodeUjian')");

    if ($errCode = mysqli_errno($conn) !== 0) {
        $errMsg = mysqli_error($conn);
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Terjadi Kesalahan! <i>Last Error: $errMsg (Code: $errCode)</i>."
        );
    }

    // mengambil semua soal esai
    $listSoalEsai = call_procedure($conn, "ambil_soal_esai('$kodeUjian')");

    if ($errCode = mysqli_errno($conn) !== 0) {
        $errMsg = mysqli_error($conn);
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Terjadi Kesalahan! <i>Last Error: $errMsg (Code: $errCode)</i>."
        );
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
    <link rel="shortcut icon" href="../assets/logomark.ico" type="image/x-icon">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap"></noscript>
    <link rel="preload" href="../styles/bootstrap-override.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="../styles/bootstrap-override.css"></noscript>
    <title>Ujian <?=$kodeUjian?></title>
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
                <li class="breadcrumb-item">...</li>
                <li class="breadcrumb-item"><a href="./pertemuan.php?kode=<?=$ujian['kode_prt']?>">Pertemuan <?=$ujian['nomor_prt']?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Ujian <?=$ujian['kode_ujian']?></li>
            </ol>
        </nav>
        <section class="mb-4">
            <h1>Ujian <?=$ujian['kode_ujian']?></h1>
        </section>
        <section class="mb-5">
            <div class="accordion" id="menuPanel">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <span class="material-icons">description</span>
                            <span>Detail Ujian</span>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <table class="table">
                                <tr>
                                    <th>Kode Ujian</th>
                                    <td><?=$ujian['kode_ujian']?></td>
                                </tr>
                                <tr>
                                    <th>Durasi</th>
                                    <td><?=$durasi?></td>
                                </tr>
                                <tr>
                                    <th>Catatan</th>
                                    <td><?=$ujian['catatan']?></td>
                                </tr>
                            </table>
                            <!-- <hr>
                            <form action="./pertemuan.php?kode=<?=$ujian['kode_prt']?>" method="post">
                                <input type="hidden" name="kode_pertemuan" value="<?=$ujian['kode_prt']?>">
                                <input type="hidden" name="kode_kelas" value="<?=$kelas['kode_kls']?>">
                                <button class="btn btn-danger d-inline-flex align-items-center gap-1" type="submit" name="hapus_pertemuan" onclick="return confirm('Anda yakin ingin menghapus Pertemuan ini beserta seluruh isinya (seperti materi, tugas, dll) ?')">
                                    <span class="material-icons">delete</span>
                                    <span>Hapus Pertemuan & Seluruh Isinya</span>
                                </button>
                            </form> -->
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                            <span class="material-icons">help_outline</span>
                            <span>Tambah Soal</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                           <p>Silahkan <b>lengkapi kolom-kolom di bawah ini</b> sesuai petunjuk yang tersedia.</p>
                            <hr>
                            <form action="./ujian.php?kode=<?=$ujian['kode_ujian']?>" method="post" enctype="multipart/form-data">
                                <article class="mb-3">
                                    <label class="form-label" for="pertanyaan">Pertanyaan :</label>
                                    <textarea class="form-control" name="pertanyaan" id="pertanyaan" cols="30" rows="1" required></textarea>
                                    <div class="form-text">Masukkan pertanyaan yang akan ditanyakan.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="poinBenar">Poin Benar :</label>
                                    <input class="form-control" type="number" min="1" name="poin_benar" id="poinBenar" value="1" required>
                                    <div class="invalid-feedback">Poin Benar harus diisi dengan angka yang lebih dari 0 (nol).</div>
                                    <div class="form-text">Masukkan poin yang akan didapat jika menjawab pertanyaan ini dengan benar.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="poinSalah">Poin Salah :</label>
                                    <input class="form-control" type="number" min="0" name="poin_salah" id="poinSalah" value="0" required>
                                    <div class="invalid-feedback">Poin Salah harus diisi dengan angka.</div>
                                    <div class="form-text">Masukkan poin yang akan didapat jika salah menjawab pertanyaan ini.</div>
                                </article>
                                <article class="mb-3">
                                    <label for="jenisSoal" class="form-label">Jenis Soal :</label>
                                    <div id="jenisSoal">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="is_pg" value="1" id="jnsSoalPG">
                                            <label class="form-check-label" for="jnsSoalPG">Pilihan Ganda</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="is_pg" value="0" id="jnsSoalEsai">
                                            <label class="form-check-label" for="jnsSoalEsai">Esai</label>
                                        </div>
                                    </div>
                                </article>
                                <article class="input-jwb mb-3 d-none" data-type="0">
                                    <div class="form-text">Jenis soal esai tidak membutuhkan kunci jawaban.</div>
                                </article>
                                <article class="input-jwb mb-3 d-none" data-type="1">
                                    <label class="form-label mb-0" for="kunciJwb">Opsi Pilihan Ganda :</label>
                                    <p class="form-text mt-0 mb-2">Buat opsi jawaban untuk pertanyaan di atas. Minimal harus ada 1 opsi jawaban salah</p>

                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" checked disabled>
                                        </div>
                                        <input id="opsiBenar" type="text" class="form-control" name="opsi_benar" placeholder="Opsi Benar">
                                    </div>

                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" disabled>
                                        </div>
                                        <input id="opsiSalah" type="text" class="form-control" name="opsi_salah1" placeholder="Opsi Salah 1">
                                    </div>

                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" disabled>
                                        </div>
                                        <input type="text" class="form-control" name="opsi_salah2" placeholder="Opsi Salah 2 (opsional)">
                                    </div>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" disabled>
                                        </div>
                                        <input type="text" class="form-control" name="opsi_salah3" placeholder="Opsi Salah 3 (opsional)">
                                    </div>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" disabled>
                                        </div>
                                        <input type="text" class="form-control" name="opsi_salah4" placeholder="Opsi Salah 4 (opsional)">
                                    </div>
                                </article>
                                <article class="mb-3" data-type="file">
                                    <label for="fileSoal" class="form-label">File Soal (opsional) :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="file" name="file_soal" id="fileSoal">
                                        <div class="input-group-text" title="Ukuran file maksimal yang diperbolehkan">Maks. 5 MB</div>
                                    </div>
                                    <div class="form-text">
                                        Upload file soal seperti gambar atau dokumen jika diperlukan unturk memperjelas soal.<br>
                                        <b>Ekstensi yang diperbolehkan: 'jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar'</b>
                                    </div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button type="submit" name="tambah_soal" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">add</span>
                                        <span>Tambahkan Soal</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="mb-4">
            <h3>Daftar Soal PG</h3>
            <?php if ($listSoalPG && sizeof($listSoalPG) > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped">
                        <thead class="text-center">
                            <tr>
                                <th scope="col" rowspan="2">Kode Soal</th>
                                <th scope="col" rowspan="2">Pertanyaan</th>
                                <th scope="col" colspan="2">Poin</th>
                                <th scope="col" rowspan="2">Opsi Jawaban</th>
                            </tr>
                            <tr>
                                <th>Benar</th>
                                <th>Salah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listSoalPG as $soalPG) : ?>
                                <tr>
                                    <td><?=$soalPG['kode_soal']?></td>
                                    <td>
                                        <p><?=$soalPG['pertanyaan']?></p>
                                        <?php if (!empty($soalPG['nama_file'])) : ?>
                                            <a href="../db/<?=$soalPG['nama_file']?>" target="_blank">Lihat Lampiran</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?=$soalPG['poin_benar']?></td>
                                    <td class="text-center"><?=$soalPG['poin_salah']?></td>
                                    <td>
                                        <ul>
                                            <li><span><b><?=$soalPG['opsi_benar']?></b></span></li>
                                            <li><span><?=$soalPG['opsi_salah1']?></span></li>
                                            <li><span><?=$soalPG['opsi_salah2']?></span></li>
                                            <li><span><?=$soalPG['opsi_salah3']?></span></li>
                                            <li><span><?=$soalPG['opsi_salah4']?></span></li>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Belum ada soal Pilihan Ganda pada ujian ini.</p>
            <?php endif; ?>
        </section>
        <section class="mb-5">
            <h3>Daftar Soal Esai</h3>
            <?php if ($listSoalEsai && sizeof($listSoalEsai) > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped">
                        <thead class="text-center">
                            <tr>
                                <th scope="col" rowspan="2">Kode Soal</th>
                                <th scope="col" rowspan="2">Pertanyaan</th>
                                <th scope="col" colspan="2">Poin</th>
                            </tr>
                            <tr>
                                <th>Benar</th>
                                <th>Salah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listSoalEsai as $soalEsai) : ?>
                                <tr>
                                    <td><?=$soalEsai['kode_soal']?></td>
                                    <td>
                                        <p><?=$soalEsai['pertanyaan']?></p>
                                        <?php if (!empty($soalEsai['nama_file'])) : ?>
                                            <a href="../db/<?=$soalEsai['nama_file']?>" target="_blank">Lihat Lampiran</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?=$soalEsai['poin_benar']?></td>
                                    <td class="text-center"><?=$soalEsai['poin_salah']?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Belum ada soal Esai pada ujian ini.</p>
            <?php endif; ?>
        </section>
        <section class="mb-5">
            <h3>Daftar Nilai Mahasiswa</h3>
            <?php
                // mengeksekusi query untuk mendapatkan data mahasiswa yang terdaftar sebagai peserta ujian
                $listNilaiUjian = call_procedure($conn, "get_exam_rank('$kodeUjian')");

                // mengambil kunci jawaban PG
                $listKeyAnswer = call_procedure($conn, "get_key_answer('$kodeUjian')");
            ?>
            <div class="responsive-table">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="text-center">
                        <tr>
                            <th scope="col">NIM</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Status</th>
                            <th scope="col">Nilai</th>
                            <th scope="col">Rank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listNilaiUjian as $nilaiUjian) : ?>
                            <?php
                                // mengambil list jwb PG mhs
                                $nim = $nilaiUjian['nim'];
                                $listJwbPG = call_procedure($conn, "get_pg_answer('$kodeUjian', '$nim')");
                            ?>
                            <?php if (sizeof($listJwbPG) > 0) : ?>
                                <tr class="jwb-mhs" data-link="./nilai.php?mhs=<?=$nim?>&ujian=<?=$kodeUjian?>">
                                    <td><?=$nilaiUjian['nim']?></td>
                                    <td><?=$nilaiUjian['nama_lengkap']?></td>
                                    <td class="text-center">
                                        <?php if ($nilaiUjian['sudah_dinilai']) : ?>
                                            <p>Sudah Diperiksa</p>
                                        <?php else : ?>
                                            <p>Belum Diperiksa</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?=$nilaiUjian['nilai']?></td>
                                    <td class="text-center"><?=$nilaiUjian['rank']?></td>
                                </tr>
                            <?php else : ?>
                                <tr class="jwb-mhs">
                                    <td><?=$nilaiUjian['nim']?></td>
                                    <td><?=$nilaiUjian['nama_lengkap']?></td>
                                    <td class="text-center">Belum Mengerjakan</td>
                                    <td class="text-center"><?=$nilaiUjian['nilai']?></td>
                                    <td class="text-center"><?=$nilaiUjian['rank']?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section>
            <a href="./pertemuan.php?kode=<?=$ujian['kode_prt']?>" class="mt-3 btn btn-primary d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">arrow_back</span>
                <span>Kembali</span>
            </a>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
    <script>
        const questionTypes = document.querySelectorAll('#jenisSoal input');
        const answerKeyInputs = document.querySelectorAll('.input-jwb');

        questionTypes.forEach((type) => {
            type.addEventListener('input', () => {
                answerKeyInputs.forEach((inputEl) => {
                    inputEl.classList.add('d-none');

                    const isPG = inputEl.getAttribute('data-type');

                    if (type.getAttribute('value') === isPG) {
                        inputEl.classList.remove('d-none');

                        if (isPG == 1) {
                            const opsiBenar = inputEl.querySelector('#opsiBenar');
                            const opsiSalah1 = inputEl.querySelector('#opsiSalah');

                            opsiBenar.setAttribute('required', '');
                            opsiSalah1.setAttribute('required', '');
                        }
                    }
                });
            });
        });

        const answers = document.querySelectorAll('.jwb-mhs');
        answers.forEach((answer) => {
            answer.addEventListener('click', () => {
                if (answer.hasAttribute('data-link')) {
                    window.location.href = answer.getAttribute('data-link');
                }
            })
        });
    </script>
</body>
</html>
