<?php
    session_start();

    // mengimport koneksi database ($conn)
    require '../includes/db-connect.php';

    // mengimport user-defined functions
    include '../includes/function.php';


    // mendapatkan url dari laman saat ini
    $urlOfThisPage = get_url_of_this_page();

    // jika sesi admin tidak aktif, mengarahkan ke halaman utama admin.
    if (!isset($_SESSION['admin']) && !$_SESSION['admin']) {
        header("location: ../admin.php?redirect=$urlOfThisPage");
        exit;
    }

    if (isset($_POST['buat_matkul'])) {
        $kode = htmlspecialchars(strtoupper($_POST['kode']));
        $nama = htmlspecialchars($_POST['nama']);
        $semester = htmlspecialchars($_POST['semester']);
        $sks = htmlspecialchars($_POST['sks']);
        $thnMulai = htmlspecialchars($_POST['thn_mulai']);
        $thnSelesai = htmlspecialchars($_POST['thn_selesai']);
        $jmlPertemuan = (empty($_POST['jml_pertemuan'])) ? 16 : htmlspecialchars($_POST['jml_pertemuan']);
        $dosen1 = htmlspecialchars($_POST['dosen_pengampu1']);
        $dosen2 = (empty($_POST['dosen_pengampu2'])) ? null : htmlspecialchars($_POST['dosen_pengampu2']);

        $stmt = $conn->prepare("INSERT INTO Mata_Kuliah (kode, nama, semester, sks, thn_mulai, thn_selesai, jml_pertemuan, dosen_pengampu1, dosen_pengampu2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssiississ', $kode, $nama, $semester, $sks, $thnMulai, $thnSelesai, $jmlPertemuan, $dosen1, $dosen2);

        if ($stmt->execute()) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Mata Kuliah baru berhasil dibuat"
            );
            // redirect ke main page
            header('location: ../admin.php');
            exit;

        } else {
            // memberikan respon gagal
            $duplicatePKErr = 1062;
            if ($conn->errno === $duplicatePKErr) {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Kode Mata Kuliah <b>'$kode'</b> sudah terpakai! Harap gunakan kode lain."
                );
            } else {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat menambahkan data mata kuliah.<br><i>$conn->error!</i>"
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
    <?php require '../components/head.php'; ?>
    <?php require '../components/head-admin.php'; ?>
    <title>Tambah Mata Kuliah | Admin | SPOT RPL</title>
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
            <h1>Tambah Mata Kuliah</h1>
        </header>
        <form action="./tambah-matkul.php" method="post">
            <article class="mb-3">
                <label class="form-label" for="kodeMataKuliah">Kode Mata Kuliah :</label>
                <input class="form-control" type="text" name="kode" id="kodeMataKuliah" pattern="[A-Z]{2}[0-9]{3}" title="Masukkan 2 huruf kapital diikuti 3 digit angka" required>
                <div class="invalid-feedback">Kode Mata Kuliah tidak sesuai! (Contoh : RL201)</div>
                <div class="form-text">Kode Mata Kuliah terdiri dari 2 huruf kapital diikuti 3 digit angka.</div>
            </article>
            <article class="mb-3">
                <label class="form-label" for="namaMataKuliah">Nama Mata Kuliah :</label>
                <input class="form-control" type="text" name="nama" id="namaMataKuliah" required>
            </article>
            <article class="mb-3">
                <label class="form-label" for="semester">Semester :</label>
                <input class="form-control" type="number" min="1" name="semester" id="semester" required>
                <div class="invalid-feedback">Semester harus diisi dengan angka yang lebih dari 0 (nol).</div>
            </article>
            <article class="mb-3">
                <label class="form-label" for="sks">SKS :</label>
                <input class="form-control" type="number" min="1" name="sks" id="sks" required>
                <div class="invalid-feedback">SKS harus diisi dengan angka yang lebih dari 0 (nol).</div>
            </article>
            <article class="mb-3">
                <label class="form-label" for="thnMulai">Tahun Mulai :</label>
                <input class="form-control" type="number" min="1" name="thn_mulai" id="thnMulai" required>
                <div class="form-text">Tahun Mulai harus diisi dengan 4 digit angka.</div>
            </article>
            <article class="mb-3">
                <label class="form-label" for="thnSelesai">Tahun Selesai :</label>
                <input class="form-control" type="number" min="1" name="thn_selesai" id="thnSelesai" required>
                <div class="form-text">Tahun Mulai harus diisi dengan 4 digit angka.</div>
            </article>
            <article class="mb-3">
                <label class="form-label" for="jmlPertemuan">Jumlah Pertemuan :</label>
                <input class="form-control" type="number" min="1" max="18" name="jml_pertemuan" id="jmlPertemuan">
                <div class="form-text">Berapa banyak alokasi pertemuan maksimal untuk mata kuliah ini. Default: 16 pertemuan</div>
            </article>
            <?php
                // query untuk mendapatkan semua data dosen yang ada
                $dosenResult = $conn->query("SELECT kode, nama FROM Dosen");
                if ($dosenResult === FALSE) {
                    $_SESSION['alert'] = array(
                        'error' => TRUE,
                        'message' => $conn->error
                    );
                } else {
                    // mendapatkan list dosen dari hasil query ke dalam array
                    $listDosen = array();
                    while ($dosen = $dosenResult->fetch_assoc()) {
                        array_push($listDosen, $dosen);
                    }
                }
            ?>
            <article class="mb-3">
                <label class="form-label" for="dosenPengampu1">Dosen Pengampu 1 :</label>
                <select class="form-select" name="dosen_pengampu1" id="dosenPengampu1" required>
                    <option value="" selected disabled>-- Pilih Dosen --</option>
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
                <button id="tambahBrg" type="submit" name="buat_matkul" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1">
                    <span class="material-icons">add</span>
                    <span>Buat Mata Kuliah Baru</span>
                </button>
            </article>
        </form>
        <hr>
        <a href="../admin.php" class="mt-3 btn btn-success d-flex align-items-center justify-content-center gap-1">
            <span class="material-icons">arrow_back</span>
            <span>Kembali</span>
        </a>
    </main>
    <script src="../script/form-validation.js"></script>
</body>
</html>
