<?php
    session_start();

    // mengimport koneksi database ($conn)
    require '../includes/db-connect.php';

    // mengimport user-defined functions
    include '../includes/function.php';

    // memastikan URL valid
    if (!isset($_GET['mhs']) || empty($_GET['mhs']) || !isset($_GET['ujian']) || empty($_GET['ujian'])) {
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

    // mendapatkan mhs
    $nim = $_GET['mhs'];

    // mendapatkan kode ujian
    $kodeUjian = $_GET['ujian'];

    // mengambil data jawaban ujian mhs soal PG
    $listJwbPG = call_procedure($conn, "get_pg_answer('$kodeUjian', '$nim')");

    // mengambil data jawaban ujian mhs soal Esai
    $listJwbEsai = call_procedure($conn, "get_esai_answer('$kodeUjian', '$nim')");

    if (sizeof($listJwbPG) === 0) {
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Data jawaban tidak ditemukan."
        );

        // mengarahkan kembali ke halaman utama admin
        header("location: ../admin.php");
        exit;
    }

    // mengambil data mhs
    $mhsResult = mysqli_query($conn, "SELECT nim, nama_lengkap FROM Mahasiswa WHERE nim='$nim'");
    $mhs = mysqli_fetch_assoc($mhsResult);

    // mengambil data soal PG
    $listSoalPG = call_procedure($conn, "ambil_soal_pg('$kodeUjian')");

    // mengambil data soal Esai
    $listSoalEsai = call_procedure($conn, "ambil_soal_esai('$kodeUjian')");

    if (isset($_POST['save_score'])) {

        $countErr = 0;

        // memasukkan poin jawaban PG
        $listPoinPG = array();
        foreach ($listSoalPG as $soalPG) {
            $kodeSoal = $soalPG['kode_soal'];
            $poin = 0;

            foreach ($listJwbPG as $jwbPG) {
                if ($jwbPG['kode_soal'] === $kodeSoal) {
                    if ($jwbPG['jawaban'] === $soalPG['opsi_benar']) {
                        $poin = $soalPG['poin_benar'];
                    } else {
                        $poin = $soalPG['poin_salah'];
                    }
                    break;
                }
            }

            $insertScore = mysqli_query($conn, "UPDATE Jawaban_Ujian SET poin=$poin
                WHERE ujian='$kodeUjian' AND soal='$kodeSoal' AND mahasiswa='$nim'
            ");

            array_push($listPoinPG, $poin);

            if (!$insertScore) $countErr++;
        }

        // mendapatkan list poin jawaban esai
        $listPoinEsai = array();
        foreach ($_POST as $field => $value) {
            if (strpos($field, 'SOL') === 0) {
                array_push($listPoinEsai, $value);
            }
        }

        // memasukkan poin jawaban Esai
        $i = 0;
        foreach ($listSoalEsai as $soalEsai) {
            $kodeSoal = $soalEsai['kode_soal'];
            $poin = $listPoinEsai[$i];

            $insertScore = mysqli_query($conn, "UPDATE Jawaban_Ujian SET poin=$poin
                WHERE ujian='$kodeUjian' AND soal='$kodeSoal' AND mahasiswa='$nim'
            ");

            if (!$insertScore) $countErr++;
            $i++;
        }

        if ($countErr > 0) {
            $errMsg = mysqli_error($conn);
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat hendak memasukkan poin pada $countErr soal <i>$errMsg</i>"
            );
        }


        // menjumlahkan poin
        $totalPoin = 0;
        foreach ($listPoinPG as $poinPG) {
            $totalPoin += $poinPG;
        }
        foreach ($listPoinEsai as $poinEsai) {
            $totalPoin += $poinEsai;
        }

        // memasukkan data total poin
        $insertTotalScore = mysqli_query($conn, "UPDATE Nilai_Ujian SET nilai = $totalPoin, sudah_dinilai=TRUE
            WHERE ujian='$kodeUjian' AND mahasiswa='$nim'
        ");

        header("location: ./ujian.php?kode=$kodeUjian");
        exit;
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
    <link rel="preload" href="../styles/bootstrap-override.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="../styles/bootstrap-override.css"></noscript>
    <link rel="preload" href="../styles/exam.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="../styles/exam.css"></noscript>
    <title>Ujian <?=$kodeUjian?> <?=$mhs['nama_lengkap']?> | Admin | SPOT RPL</title>
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
        <section class="mb-4">
            <h1>Data Mahasiswa</h1>
            <table class="table">
                <tr>
                    <td>NIM</td>
                    <th><?=$mhs['nim']?></th>
                </tr>
                <tr>
                    <td>Nama</th>
                    <th><?=$mhs['nama_lengkap']?></th>
                </tr>
                <tr>
                    <td colspan="2">Silahkan berikan poin untuk jawaban esai pada ujian ini. Poin untuk jawaban pilihan ganda akan diberikan secara otomatis. Pastikan Anda menekan tombol <b>Simpan Penilaian Ujian</b> setelah memeriksa ujian ini.</td>
                </tr>
            </table>
        </section>
        <?php if (sizeof($listSoalPG) > 0) : ?>
            <section class="mb-4">
                <h2 class="mb-4">Jawaban Pilihan Ganda</h2>
                <?php $i = 1; ?>
                <?php foreach($listSoalPG as $soalPG) : ?>
                    <article class="question border bg-light">
                        <div class="question-header">
                            <span class="nomor">Nomor <?=$i?></span>
                        </div>
                        <div class="question-body">
                            <p><?=$soalPG['pertanyaan']?></p>
                            <?php if (!empty($soalPG['nama_file']) && strpos($soalPG['mimetype'], 'image/') !== FALSE) : ?>
                                <img src="../db/<?=$soalPG['nama_file']?>" alt="gambar soal" width="100%">
                            <?php elseif (!empty($soalPG['nama_file'])) : ?>
                                <a href="../db/<?=$soalPG['nama_file']?>" target="_blank" class="btn btn-success">Lihat File</a>
                            <?php endif; ?>

                            <div class="answer-options">
                                <?php
                                    $kodeSoal = $soalPG['kode_soal'];
                                    $jwbPG = '';
                                    foreach ($listJwbPG as $tJwbPG) {
                                        if ($tJwbPG['kode_soal'] == $kodeSoal) {
                                            $jwbPG = $tJwbPG['jawaban'];
                                        }
                                    }

                                    $optList = array();
                                    foreach ($soalPG as $field => $value) {
                                        if (strpos($field, 'opsi') !== FALSE && !empty($value)) {
                                            array_push($optList, $value);
                                        }
                                    }
                                    shuffle($optList);
                                ?>
                                <?php foreach ($optList as $opt) : ?>
                                    <?php if ($opt === $jwbPG) : ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="<?=$jwbPG?>" checked disabled required>
                                            <label class="form-check-label"><?=$opt?></label>
                                        </div>
                                    <?php else : ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="<?=$jwbPG?>" disabled required>
                                            <label class="form-check-label"><?=$opt?></label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="question-footer">
                            <span>Kunci Jawaban : <b><?=$soalPG['opsi_benar']?></b></span>
                            <?php if ($jwbPG === $soalPG['opsi_benar']) : ?>
                                <span>Poin : <?=$soalPG['poin_benar']?></span>
                            <?php else : ?>
                                <span>Poin : <?=$soalPG['poin_salah']?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
        <form action="./nilai.php?mhs=<?=$nim?>&ujian=<?=$kodeUjian?>" method="post">
            <?php if (sizeof($listSoalEsai) > 0) : ?>
                <section class="mb-4">
                    <h2 class="mb-4">Jawaban Esai</h2>
                    <?php $i = 1; ?>
                    <?php foreach ($listSoalEsai as $soalEsai) : ?>
                        <article class="question border bg-light">
                            <div class="question-header">
                                <span class="nomor">Nomor <?=$i?></span>
                            </div>
                            <div class="question-body">
                                <?php $jwbEsai = $listJwbEsai[$i-1]; ?>
                                <p><?=$soalEsai['pertanyaan']?></p>
                                <?php if (!empty($soalEsai['nama_file']) && strpos($soalEsai['mimetype'], 'image/') !== FALSE) : ?>
                                    <img src="../db/<?=$soalEsai['nama_file']?>" alt="gambar soal" width="100%">
                                <?php elseif (!empty($soalEsai['nama_file'])) : ?>
                                    <a href="../db/<?=$soalEsai['nama_file']?>" target="_blank" class="btn btn-success">Lihat File</a>
                                <?php endif; ?>
                                <article class="mt-3">
                                    <label class="form-label" for="<?=$soalEsai['kode_soal']?>">Jawaban :</label>
                                    <textarea class="form-control" id="<?=$soalEsai['kode_soal']?>" cols="30" rows="1" required disabled><?=$jwbEsai['jawaban']?></textarea>
                                </article>
                            </div>
                            <div class="question-footer">
                                <label class="form-label" for="slider<?=$soalEsai['kode_soal']?>">Poin :</label>
                                <input class="form-range" type="range" name="<?=$soalEsai['kode_soal']?>" id="slider<?=$soalEsai['kode_soal']?>" value="<?=$jwbEsai['poin']?>" min="<?=$soalEsai['poin_salah']?>" max="<?=$soalEsai['poin_benar']?>" step="0.5" oninput="out<?=$soalEsai['kode_soal']?>.value = parseFloat(slider<?=$soalEsai['kode_soal']?>.value) + ' poin'">
                                <div class="slider-help">
                                    <p><?=$soalEsai['poin_salah']?></p>
                                    <b><output for="slider<?=$soalEsai['kode_soal']?>" id="out<?=$soalEsai['kode_soal']?>"></output></b>
                                    <p><?=$soalEsai['poin_benar']?></p>
                                </div>
                                <div class="form-text">Berikan nilai dengan cara menggeser slider di atas.</div>
                            </div>
                        </article>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
            <section class="mb-4 p-4 border bg-light d-flex flex-column gap-3">
                <button type="submit" name="save_score" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1">
                    <span class="material-icons">save</span>
                    <span>Simpan Penilaian Ujian</span>
                </button>
                <a href="./ujian.php?kode=<?=$kodeUjian?>" class="btn btn-secondary d-flex align-items-center justify-content-center gap-2">
                    <span class="material-icons">arrow_back</span>
                    <span>Kembali</span>
                </a>
            </section>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-p34f1UUtsS3wqzfto5wAAmdvj+osOnFyQFpp4Ua3gs/ZVWx6oOypYoCJhGGScy+8" crossorigin="anonymous"></script>
    <script>

    </script>
</body>
</html>
