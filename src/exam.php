<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    // memastikan URL valid
    if (!isset($_GET['kode']) || empty($_GET['kode'])) {
        // mengarahkan kembali ke halaman utama
        header("location: not-found.php");
        exit;
    }

    // mendapatkan kode ujian yang akan diakses
    $kodeUjian = $_GET['kode'];

    // jika sesi login tidak aktif atau user tidak valid
    if (!isset($_SESSION['login']) || !$_SESSION['login'] || !isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // mengarahkan ke halaman login
        $redirectLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        header("location: login.php?redirect=$redirectLink");
        exit;
    }

    // mendapatkan NIM & nama dari user yang sedang login
    $nim = $_SESSION['user']['id'];
    $nama = $_SESSION['user']['name'];


    // mendapatkan data ujian
    $listUjian = call_procedure($conn, "detail_ujian('$kodeUjian')");

    if (sizeof($listUjian) === 0) {
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Kode Ujian <b>$kodeUjian</b> tidak ditemukan! Pastikan link ujian Anda sudah benar."
        );

        header('location: ./not-found.php');
        exit;
    }

    $ujian = (sizeof($listUjian) === 1) ? $listUjian[0] : $listUjian;

    $durasi = strtotime($ujian['durasi']);
    $durasi = date('G', $durasi).' Jam '.date('i', $durasi).' Menit';


    // mendapatkan data soal
    $listSoalPG = call_procedure($conn, "ambil_soal_pg('$kodeUjian')");
    $listSoalEsai = call_procedure($conn, "ambil_soal_esai('$kodeUjian')");
    $jmlSoalPG = sizeof($listSoalPG);
    $jmlSoalEsai = sizeof($listSoalEsai);
    $jmlSoal = $jmlSoalPG + $jmlSoalEsai;


    if (isset($_POST['start_exam'])) {
        $_SESSION['is_exam'] = $_POST['exam_session_id'];
    }

    if (isset($_POST['finish_exam'])) {
        $_SESSION['is_exam'] = NULL;

        // mendapatkan list kode soal pada ujian ini
        $listKodeSoalPG = array();
        $listKodeSoalEsai = array();

        foreach ($listSoalPG as $soalPG) {
            foreach ($soalPG as $field => $value) {
                if ($field == 'kode_soal') {
                    array_push($listKodeSoalPG, $value);
                }
            }
        }
        foreach ($listSoalEsai as $soalEsai) {
            foreach ($soalEsai as $field => $value) {
                if ($field === 'kode_soal') {
                    array_push($listKodeSoalEsai, $value);
                }
            }
        }

        // mendapatkan list jwb mahasiswa
        $listJwbPG = array();
        $listJwbEsai = array();

        foreach ($_POST as $field => $value) {
            // mendapatkan list jwb PG
            if (in_array($field, $listKodeSoalPG)) {
                array_push($listJwbPG, $value);
            }
            // mendapatkan list jwb Esai
            if (in_array($field, $listKodeSoalEsai)) {
                array_push($listJwbEsai, $value);
            }
        }

        $kodeUjian = $ujian['kode_ujian'];
        $countErr = 0;

        // memasukkan data jawaban PG mahasiswa
        $i = 0;
        while ($i < sizeof($listKodeSoalPG)) {
            $kodeJwb = '';

            // mencari kode yang belum terpakai
            do {
                $kodeJwb = code_generator(5, 'JWB');
                $checkPK = mysqli_query($conn, "SELECT * FROM Jawaban_Ujian WHERE kode='$kodeJwb'");
            } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);


            $insertJwb = mysqli_query($conn, "INSERT INTO Jawaban_Ujian (kode, ujian, mahasiswa, soal, jawaban)
                VALUES ('$kodeJwb', '$kodeUjian', '$nim', '$listKodeSoalPG[$i]', '$listJwbPG[$i]')
            ");
            if (!$insertJwb) $countErr++;
            $i++;
        }

        // memasukkan data jawaban Esai mahasiswa
        $i = 0;
        while ($i < sizeof($listKodeSoalEsai)) {
            $kodeJwb = '';

            // mencari kode yang belum terpakai
            do {
                $kodeJwb = code_generator(5, 'JWB');
                $checkPK = mysqli_query($conn, "SELECT * FROM Jawaban_Ujian WHERE kode='$kodeJwb'");
            } while ($checkPK !== FALSE && mysqli_num_rows($checkPK) > 0);


            $insertJwb = mysqli_query($conn, "INSERT INTO Jawaban_Ujian (kode, ujian, mahasiswa, soal, jawaban)
                VALUES ('$kodeJwb', '$kodeUjian', '$nim', '$listKodeSoalEsai[$i]', '$listJwbEsai[$i]')
            ");
            if (!$insertJwb) $countErr++;
            $i++;
        }

        if ($countErr > 0) {
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat menyimpan $countErr jawaban Anda!."
            );
        }

        // merefesh halaman
        header("location: ./exam.php?kode=$kodeUjian");
        exit;
    }


    // mengecek apakah mhs sudah pernah mengerjakan ujian ini
    $kodeUjian = $ujian['kode_ujian'];
    $hasDoneExamResult = mysqli_query($conn, "SELECT has_done_exam('$kodeUjian', '$nim')");
    $hasDoneExam = mysqli_fetch_row($hasDoneExamResult)[0];


    // mendapatkan nilai ujian jika sudah dinilai
    $hasExamCheckedResult = mysqli_query($conn, "SELECT * FROM Nilai_Ujian WHERE ujian='$kodeUjian' AND mahasiswa='$nim' AND sudah_dinilai=1");
    $hasExamChecked = mysqli_num_rows($hasExamCheckedResult) == 1;
    $examScore = ($hasExamChecked) ? mysqli_fetch_assoc($hasExamCheckedResult) : null;


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
    <link rel="preload" href="./styles/bootstrap-override.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/bootstrap-override.css"></noscript>
    <link rel="preload" href="./styles/exam.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/exam.css"></noscript>
    <title>Ujian P<?=$ujian['nomor_prt']?> <?=$ujian['nama_kls']?> <?=$ujian['nama_mk']?> | SPOT RPL</title>
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
    <main class="container-md p-4">
        <?php if(isset($_SESSION['is_exam']) && $_SESSION['is_exam'] !== NULL) : ?>
            <form action="./exam.php?kode=<?=$ujian['kode_ujian']?>" method="post">
                <span>ID Sesi: <?=$_SESSION['is_exam']?></span>
                <?php if (sizeof($listSoalPG) > 0) : ?>
                    <section class="mb-4">
                        <h1 class="mb-4">Soal Pilihan Ganda</h1>
                        <?php $i = 1; ?>
                        <?php foreach($listSoalPG as $soalPG) : ?>
                            <article class="question border bg-light">
                                <div class="question-header">
                                    <span class="nomor">Nomor <?=$i?></span>
                                </div>
                                <div class="question-body">
                                    <p><?=$soalPG['pertanyaan']?></p>
                                    <?php if (!empty($soalPG['nama_file']) && strpos($soalPG['mimetype'], 'image/') !== FALSE) : ?>
                                        <img src="./db/<?=$soalPG['nama_file']?>" alt="gambar soal" width="100%">
                                    <?php elseif (!empty($soalPG['nama_file'])) : ?>
                                        <a href="./db/<?=$soalPG['nama_file']?>" target="_blank" class="btn btn-success">Lihat File</a>
                                    <?php endif; ?>

                                    <div class="answer-options">
                                        <?php
                                            $optList = array();
                                            foreach ($soalPG as $field => $value) {
                                                if (strpos($field, 'opsi') !== FALSE && !empty($value)) {
                                                    array_push($optList, $value);
                                                }
                                            }
                                            shuffle($optList);
                                        ?>
                                        <?php foreach ($optList as $opt) : ?>
                                            <?php $randomNum = rand()?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="<?=$soalPG['kode_soal']?>" id="<?=$soalPG['kode_pg'].$randomNum?>" value="<?=$opt?>" required>
                                                <label class="form-check-label" for="<?=$soalPG['kode_pg'].$randomNum?>"><?=$opt?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </article>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
                <?php if ($listSoalEsai > 0) : ?>
                    <section class="mb-4">
                        <h1 class="mb-4">Soal Esai</h1>
                        <?php $i = 1; ?>
                        <?php foreach ($listSoalEsai as $soalEsai) : ?>
                            <article class="question border bg-light">
                                <div class="question-header">
                                    <span class="nomor">Nomor <?=$i?></span>
                                </div>
                                <div class="question-body">
                                    <p><?=$soalEsai['pertanyaan']?></p>
                                    <?php if (!empty($soalEsai['nama_file']) && strpos($soalEsai['mimetype'], 'image/') !== FALSE) : ?>
                                        <img src="./db/<?=$soalEsai['nama_file']?>" alt="gambar soal" width="100%">
                                    <?php elseif (!empty($soalEsai['nama_file'])) : ?>
                                        <a href="./db/<?=$soalEsai['nama_file']?>" target="_blank" class="btn btn-success">Lihat File</a>
                                    <?php endif; ?>
                                    <article class="mt-3">
                                        <label class="form-label" for="<?=$soalEsai['kode_soal']?>">Jawaban :</label>
                                        <textarea class="form-control" name="<?=$soalEsai['kode_soal']?>" id="<?=$soalEsai['kode_soal']?>" cols="30" rows="1" required></textarea>
                                    </article>
                                </div>
                            </article>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
                <section class="mb-4 p-4 border bg-light d-flex">
                    <button type="submit" name="finish_exam" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                        <span class="material-icons">check_circle_outline</span>
                        <span>Akhiri Ujian</span>
                    </button>
                </section>
            </form>
        <?php else : ?>
            <header class="mb-4 text-center">
                <h1><b>Ujian Online SPOT RPL</b></h1>
                <h2>Kelas <?=$ujian['nama_kls']?> <?=$ujian['nama_mk']?></h2>
                <h3>Pertemuan Ke-<?=$ujian['nomor_prt']?></h3>
            </header>
            <section class="mb-3 p-2">
                <div class="responsive-table">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>Kode Ujian</th>
                                <td><?=$ujian['kode_ujian']?></td>
                            </tr>
                            <tr>
                                <th>Pertemuan</th>
                                <td><?=$ujian['nomor_prt']?> - <?=$ujian['topik_prt']?></td>
                            </tr>
                            <tr>
                                <th>Kelas</th>
                                <td><?=$ujian['nama_kls']?></td>
                            </tr>
                            <tr>
                                <th>Mata Kuliah</th>
                                <td><?=$ujian['nama_mk']?></td>
                            </tr>
                            <tr>
                                <th>Semester</th>
                                <td><?=$ujian['semester']?></td>
                            </tr>
                            <tr>
                                <th>Dosen Pengampu</th>
                                <td>
                                    <ul>
                                        <li><?=$ujian['nama_dsn1']?></li>
                                        <li><?=$ujian['nama_dsn2']?></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Durasi Ujian</th>
                                <td><?=$durasi?></td>
                            </tr>
                            <tr>
                                <th>Jumlah Soal</th>
                                <td><?=$jmlSoal?> soal (<?=$jmlSoalPG?> soal Pilihan Ganda + <?=$jmlSoalEsai?> soal Esai)</td>
                            </tr>
                            <tr>
                                <th>Instruksi Ujian</th>
                                <td><p><?=$ujian['catatan']?></p></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="mb-2 p-3 border bg-light d-flex flex-column gap-3">
                <?php if (isset($hasExamChecked) && $hasExamChecked) : ?>
                    <p class="text-center">Nilai Ujian Anda : <b><?=$examScore['nilai']?></b></p>
                <?php elseif (isset($hasDoneExam) && $hasDoneExam) : ?>
                    <p>Anda sudah mengerjakan ujian ini. Silahkan tunggu dosen Anda memeriksa jawaban untuk mengetahui nilai ujian Anda.</p>
                <?php else : ?>
                    <div>
                        <h4>Data Diri Anda</h4>
                        <table class="table">
                            <tr>
                                <td>NIM</td>
                                <th><?=$nim?></th>
                            </tr>
                            <tr>
                                <td>Nama</th>
                                <th><?=$nama?></th>
                            </tr>
                            <tr>
                                <td colspan="2">Pastikan data diri yang tercantum di atas sudah benar.</td>
                            </tr>
                        </table>
                        <span></span>
                    </div>
                    <button type="button"  data-bs-toggle="modal" data-bs-target="#startExamConfirmDialog" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1">
                        <span class="material-icons">edit</span>
                        <span>Mulai Ujian</span>
                    </button>
                <?php endif; ?>
                <a href="./meeting.php?kelas=<?=$ujian['kode_kls']?>&menu=Evaluasi" class="btn btn-secondary flex-fill d-flex align-items-center justify-content-center gap-1">
                    <span class="material-icons">arrow_back</span>
                    <span>Kembali</span>
                </a>
            </section>
            <div class="modal fade" id="startExamConfirmDialog" tabindex="-1" aria-labelledby="startExamConfirm" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="startExamConfirm">Mulai Ujian Sekarang?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Setelah Anda memulai ujian, Anda tidak dapat menghentikannya hingga ujian selesai.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                            <form action="./exam.php?kode=<?=$ujian['kode_ujian']?>" method="post">
                                <input type="hidden" name="exam_session_id" value="<?=uniqid();?>">
                                <button type="submit" name="start_exam" data-bs-toggle="modal" data-bs-target="#startExamConfirmDialog" class="btn btn-primary d-flex align-items-center justify-content-center gap-1">
                                    <span>Ya, Mulai Sekarang</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
    <script>
    </script>
</html>
</body>
