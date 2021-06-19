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


    // mendapatkan url dari laman saat ini
    $urlOfThisPage = get_url_of_this_page();

    // jika sesi admin tidak aktif, mengarahkan ke halaman utama admin.
    if (!isset($_SESSION['admin']) && !$_SESSION['admin']) {
        header("location: ../admin.php?redirect=$urlOfThisPage");
        exit;
    }


    // mendapatkan kode dari pertemuan yang akan diakses
    $kodeMeeting = $_GET['kode'];

    // mengeksekusi query untuk mendapatkan data pertemuan
    $meetingResult = $conn->query("SELECT * FROM Pertemuan WHERE kode='$kodeMeeting'");

    if ($meetingResult->num_rows !== 1) {
        if ($meetingResult->num_rows === 0) {
            // jika data pertemuan tidak ditemukan pada database
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Data pertemuan dengan kode <b>'$kodeMeeting'</b> tidak dapat ditemukan"
            );
        } else {
            // memberikan respon gagal lainnya
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan! <i>$conn->error</i>"
            );
        }
        // mengarahkan kembali ke halaman utama admin
        header("location: ../admin.php");
        exit;
    }

    // mendapatkan data pertemuan
    $pertemuan = $meetingResult->fetch_assoc();
    $waktuAkses = explode(' ', $pertemuan['waktu_akses']);
    $tglAkses = $waktuAkses[0];
    $jamAkses = substr($waktuAkses[1], 0, -3);


    // menangani form hapus pertemuan
    if (isset($_POST['hapus_pertemuan'])) {
        $kodePertemuan = htmlspecialchars($_POST['kode_pertemuan']);
        $kodeKelas = htmlspecialchars($_POST['kode_kelas']);

        $deleteRespons = $conn->query("DELETE FROM Pertemuan WHERE kode='$kodePertemuan'");

        if ($deleteRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Data Pertemuan berhasil dihapus."
            );
            header("location: ./kelas.php?kode=$kodeKelas");
            exit;

        } else {
            // memberikan respon gagal
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat menghapus data.<br><i>$conn->error!</i>"
            );
        }
    }


    // menghandle form ubah data pertemuan
    if (isset($_POST['ubah_pertemuan'])) {
        $nomorPert = htmlspecialchars($_POST['nomor_pert']);
        $topik = htmlspecialchars($_POST['topik']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $waktuAkses = htmlspecialchars($_POST['tgl_akses']).' '.htmlspecialchars($_POST['jam_akses']);

        $updateResult = $conn->query("UPDATE Pertemuan SET nomor_pert='$nomorPert', topik='$topik', deskripsi='$deskripsi', waktu_akses='$waktuAkses' WHERE kode='$kodeMeeting'");

        // memberikan respon berhasil
        if ($updateResult) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Perubahan berhasil dilakukan."
            );
        } else {
            // memberikan respon gagal
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat merubah data.<br><i>$conn->error!</i>"
            );
        }
        // memuat ulang halaman agar perubahan dapat dimunculkan
        header("location: $urlOfThisPage");
        exit;
    }


    // menghandle form upload materi
    if (isset($_POST['upload_materi'])) {
        $judul = htmlspecialchars($_POST['judul']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $jenisMateri = htmlspecialchars($_POST['jenis_materi']);
        $kode = '';

        // mencari kode yang belum terpakai
        do {
            $kode = code_generator(5, 'MTR');
            $checkPK = $conn->query("SELECT * FROM Materi WHERE kode='$kode'");
        } while ($checkPK !== FALSE && $checkPK->num_rows > 0);

        $uploadRespons = FALSE;

        if ($jenisMateri === 'url') {
            $url = htmlspecialchars($_POST['url']);
            $uploadRespons = $conn->query("INSERT INTO Materi (kode, pertemuan, judul, deskripsi, `url`)
                VALUES ('$kode', '$kodeMeeting', '$judul', '$deskripsi', '$url')");

        } elseif ($jenisMateri === 'file') {
            // mendapatkan nama file & ekstensinya
            $breakFileName = break_filename($_FILES['file_materi']);
            $fileName = $breakFileName['name'];
            $fileExt = $breakFileName['ext'];
            $newFileName = $kode.'_'.$fileName.'.'.$fileExt;    // nama file baru
            $fileDestination = '../db/'.$newFileName;           // lokasi tujuan penyimpanan file

            // mengupload file materi
            $fileUpload = upload_file($_FILES['file_materi'], $fileDestination);

            if ($fileUpload['error'] === false) {
                // menginputkan data file yang sudah diupload
                $uploadRespons = $conn->query("INSERT INTO Materi (kode, pertemuan, judul, deskripsi, nama_file, mimetype)
                    VALUES ('$kode', '$kodeMeeting', '$judul', '$deskripsi', '$newFileName', '$mimetype')");

            } else {
                $_SESSION['alert'] = array(
                    'error' => $fileUpload['error'],
                    'message' => $fileUpload['message']
                );
            }
        }
        // memberikan respon berhasil
        if ($uploadRespons) {
            $_SESSION['alert'] = array(
                'error' => false,
                'message' => "File Materi berhasil ditambahkan."
            );
        }
        header("location: $urlOfThisPage");
        exit;
    }


    // menghandle form buat tugas baru
    if (isset($_POST['buat_tugas'])) {
        $judul = htmlspecialchars($_POST['judul']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $tglDeadline = htmlspecialchars($_POST['tgl_deadline']);
        $jamDeadline = htmlspecialchars($_POST['jam_deadline']);
        $deadline = $tglDeadline.' '.$jamDeadline;
        $lampiran = $_FILES['lampiran'];
        $kode = '';

        // mencari kode yang belum terpakai
        do {
            $kode = code_generator(5, 'TGS');
            $checkPK = $conn->query("SELECT * FROM Tugas WHERE kode='$kode'");
        } while ($checkPK !== FALSE && $checkPK->num_rows > 0);

        $uploadRespons = FALSE;

        // mengecek jika tidak ada file lampiran yang diupload
        $emptyFileErrCode = 4;
        if ($lampiran['error'] === $emptyFileErrCode) {
            // mengupload tugas tanpa lampiran
            $uploadRespons = $conn->query("INSERT INTO Tugas (kode, pertemuan, judul, deskripsi, deadline)
                VALUES ('$kode', '$kodeMeeting', '$judul', '$deskripsi', '$deadline')");

        } else {
            // mendapatkan nama file & ekstensinya
            $breakFileName = break_filename($lampiran);
            $fileName = $breakFileName['name'];
            $fileExt = $breakFileName['ext'];
            $newFileName = $kode.'_'.$fileName.'.'.$fileExt;    // nama file baru
            $fileDestination = '../db/'.$newFileName;           // lokasi tujuan penyimpanan file

            // mengupload file lampiran
            $fileUpload = upload_file($lampiran, $fileDestination);

            if ($fileUpload['error'] === false) {
                // menyimpan data tugas baru beserta nama file lampirannya
                $uploadRespons = mysqli_query($conn, "INSERT INTO Tugas (kode, pertemuan, judul, deskripsi, deadline, lampiran, mimetype)
                    VALUES ('$kode', '$kodeMeeting', '$judul', '$deskripsi', '$deadline', '$newFileName', '$mimetype')");

            } else {
                $_SESSION['alert'] = array(
                    'error' => $fileUpload['error'],
                    'message' => $fileUpload['message']
                );
            }
        }
        // memberikan respon berhasil
        if ($uploadRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Tugas baru berhasil dibuat."
            );
        }
        header("location: $urlOfThisPage");
        exit;
    }


    // menghandle form buat ujian baru
    if (isset($_POST['buat_ujian'])) {
        $durasi = htmlspecialchars($_POST['durasi']);
        $catatan = htmlspecialchars($_POST['catatan']);
        $kode = '';

        // mencari kode yang belum terpakai
        do {
            $kode = code_generator(5, 'UJI');
            $checkPK = $conn->query("SELECT * FROM Ujian WHERE kode='$kode'");
        } while ($checkPK !== FALSE && $checkPK->num_rows > 0);

        $insertRespons = $conn->query("INSERT INTO Ujian (kode, pertemuan, durasi, catatan)
            VALUES ('$kode', '$kodeMeeting', '$durasi', '$catatan')");

        if ($insertRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Ujian berhasil dibuat dengan durasi pengerjaan selama $durasi."
            );
        } else {
            // memberikan respon gagal
            $duplicatePKErrCode = 1062;
            if ($conn->errno === $duplicatePKErrCode) {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Kode Ujian <b>$kode</b> sudah terpakai! Harap gunakan kode lain."
                );
            } else {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat membuat ujian.<br><i>$conn->error!</i>"
                );
            }
        }
        header("location: $urlOfThisPage");
        exit;
    }


    // menangani form daftar kehadiran mahasiswa
    if (isset($_POST['simpan_presensi'])) {
        $kodeArr = $_POST['kode'];
        $kehadiranArr = $_POST['kehadiran'];
        $keteranganArr = $_POST['keterangan'];

        $countErrors = 0;
        $i = 0;
        foreach ($kodeArr as $kode) {
            $updateRespons = $conn->query("UPDATE Kehadiran SET hadir=$kehadiranArr[$i], keterangan='$keteranganArr[$i]' WHERE kode='$kode'");
            if ($updateRespons === FALSE) $countErrors++;
            $i++;
        }

        // memberikan respon
        if ($countErrors === 0) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Data Kehadiran berhasil disimpan seluruhnya."
            );
        } else {
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat mengupdate $countErrors baris!"
            );
        }
        header("location: $urlOfThisPage");
        exit;
    }


    // mengeksekusi query untuk mendapatkan data kelas
    $kodeKelas = $pertemuan['kelas'];
    $klsResult = $conn->query("SELECT * FROM Kelas WHERE kode='$kodeKelas'");

    if ($klsResult && $klsResult->num_rows !== 1) {
        if ($klsResult->num_rows === 0) {
            // jika data kelas tidak ditemukan pada database
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Data kelas dengan kode <b>'$kodeKelas'</b> tidak dapat ditemukan"
            );
        } else {
            // memberikan respon gagal lainnya
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan! <i>$conn->error</i>"
            );
        }
        // mengarahkan kembali ke halaman utama admin
        header("location: ../admin.php");
        exit;
    }

    // mendapatkan data kelas
    $kelas = $klsResult->fetch_assoc();

    // mengeksekusi query untuk mendapatkan data mata kuliah dari pertemuan ini
    $kodeMK = $kelas['mata_kuliah'];
    $mkResult = $conn->query("SELECT * FROM Mata_Kuliah WHERE kode='$kodeMK'");

    if ($mkResult && $mkResult->num_rows !== 1) {
        if ($mkResult->num_rows === 0) {
            // jika data mata kuliah tidak ditemukan pada database
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Data mata kuliah yang terkait dengan pertemuan ini tidak dapat ditemukan"
            );
        } else {
            // memberikan respon gagal lainnya
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan! <i>$conn->error</i>"
            );
        }
        // mengarahkan kembali ke halaman utama admin
        header("location: ../admin.php");
        exit;
    }

    // mendapatkan data mata kuliah
    $matkul = $mkResult->fetch_assoc();


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
    <title>Pertemuan <?=$pertemuan['nomor_pert']?> Kelas <?=$kelas['nama']?></title>
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
                <li class="breadcrumb-item"><a href="./matkul.php?kode=<?=$matkul['kode']?>"><?=$matkul['nama']?></a></li>
                <li class="breadcrumb-item"><a href="./kelas.php?kode=<?=$kelas['kode']?>">Kelas <?=$kelas['nama']?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Pertemuan <?=$pertemuan['nomor_pert']?></li>
            </ol>
        </nav>
        <section class="mb-4">
            <h1>Pertemuan <?=$pertemuan['nomor_pert']?></h1>
            <h2><?=$pertemuan['topik']?></h2>
        </section>
        <section class="mb-5">
            <div class="accordion" id="menuPanel">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <span class="material-icons">description</span>
                            <span>Detail Pertemuan</span>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <table class="table">
                                <tr>
                                    <th>Kode Pertemuan</th>
                                    <td><?=$pertemuan['kode']?></td>
                                </tr>
                                <tr>
                                    <th>Pertemuan Ke-</th>
                                    <td><?=$pertemuan['nomor_pert']?></td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td><?=$kelas['nama']?></td>
                                </tr>
                                <tr>
                                    <th>Topik</th>
                                    <td><?=$pertemuan['topik']?></td>
                                </tr>
                                <tr>
                                    <th>Deskripsi</th>
                                    <td><?=$pertemuan['deskripsi']?></td>
                                </tr>
                                <tr>
                                    <th>Waktu Akses</th>
                                    <td><?=$pertemuan['waktu_akses']?></td>
                                </tr>
                            </table>
                            <hr>
                            <form action="./pertemuan.php?kode=<?=$pertemuan['kode']?>" method="post">
                                <input type="hidden" name="kode_pertemuan" value="<?=$pertemuan['kode']?>">
                                <input type="hidden" name="kode_kelas" value="<?=$kelas['kode']?>">
                                <button class="btn btn-danger d-inline-flex align-items-center gap-1" type="submit" name="hapus_pertemuan" onclick="return confirm('Anda yakin ingin menghapus Pertemuan ini beserta seluruh isinya (seperti materi, tugas, dll) ?')">
                                    <span class="material-icons">delete</span>
                                    <span>Hapus Pertemuan & Seluruh Isinya</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                            <span class="material-icons">edit</span>
                            <span>Ubah Pertemuan</span>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <p>Silahkan ubah data di bawah ini jika memang diperlukan perubahan, lalu klik tombol <b>"Simpan Perubahan"</b>.</p>
                            <hr>
                            <form action="./pertemuan.php?kode=<?=$pertemuan['kode']?>" method="post">
                                <article class="mb-3">
                                    <label class="form-label" for="kodePertemuan">Kode Pertemuan :</label>
                                    <input class="form-control" type="text" name="kode" id="kodePertemuan" value="<?=$pertemuan['kode']?>" required disabled>
                                    <div class="form-text">Kode Pertemuan tidak dapat diubah.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="kelas">Kelas :</label>
                                    <input class="form-control" type="text" name="kelas" id="kelas" value="<?=$kelas['nama']?>" required disabled>
                                    <div class="form-text">Data Kelas tidak dapat diubah.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="nomorPert">Nomor Pertemuan :</label>
                                    <input class="form-control" type="number" min="1" name="nomor_pert" id="nomorPert" value="<?=$pertemuan['nomor_pert']?>" required>
                                    <div class="invalid-feedback">Nomor Pertemuan harus diisi dengan angka yang lebih dari 0 (nol).</div>
                                    <div class="form-text">Nomor Pertemuan harus diisi dengan angka.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="topik">Topik :</label>
                                    <input class="form-control" type="text" name="topik" id="topik" value="<?=$pertemuan['topik']?>" required>
                                    <div class="invalid-feedback">Topik harus tidak boleh kosong.</div>
                                    <div class="form-text">Masukkan topik yang akan dibahas pada pertemuan ini.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="deskripsi">Deskripsi (opsional) :</label>
                                    <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="2"><?=$pertemuan['deskripsi']?></textarea>
                                    <div class="form-text">Masukkan deskripsi mengenai topik yang akan dibahas pada pertemuan ini.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="tglAkses">Tanggal Akses :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="date" name="tgl_akses" id="tglAkses" value="<?=$tglAkses?>" required>
                                        <span class="input-group-text" title="Tanggal 28 Mei 2021">Contoh: 05/28/2021</span>
                                    </div>
                                    <div class="form-text">
                                        Pilih tanggal kapan pertemuan ini mulai bisa diakses oleh mahasiswa.<br>
                                        <b>Format penulisan: [Bulan/Tanggal/Tahun].</b>
                                    </div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="jamAkses">Jam Akses :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="time" name="jam_akses" id="jamAkses" value="<?=$jamAkses?>" required>
                                        <span class="input-group-text" title="Pukul 14.30 (Sore)">Contoh: 02:30 PM</span>
                                    </div>
                                    <div class="form-text">
                                        Pilih pukul berapa pertemuan ini mulai bisa diakses oleh mahasiswa.<br>
                                        <b>Format penulisan: [Jam:Menit] diikuti AM atau PM.</b><br>
                                        AM = Pagi, PM = Siang/Sore/Malam.
                                    </div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="ubah_pertemuan" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
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
                            <span class="material-icons">upload_file</span>
                            <span>Upload Materi Baru</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                           <p>Silahkan <b>lengkapi kolom-kolom di bawah ini</b> sesuai petunjuk yang tersedia.</p>
                            <hr>
                            <form action="./pertemuan.php?kode=<?=$pertemuan['kode']?>" method="post" enctype="multipart/form-data">
                                <article class="mb-3">
                                    <label class="form-label" for="judul">Judul :</label>
                                    <input class="form-control" type="text" name="judul" id="judul" required>
                                    <div class="invalid-feedback">Judul harus tidak boleh kosong.</div>
                                    <div class="form-text">Masukkan judul dari materi yang ingin Anda upload.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="deskripsi">Deskripsi (opsional) :</label>
                                    <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="1"></textarea>
                                    <div class="form-text">Masukkan deskripsi mengenai materi yang ingin Anda upload.</div>
                                </article>
                                <article class="mb-3">
                                    <label for="jenisMateri" class="form-label">Jenis Materi :</label>
                                    <select class="form-select" name="jenis_materi" id="jenisMateri" required>
                                        <option value="" selected disabled>-- Pilih Jenis Materi --</option>
                                        <option value="file">File</option>
                                        <option value="url">URL</option>
                                    </select>
                                    <div class="form-text">Pilih salah satu jenis materi yang tersedia.</div>
                                </article>
                                <article class="input-materi mb-3 d-none" data-type="file">
                                    <label for="fileMateri" class="form-label">Upload File :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="file" name="file_materi" id="fileMateri">
                                        <div class="input-group-text" title="Ukuran file maksimal yang diperbolehkan">Maks. 5 MB</div>
                                    </div>
                                    <div class="form-text">
                                        Pilih file materi yang ingin Anda upload.<br>
                                        <b>Ekstensi yang diperbolehkan: 'jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar'</b>
                                    </div>
                                </article>
                                <article class="input-materi mb-3 d-none" data-type="url">
                                    <label class="form-label" for="url">URL atau Link :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="url" name="url" id="url">
                                        <div class="input-group-text" title="Contoh URL atau link">https://url.com/contoh</div>
                                    </div>
                                    <div class="form-text">Masukkan URL / Link materi seperti: link Youtube, dll.</div>
                                </article>

                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="upload_materi" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">upload_file</span>
                                        <span>Upload Materi Baru</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                            <span class="material-icons">add_task</span>
                            <span>Buat Tugas Baru</span>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <p>Silahkan <b>lengkapi kolom-kolom di bawah ini</b> sesuai petunjuk yang tersedia.</p>
                            <hr>
                            <form action="./pertemuan.php?kode=<?=$pertemuan['kode']?>" method="post" enctype="multipart/form-data">
                                <article class="mb-3">
                                    <label class="form-label" for="judul">Judul :</label>
                                    <input class="form-control" type="text" name="judul" id="judul" required>
                                    <div class="invalid-feedback">Judul tidak boleh kosong.</div>
                                    <div class="form-text">Masukkan judul dari tugas yang ingin Anda berikan.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="deskripsi">Deskripsi (opsional) :</label>
                                    <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="1"></textarea>
                                    <div class="form-text">Masukkan deskripsi dari tugas yang ingin Anda berikan.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="tglDeadline">Batas Tanggal Pengumpulan Tugas :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="date" name="tgl_deadline" id="tglDeadline" required>
                                        <span class="input-group-text" title="Tanggal 28 Mei 2021">Contoh: 05/28/2021</span>
                                    </div>
                                    <div class="form-text">
                                        Pilih tanggal kapan batas terakhir pengumpulan tugas bagi mahasiswa.<br>
                                        <b>Format penulisan: [Bulan/Tanggal/Tahun].</b>
                                    </div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="jamDeadline">Batas Waktu Pengumpulan Tugas :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="time" name="jam_deadline" id="jamDeadline" required>
                                        <span class="input-group-text" title="Pukul 14.30 (Sore)">Contoh: 02:30 PM</span>
                                    </div>
                                    <div class="form-text">
                                        Pilih pukul berapa batas terakhir pengumpulan tugas bagi mahasiswa.<br>
                                        <b>Format penulisan: [Jam:Menit] diikuti AM atau PM.</b><br>
                                        AM = Pagi, PM = Siang/Sore/Malam.
                                    </div>
                                </article>
                                <article class="mb-3">
                                    <label for="lampiran" class="form-label">File Lampiran (Opsional) :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="file" name="lampiran" id="lampiran">
                                        <div class="input-group-text" title="Ukuran file maksimal yang diperbolehkan">Maks. 5 MB</div>
                                    </div>
                                    <div class="form-text">
                                        Silahkan upload file terkait instruksi untuk tugas ini jika diperlukan.<br>
                                        <b>Ekstensi yang diperbolehkan: 'jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar'</b>
                                    </div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="buat_tugas" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">add_task</span>
                                        <span>Buat Tugas Baru</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
                            <span class="material-icons">post_add</span>
                            <span>Buat Ujian Baru</span>
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <p>Silahkan <b>lengkapi kolom-kolom di bawah ini</b> sesuai petunjuk yang tersedia.</p>
                            <hr>
                            <form action="./pertemuan.php?kode=<?=$pertemuan['kode']?>" method="post" enctype="multipart/form-data">
                                <article class="mb-3">
                                    <label class="form-label" for="durasi">Durasi Ujian :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="time" name="durasi" id="durasi" min="00:01" max="12:00" required>
                                        <span class="input-group-text" title="Durasi Ujian: 2 Jam 30 Menit">Contoh: 02:30 AM</span>
                                    </div>
                                    <div class="form-text">
                                        Tentukan berapa lama durasi pengerjaan ujian. Maksimal durasi ujian hanya sampai 12 Jam.<br>
                                        <b>Format penulisan: [Jam:Menit] AM</b>
                                    </div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="catatan">Catatan (opsional) :</label>
                                    <input class="form-control" type="text" name="catatan" id="catatan">
                                    <div class="form-text">Masukkan catatan mengenai ujian ini, seperti petunjuk pengerjaan, peraturan, dll.</div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="buat_ujian" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">post_add</span>
                                        <span>Buat Ujian Baru</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="mb-5">
            <h3>Daftar Materi</h3>
            <?php
                // mendapatkan data materi dari pertemuan ini
                $listMateri = call_procedure($conn, "materi_pertemuan('$kodeMeeting')");
                if ($errCode = $conn->errno !== 0) {
                    $_SESSION['alert'] = array(
                        'error' => TRUE,
                        'message' => "Terjadi kesalahan saat mengambil data materi! <i>$conn->error</i> $errCode"
                    );
                }
            ?>
            <?php if (sizeof($listMateri) > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped table-hover">
                        <thead class="text-center">
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Judul</th>
                                <th scope="col">Deskripsi</th>
                                <th scope="col">File / URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listMateri as $materi) : ?>
                                <tr class="" data-link="">
                                    <td><?=$materi['kode']?></td>
                                    <td><?=$materi['judul']?></td>
                                    <td><?=$materi['deskripsi']?></td>
                                    <?php if (!empty($materi['nama_file'])) : ?>
                                        <td><a href="../db/<?=$materi['nama_file']?>" target="_blank"><?=$materi['nama_file']?></a></td>
                                    <?php else : ?>
                                        <td><a href=<?=$materi['url']?>" target="_blank"><?=$materi['url']?></a></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Anda belum memberikan materi apapun untuk pertemuan ini.</p>
            <?php endif; ?>
        </section>
        <section class="mb-5">
            <h3>Daftar Tugas</h3>
            <?php
                // mendapatkan data tugas dari pertemuan ini
                $tugasResult = $conn->query("SELECT * FROM Tugas WHERE pertemuan='$kodeMeeting'");
                if ($errCode = mysqli_errno($conn) !== 0) {
                    $_SESSION['alert'] = array(
                        'error' => TRUE,
                        'message' => "Terjadi kesalahan saat mengambil data tugas! <i>$conn->error</i> $errCode"
                    );
                }
            ?>
            <?php if ($tugasResult && $tugasResult->num_rows > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped table-hover">
                        <thead class="text-center">
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Judul</th>
                                <th scope="col">Deskripsi</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">File / URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tugas = $tugasResult->fetch_assoc()) : ?>
                                <tr class="" data-link="">
                                    <td><?=$tugas['kode']?></td>
                                    <td><?=$tugas['judul']?></td>
                                    <td><?=$tugas['deskripsi']?></td>
                                    <td><?=$tugas['deadline']?></td>
                                    <?php if (!empty($tugas['lampiran'])) : ?>
                                        <td><a href="../db/<?=$tugas['lampiran']?>" target="_blank"><?=$tugas['lampiran']?></a></td>
                                    <?php else : ?>
                                        <td><?=$tugas['lampiran']?></a></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Anda belum memberikan tugas apapun untuk pertemuan ini.</p>
                <?php endif; ?>
            </section>
        <section class="mb-5">
            <h3>Daftar Evaluasi</h3>
            <?php
                // mendapatkan data ujian dari pertemuan ini
                $ujianResult = $conn->query("SELECT * FROM Ujian WHERE pertemuan='$kodeMeeting'");
                if ($errCode = mysqli_errno($conn) !== 0) {
                    $_SESSION['alert'] = array(
                        'error' => TRUE,
                        'message' => "Terjadi kesalahan saat mengambil data ujian! <i>$conn->error</i> $errCode"
                    );
                }
            ?>
            <?php if ($ujianResult && $ujianResult->num_rows > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped table-hover">
                        <thead class="text-center">
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Durasi</th>
                                <th scope="col">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ujian = $ujianResult->fetch_assoc()) : ?>
                                <tr class="ujian" data-link="./ujian.php?kode=<?=$ujian['kode']?>">
                                    <td><?=$ujian['kode']?></td>
                                    <td class="text-center"><?=$ujian['durasi']?></td>
                                    <td><?=$ujian['catatan']?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Anda belum memberikan ujian apapun untuk pertemuan ini.</p>
            <?php endif; ?>
        </section>
        <section class="mb-5">
            <h3>Daftar Kehadiran Mahasiswa</h3>
            <?php
                // mengeksekusi query untuk mendapatkan data kehadiran dari pertemuan ini
                $listPresensi = call_procedure($conn, "absensi_pertemuan('$kodeMeeting')");
                if ($errCode = mysqli_errno($conn) !== 0) {
                    $_SESSION['alert'] = array(
                        'error' => TRUE,
                        'message' => "Terjadi kesalahan saat mengambil data presensi! <i>$conn->error</i> $errCode"
                    );
                }
            ?>
            <?php if (sizeof($listPresensi) > 0) : ?>
                <form action="./pertemuan.php?kode=<?=$kodeMeeting?>" method="post">
                    <div class="responsive-table">
                        <table class="mt-3 table table-bordered table-striped table-hover">
                            <thead class="text-center">
                                <tr>
                                    <th scope="col">NIM</th>
                                    <th scope="col">Nama</th>
                                    <th scope="col">Hadir</th>
                                    <th scope="col">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($listPresensi as $presensi) : ?>
                                <tr data-id="<?=$presensi['kode']?>">
                                        <td><?=$presensi['nim']?></td>
                                        <td><?=$presensi['nama_lengkap']?></td>
                                        <td>
                                            <div class="form-check d-flex justify-content-center align-items-stretch">
                                                <?php if ($presensi['hadir']) : ?>
                                                    <input class="form-check-input trigger-check" type="checkbox" data-id="<?=$presensi['kode']?>" checked>
                                                    <input class="check-value" type="hidden" name="kehadiran[]" id="hdr_<?=$presensi['kode']?>" data-id="<?=$presensi['kode']?>" value="1">
                                                <?php else : ?>
                                                    <input class="form-check-input trigger-check" type="checkbox" data-id="<?=$presensi['kode']?>">
                                                    <input class="check-value" type="hidden" name="kehadiran[]" id="hdr_<?=$presensi['kode']?>" data-id="<?=$presensi['kode']?>" value="0">
                                                <?php endif; ?>
                                                <input type="hidden" name="kode[]" value="<?=$presensi['kode']?>">
                                            </div>
                                        </td>
                                        <td>
                                            <input class="form-control" type="text" name="keterangan[]" id="ktr_<?=$presensi['kode']?>" value="<?=$presensi['keterangan']?>">
                                        </td>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-success" name="simpan_presensi" type="submit">Simpan Presensi</button>
                </form>
            <?php else : ?>
                <p class="p-3 border bg-light">Tidak ditemukan satupun data presensi untuk pertemuan ini.</p>
            <?php endif; ?>
        </section>
        <section>
            <a href="./kelas.php?kode=<?=$kelas['kode']?>" class="mt-3 btn btn-primary d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">arrow_back</span>
                <span>Kembali</span>
            </a>
        </section>
    </main>
    <script>
        const materiTypes = document.querySelectorAll('#jenisMateri option');
        const materiInputs = document.querySelectorAll('.input-materi');

        materiTypes.forEach((type) => {
            type.addEventListener('click', () => {
                materiInputs.forEach((inputEl) => {
                    inputEl.classList.add('d-none');

                    const tipeMateri = inputEl.getAttribute('data-type');

                    if (type.getAttribute('value') === tipeMateri) {
                        const inputField = inputEl.querySelector('input');

                        inputField.setAttribute('required', '');
                        inputEl.classList.remove('d-none');
                    }
                });
            });
        });

        const allTriggerCheck = document.querySelectorAll('.trigger-check');
        const checkValues = document.querySelectorAll('.check-value');

        allTriggerCheck.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                checkValues.forEach((checkValue) => {
                    if (trigger.getAttribute('data-id') == checkValue.getAttribute('data-id')) {
                        if (trigger.checked)
                            checkValue.value = 1;
                        else
                            checkValue.value = 0;
                    }
                });
            });
        });

        const exams = document.querySelectorAll('.ujian');
        exams.forEach((exam) => {
            exam.addEventListener('click', () => {
                window.location.href = exam.getAttribute('data-link');
            })
        });
    </script>
</body>
</html>
