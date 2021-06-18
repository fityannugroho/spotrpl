<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    // memastikan URL valid
    if (!isset($_GET['kelas']) || empty($_GET['kelas'])) {
        // mengarahkan kembali ke halaman utama
        header("location: not-found.php");
        exit;
    }

    // mendapatkan kode kelas yang akan diakses
    $kodeKelas = $_GET['kelas'];

    // mendapatkan nomor pertemuan yang hendak diakses (default: 1)
    $noPertemuan = (isset($_GET['pertemuan']) && !empty($_GET['pertemuan'])) ? (int)$_GET['pertemuan'] : 1;

    // daftar menu
    $menus = array('Materi', 'Tugas', 'Evaluasi', 'Monitoring');

    // mendapatkan data menu yang ingin diakses pengguna
    $menu = (isset($_GET['menu']) && !empty($_GET['menu'])) ? $_GET['menu'] : 'Materi';

    // jika sesi login tidak aktif atau user tidak valid
    if (!isset($_SESSION['login']) || !$_SESSION['login'] || !isset($_SESSION['user']) || empty($_SESSION['user'])) {
        // mengarahkan ke halaman login
        $redirectLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        header("location: login.php?redirect=$redirectLink");
        exit;
    }

    // mendapatkan NIM dari user yang sedang login
    $nim = $_SESSION['user']['id'];

    // mendapatkan data pertemuan.
    $pertemuan = call_procedure($conn, "get_meeting('$kodeKelas', $noPertemuan)");

    if (sizeof($pertemuan) !== 1) {
        $_SESSION['alert'] = array(
            'error' => TRUE,
            'message' => "Kode kelas <b>'$kodeKelas'</b> tidak dapat ditemukan!"
        );

        header("location: not-found.php");
        exit;
    }

    $pertemuan = $pertemuan[0];

    // memastikan jika pertemuan yang dicari ada dan sudah dapat diakses
    if ($pertemuan['is_exist'] && $pertemuan['is_accessible']) {

        // mendapatkan daftar pertemuan yang sudah terbuka (dapat diakses)
        $meetingsOpened = call_procedure($conn, "daftar_pertemuan_dibuka('$kodeKelas')");

    } else {

        if (isset($pertemuan['is_accessible']) && !$pertemuan['is_accessible']) {
            // jika pertemuan belum bisa diakses saat ini
            $waktu_akses = $pertemuan['access_time'];
            $_SESSION['error'] = "Pertemuan ke-$noPertemuan masih terkunci! <br> Anda baru dapat mengaksesnya pada tanggal <b>$waktu_akses</b>";

        } else {
            // jika pertemuan yang dicari tidak ada
            $_SESSION['error'] = "<b>Pertemuan ke-$noPertemuan belum dibuat!</b><br> Silahkan tanyakan kepada dosen Anda.";
        }
    }


    // mendapatkan data rps mata kuliah
    $kodeMK = $pertemuan['kode_mk'];
    $rpsResult = mysqli_query($conn, "SELECT * FROM RPS WHERE mata_kuliah='$kodeMK'");
    $rps = mysqli_fetch_assoc($rpsResult);

    // mendapatkan data silabus mata kuliah
    $silabusResult = mysqli_query($conn, "SELECT * FROM Silabus WHERE mata_kuliah='$kodeMK'");
    $silabus = mysqli_fetch_assoc($silabusResult);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/logomark.ico" type="image/x-icon">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined&display=swap"></noscript>
    <link rel="preload" href="./styles/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/style.css"></noscript>
    <link rel="preload" href="./styles/navbar.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/navbar.css"></noscript>
    <link rel="preload" href="./styles/meeting.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./styles/meeting.css"></noscript>
    <title><?=$pertemuan['nama_mk']?> <?=$pertemuan['nama_kelas']?> | SPOT RPL</title>
</head>
<body>
    <nav>
        <a href="./index.php" class="logo" title="SPOT RPL">
            <img src="./assets/logomark.png" alt="logo" height="40" role="img">
            <div class="logo-name">
                <span class="name1">SPOT RPL</span>
                <span class="name2">Sistem Pembelajaran Online Terpadu</span>
            </div>
        </a>
        <ul class="main-menus">
            <?php if (!empty($silabus['nama_file'])) : ?>
                <li><a href="./db/<?=$silabus['nama_file']?>" target="_blank">Silabus</a></li>
            <?php else : ?>
                <li><a href="#" target="">Silabus</a></li>
            <?php endif; ?>
            <?php if (!empty($rps['nama_file'])) : ?>
                <li><a href="./db/<?=$rps['nama_file']?>" target="_blank">RPS</a></li>
            <?php else : ?>
                <li><a href="#" target="">RPS</a></li>
            <?php endif; ?>
        </ul>
        <div class="right-group">
            <div id="profileToggle" class="profile-toggle">
                <img class="avatar" src="./assets/profile-avatar.png" alt="avatar" title="Lihat Profil">
                <i class="material-icons-outlined">arrow_drop_down</i>
            </div>
            <div class="profile-box">
                <div class="profile-content">
                    <img src="./assets/profile-avatar.png" alt="avatar">
                    <div class="info">
                        <p><b><?=$_SESSION['user']['name']?></b></p>
                        <p><?=$_SESSION['user']['id']?></p>
                    </div>
                </div>
                <hr>
                <div class="profile-btn">
                    <a href="" id="logoutBtn" type="menu" class="btn secondary-btn">
                        <i class="material-icons-outlined">manage_accounts</i>
                        <span>Atur Profil</span>
                    </a>
                    <a href="logout.php" id="logoutBtn" type="menu" class="btn primary-btn">
                        <i class="material-icons-outlined">logout</i>
                        <span>Keluar</span>
                    </a>
                </div>
            </div>
            <button class="icon-btn burger-icon"><i class="material-icons-outlined">menu</i></button>
        </div>
    </nav>
    <?php if (!empty($pertemuan) && $pertemuan['is_exist'] && $pertemuan['is_accessible']) : ?>
        <main>
            <section class="head">
                <h1><?=$pertemuan['kode_mk']?> - <?=$pertemuan['nama_mk']?></h1>
                <?php if (isset($meetingsOpened) && !empty($meetingsOpened)) : ?>
                    <select name="meeting" id="meetingID">
                        <option disabled>-- Pilih Pertemuan --</option>
                        <?php foreach ($meetingsOpened as $meeting) : ?>
                            <?php if ($meeting['nomor_pert'] == $noPertemuan) : ?>
                                <option data-link="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$meeting['nomor_pert']?>" selected>Pertemuan <?=$meeting['nomor_pert']?></option>
                            <?php else : ?>
                                <option data-link="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$meeting['nomor_pert']?>">Pertemuan <?=$meeting['nomor_pert']?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </section>
            <section>
                <div class="jumbotron">
                    <h2>Pertemuan <?=$pertemuan['nomor_pert']?></h2>
                    <h3><?=$pertemuan['topik']?></h3>
                    <span>Waktu akses : <?=$pertemuan['waktu_akses']?></span>
                </div>
                <div class="menus">
                    <ul>
                        <li class="sticky">
                            <a href="./dashboard.php" class="menu icon-btn" title="Ke Dashboard">
                                <i class="material-icons-outlined">arrow_back</i>
                            </a>
                        </li>
                        <?php if (isset($menus) && !empty($menus)) : ?>
                            <?php foreach ($menus as $mn) : ?>
                                <?php if (strtoupper($mn) === strtoupper($menu)) : ?>
                                    <li><a href="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$noPertemuan?>&menu=<?=$mn?>" class="menu active"><?=$mn?></a></li>
                                    <?php else : ?>
                                        <li><a href="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$noPertemuan?>&menu=<?=$mn?>" class="menu"><?=$mn?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>
            <?php if (isset($menu) && $menu === 'Materi') : ?>
                <section class="content" id="materi">
                    <?php if (!empty($pertemuan['deskripsi'])) : ?>
                        <section class="desc">
                            <article>
                                <h3>Deskripsi Pertemuan</h3>
                                <p><?=$pertemuan['deskripsi']?></p>
                            </article>
                        </section>
                    <?php endif; ?>
                    <?php
                        // kode pertemuan diambil dari $pertemuan['kode']
                        $kodeMeeting = $pertemuan['kode'];

                        // mendapatkan data materi
                        $materiResult = mysqli_query($conn, "SELECT * FROM Materi WHERE pertemuan='$kodeMeeting'");

                        if ($materiResult === FALSE) {
                            $error = mysqli_error($conn);
                            echo "<script>alert('ERROR: $error (code: $codeErr)')</script>";
                        }
                    ?>
                    <?php if ($materiResult && mysqli_num_rows($materiResult) > 0) : ?>
                        <?php while ($materi = mysqli_fetch_assoc($materiResult)) : ?>
                            <article>
                                <h3><?=$materi['judul']?></h3>
                                <p><?=$materi['deskripsi']?></p>
                                <?php if (strpos($materi['mimetype'], 'image') !== FALSE) : ?>
                                    <img src="./db/<?=$materi['nama_file']?>" alt="<?=$materi['nama_file']?>" width="100%">
                                <?php elseif (!empty($materi['nama_file'])) : ?>
                                    <a href="./db/<?=$materi['nama_file']?>" target="_blank" class="btn">
                                        <i class="material-icons-outlined">attachment</i>
                                        Unduh File Materi
                                    </a>
                                <?php else : ?>
                                    <a href="<?=$materi['url']?>" target="_blank" class="btn">
                                        <i class="material-icons-outlined">link</i>
                                        Kunjungi URL
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <article>
                            <p>Belum ada materi pada pertemuan ini.</p>
                        </article>
                    <?php endif; ?>
                </section>
            <?php elseif (isset($menu) && $menu === 'Tugas') : ?>
                <section class="content" id="tugas">
                    <?php
                        // kode pertemuan diambil dari $pertemuan['kode']
                        $kodeMeeting = $pertemuan['kode'];

                        // mendapatkan data tugas
                        $tugasResult = mysqli_query($conn, "SELECT * FROM Tugas WHERE pertemuan='$kodeMeeting' ORDER BY deadline ASC, kode ASC");

                        if ($tugasResult === FALSE) {
                            $error = mysqli_error($conn);
                            echo "<script>alert('ERROR: $error (code: $codeErr)')</script>";
                        }

                        // menghandle form submit tugas
                        if (isset($_POST['upload_tugas'])) {

                            $noTugas = $_POST['no_tugas'];
                            $kodeTugas = $_POST['kode_tugas'];
                            $kodeSubmit = $_POST['kode_submit'];
                            $nim = $_POST['nim_mhs'];
                            $fileTugas = $_FILES['file_tugas'];

                            // persyaratan file
                            $allowedExt = array('jpg', 'jpeg', 'png', 'pdf', 'pptx', 'docx', 'zip', 'rar');
                            $maxAllowedSize = 5000000; // 5 MB

                            // mendapatkan nama file & ekstensinya
                            $breakFileName = explode('.', $fileTugas['name']);
                            $fileName = $breakFileName[0];
                            $fileExt = strtolower(end($breakFileName));

                            // mendapatkan informasi file lainnya
                            $mimetype = $fileTugas['type'];
                            $fileSize = $fileTugas['size'];
                            $fileTmp = $fileTugas['tmp_name'];
                            $fileError = $fileTugas['error'];

                            $isKodeSubmitExist = mysqli_query($conn, "SELECT EXISTS(SELECT kode FROM Submit_Tugas WHERE kode='$kodeSubmit') AS is_exists");

                            if ($isKodeSubmitExist && mysqli_fetch_row($isKodeSubmitExist)[0]) {
                                // jika sudah ada file yang dikumpulkan untuk tugas ini
                                echo "<p>Anda sudah mengumpulkan file untuk <b>Tugas $noTugas.</b> Silahkan hapus file lama jika ingin mengumpulkan file baru!</p>";

                            } else {

                                $uploadRespons = FALSE;

                                // memastikan tidak ada error pada file & file yang diupload sesuai persyaratan
                                if ($fileError !== 0 || !in_array($fileExt, $allowedExt) || $fileSize > $maxAllowedSize) {
                                    // jika file tidak sesuai persyaratan atau terjadi error
                                    echo "<p>Terjadi kesalahan saat mengupload file! Pastikan file yang akan diupload sudah memenuhi persyaratan.</p>";

                                } else {

                                    $newFileName = $kodeSubmit.'_'.$fileName.'.'.$fileExt;          // nama file baru
                                    $fileDestination = './db/'.$newFileName;                       // lokasi tujuan penyimpanan file

                                    // mengupload file direktori server & menginsert data materi ke database mysql
                                    if (move_uploaded_file($fileTmp, $fileDestination)) {

                                        $insertQuery = "INSERT INTO Submit_Tugas (kode, mahasiswa, tugas, file_tugas, mimetype)
                                            VALUES ('$kodeSubmit', '$nim', '$kodeTugas', '$newFileName', '$mimetype')
                                        ";
                                        $uploadRespons = mysqli_query($conn, $insertQuery);
                                    }
                                }

                                // memberikan respon berhasil
                                if ($uploadRespons) {
                                    echo "<p>File untuk <b>Tugas $noTugas</b> berhasil dikumpulkan.</p>";
                                }
                            }
                        }

                        // menghandle form hapus tugas
                        if (isset($_POST['hapus_tugas'])) {
                            $kodeSubmit = $_POST['kode_submit'];
                            $namaFile = $_POST['nama_file'];
                            $nomorTugas = $_POST['nomor_tugas'];

                            $deleteRespons = mysqli_query($conn, "DELETE FROM Submit_Tugas WHERE kode='$kodeSubmit'");

                            if ($deleteRespons) {
                                if (file_exists("./db/$namaFile")) {
                                    unlink("./db/$namaFile");
                                    echo "<p>File yang Anda kumpulkan untuk <b>Tugas $nomorTugas</b> berhasil dihapus.</p>";
                                } else {
                                    echo "<p>File yang Anda kumpulkan untuk <b>Tugas $nomorTugas</b> sudah tidak ada.</p>";
                                }
                            } else {
                                // memberikan respon gagal
                                $errorMsg = mysqli_error($conn);
                                echo "<p>Terjadi kesalahan saat menghapus data.<br><i>$errorMsg!</i>";
                            }
                        }
                    ?>
                    <?php if ($tugasResult && mysqli_num_rows($tugasResult) > 0) : ?>
                        <?php $i = 1; ?>
                        <?php while ($tugas = mysqli_fetch_assoc($tugasResult)) : ?>
                            <article>
                                <?php
                                    // mendapatkan waktu server saat ini
                                    $getCurrentTime = mysqli_query($conn, "SELECT NOW()");
                                    $currentTime = strtotime(mysqli_fetch_row($getCurrentTime)[0]);

                                    // mendapatkan waktu deadline
                                    $deadline = strtotime($tugas['deadline']);

                                    // menghitung sisa waktu yang tersisa
                                    $leftTime = $deadline - $currentTime;
                                    $dayLeft = floor($leftTime/(60*60*24));
                                    $leftTime %= 60*60*24;
                                    $hoursLeft = floor($leftTime/(60*60));
                                    $leftTime %= 60*60;
                                    $minutesLeft = floor($leftTime/(60));
                                    $leftTime %= 60;

                                    // mendapatkan data submit tugas untuk tugas ini
                                    $kodeTugas = $tugas['kode'];
                                    $submitResult = mysqli_query($conn, "SELECT * FROM Submit_Tugas WHERE mahasiswa='$nim' && tugas='$kodeTugas'");
                                    $submitTugas = ($submitResult && mysqli_num_rows($submitResult) === 1) ? mysqli_fetch_assoc($submitResult) : NULL;

                                    if (!$submitResult) {
                                        $errorMsg = mysqli_error($conn);
                                        echo "<p>Terjadi Error saat mengambil submit tugas<i>$errorMsg</i></p>";
                                    }
                                ?>
                                <h3>Tugas <?=$i?> : <?=$tugas['judul']?></h3>
                                <table>
                                    <tr>
                                        <th title="Batas Waktu Pengumpulan">Deadline</th>
                                        <td>
                                            <p><b><?=$tugas['deadline']?></b></p>
                                            <?php if ($leftTime >= 0) : ?>
                                                <p><?=$dayLeft?> hari <?=$hoursLeft?> jam <?=$minutesLeft?> menit dari sekarang</p>
                                            <?php else : ?>
                                                <p>0 hari 0 jam 0 menit dari sekarang</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Deskripsi</th>
                                        <?php if (empty($tugas['deskripsi'])) : ?>
                                            <td>-</td>
                                        <?php else : ?>
                                            <td><?=$tugas['deskripsi']?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <tr>
                                        <th>Lampiran</th>
                                        <?php if (empty($tugas['lampiran'])) : ?>
                                            <td>-</td>
                                        <?php else : ?>
                                            <td><a href="./db/<?=$tugas['lampiran']?>" target="_blank"><?=$tugas['lampiran']?></a></td>
                                        <?php endif; ?>
                                    </tr>
                                    <form action="./meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$noPertemuan?>&menu=Tugas" method="post" enctype="multipart/form-data">
                                        <?php if (!empty($submitTugas)) : ?>
                                            <tr>
                                                <th>Preview Tugas</th>
                                                <td><a href="./db/<?=$submitTugas['file_tugas']?>" target="_blank" rel="noopener noreferrer"><?=$submitTugas['file_tugas']?></a></td>
                                            </tr>
                                            <tr>
                                                <th>Status Pengumpulan</th>
                                                <td>Sudah mengumpulkan</td>
                                            </tr>
                                        <?php else : ?>
                                            <tr>
                                                <th><label for="fileTugas">Upload Tugas</label></th>
                                                <td><input type="file" name="file_tugas" id="fileTugas" required></td>
                                            </tr>
                                            <tr>
                                                <th>Preview Tugas</th>
                                                <td><i>File tugas yang diupload akan muncul disini.</i></td>
                                            </tr>
                                            <tr>
                                                <th>Status Pengumpulan</th>
                                                <td>Belum mengumpulkan</td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Tindakan</th>
                                            <td class=".action-btn">
                                                <input type="hidden" name="no_tugas" value="<?=$i?>">
                                                <input type="hidden" name="nim_mhs" value="<?=$nim?>">
                                                <input type="hidden" name="kode_tugas" value="<?=$tugas['kode']?>">
                                                <input type="hidden" name="kode_submit" value="<?='SMT'.substr($tugas['kode'], 3)?>">
                                                <?php if (empty($submitTugas)) : ?>
                                                    <button type="submit" class="btn submit" name="upload_tugas" id="submitTask">Kumpulkan Tugas</button>
                                                <?php else : ?>
                                                    <form action="./meeting.php?kode=<?=$kodeMeeting?>" method="post">
                                                        <input type="hidden" name="kode_submit" value="<?=$submitTugas['kode']?>">
                                                        <input type="hidden" name="nama_file" value="<?=$submitTugas['file_tugas']?>">
                                                        <input type="hidden" name="nomor_tugas" value="<?=$i?>">
                                                        <button type="submit" name="hapus_tugas" class="btn clear" id="deleteTask" onclick="return confirm('Anda yakin ingin menghapus tugas yang sudah dikumpulkan?')">Hapus Tugas</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </form>
                                </table>
                            </article>
                        <?php $i++; ?>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <article>
                            <p>Belum ada tugas pada pertemuan ini.</p>
                        </article>
                    <?php endif; ?>
                </section>
            <?php elseif (isset($menu) && $menu === 'Evaluasi') : ?>
                <section class="content" id="evaluasi">
                    <?php
                        // mendapatkan data monitoring
                        $kodeMeeting = $pertemuan['kode'];
                        $ujianResult = mysqli_query($conn, "SELECT * FROM Ujian WHERE pertemuan = '$kodeMeeting'");

                        if (!$ujianResult) {
                            $errMsg = mysqli_error($conn);
                            echo "<p>Terjadi kesalahan saat mengambil data ujian! <i>$errMsg</i></p>";
                        }
                    ?>
                    <?php if ($ujianResult && mysqli_num_rows($ujianResult) > 0) : ?>
                        <?php $i = 1 ?>
                        <?php while ($ujian = mysqli_fetch_assoc($ujianResult)) : ?>
                            <article>
                                <h3>Ujian <?=$i?></h3>
                                <a href="./exam.php?kode=<?=$ujian['kode']?>" class="btn">
                                    <i class="material-icons-outlined">task</i>
                                    Kerjakan Sekarang
                                </a>
                            </article>
                            <?php $i++; ?>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p>Belum ada ujian untuk pertemuan ini.</p>
                    <?php endif; ?>
                </section>
            <?php elseif (isset($menu) && $menu === 'Monitoring') : ?>
                <section class="content" id="monitoring">
                    <?php
                        // mendapatkan data monitoring
                        $kodeMeeting = $pertemuan['kode'];
                        $listPresensi = call_procedure($conn, "absensi_pertemuan('$kodeMeeting')");

                        if (mysqli_errno($conn) !== 0) {
                            $errMsg = mysqli_error($conn);
                            echo "<p>Terjadi kesalahan saat mengambil data presensi! <i>$errMsg</i></p>";
                        }
                    ?>
                    <article>
                        <h3>Absensi Mahasiswa</h3>
                        <?php if (sizeof($listPresensi) > 0) : ?>
                            <div class="responsive-table">
                                <table id="attendanceTable">
                                    <thead>
                                        <tr>
                                            <th>NIM</th>
                                            <th>Nama</th>
                                            <th>Kehadiran</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listPresensi as $presensi) : ?>
                                            <tr>
                                                <td><?=$presensi['nim']?></td>
                                                <td><?=$presensi['nama_lengkap']?></td>
                                                <?php if ($presensi['hadir']) : ?>
                                                    <td><i class="material-icons" title="Hadir">check_circle</i></td>
                                                <?php else : ?>
                                                    <td><i class="material-icons" title="Tidak Hadir">cancel</i></td>
                                                <?php endif; ?>
                                                <td><?=$presensi['keterangan']?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p>Daftar presensi tidak ditemukan untuk pertemuan ini.</p>
                        <?php endif; ?>
                    </article>
                </section>
            <?php else : ?>
                <section class="content">
                    <b>ERROR 404</b>
                    <p>Menu "<b><?=$menu?></b>" yang ingin anda akses tidak ditemukan!</p>
                </section>
            <?php endif; ?>
        </main>
    <?php else : ?>
        <main>
            <section class="content">
                <article>
                    <h3>Error!</h3>
                    <?php if (isset($_SESSION['error'])) : ?>
                        <p><?=$_SESSION['error']?></p>
                    <?php endif; ?>
                </article>
                <a href="./dashboard.php" id="logoutBtn" class="btn primary-btn">
                    <i class="material-icons-outlined">home</i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </section>
        </main>
    <?php endif; ?>
    <script src="./script/navbar.js"></script>
    <script src="./script/meeting.js"></script>
</body>
</html>
