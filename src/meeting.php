<?php
    session_start();

    // mengimport koneksi database ($conn)
    require './includes/db-connect.php';

    // mengimport user-defined functions
    include './includes/function.php';

    //  mengarahkan kembali ke halaman 404 jika URL tidak valid
    if (!isset($_GET['kelas']) || empty($_GET['kelas'])) {
        $_SESSION['alert'] = array('error' => TRUE, 'message' => "URL tidak valid!");
        header("location: not-found.php");
        exit;
    }

    // mendapatkan url dari laman saat ini
    $urlOfThisPage = get_url_of_this_page();

    // mengarahkan ke halaman login jika sesi login tidak aktif atau user tidak valid
    if (!isset($_SESSION['login']) || !$_SESSION['login'] || !isset($_SESSION['user']) || empty($_SESSION['user'])) {
        header("location: login.php?redirect=$urlOfThisPage");
        exit;
    }


    // mendapatkan kode kelas yang akan diakses
    $kodeKelas = $_GET['kelas'];

    // mendapatkan nomor pertemuan yang hendak diakses (default: 1)
    $noPertemuan = (isset($_GET['pert']) && (int)$_GET['pert'] > 0) ? (int)$_GET['pert'] : 1;

    // daftar menu
    $menus = array('Materi', 'Tugas', 'Evaluasi', 'Monitoring');

    // mendapatkan data menu yang ingin diakses pengguna
    $menu = (isset($_GET['menu']) && !empty($_GET['menu'])) ? $_GET['menu'] : 'Materi';

    // mendapatkan NIM dari user yang sedang login
    $nim = $_SESSION['user']['id'];

    // mendapatkan data pertemuan.
    $pertemuanResult = call_procedure($conn, "get_meeting('$kodeKelas', $noPertemuan)");
    $pertemuan = (sizeof($pertemuanResult) === 1) ? $pertemuanResult[0] : null;


    // jika kode kelas tidak ditemukan
    if (empty($pertemuan)) {
        $_SESSION['alert'] = array(
            'error' => true,
            'message' => "<b>Kode Kelas tidak ditemukan.</b> Pastikan URL sudah benar"
        );

        // jika terjadi error karena MySQL
        if (last_query_error($conn)) $_SESSION['alert'] = last_query_error($conn);
        header('location: not-found.php');
        exit;
    }

    // jika pertemuan tidak tersedia
    if (!$pertemuan['is_exist']) {
        $_SESSION['alert'] = array(
            'error' => true,
            'message' => "Pertemuan ke-$noPertemuan tidak tersedia!<br>Silahkan hubungi dosen Anda."
        );
    } elseif (!$pertemuan['is_accessible']) {
        // jika pertemuan belum bisa diakses saat ini
        $waktu_akses = $pertemuan['access_time'];
        $_SESSION['alert'] = array(
            'error' => true,
            'message' => "Pertemuan ke-$noPertemuan masih terkunci!<br>Anda baru dapat mengaksesnya pada tanggal <b>$waktu_akses</b>"
        );
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require './components/head.php'; ?>
    <?php require './components/head-page.php'; ?>
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
            <?php
                $kodeMK = $pertemuan['kode_mk'];
                try {
                    $rps = $conn->query("SELECT * FROM RPS WHERE mata_kuliah='$kodeMK'")->fetch_assoc();
                    $silabus = $conn->query("SELECT * FROM Silabus WHERE mata_kuliah='$kodeMK'")->fetch_assoc();
                } catch (\Throwable $th) {
                    $_SESSION['alert'] = array('error' => true, 'message' => $th);
                }
            ?>
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
    <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])) : ?>
        <main>
            <section class="content">
                <article>
                    <h3>Error!</h3>
                        <p><?=$_SESSION['alert']['message']?></p>
                    <?php $_SESSION['alert'] = null;?>
                </article>
                <a href="./dashboard.php" id="logoutBtn" class="btn primary-btn">
                    <i class="material-icons-outlined">home</i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </section>
        </main>
    <?php elseif (!empty($pertemuan) && $pertemuan['is_exist'] && $pertemuan['is_accessible']) : ?>
        <main>
            <section class="head">
                <h1><?=$pertemuan['kode_mk']?> - <?=$pertemuan['nama_mk']?></h1>
                <?php
                    $meetingsOpened = call_procedure($conn, "daftar_pertemuan_dibuka('$kodeKelas')");
                    if (last_query_error($conn)) {
                        $_SESSION['alert'] = last_query_error($conn);
                        header("location: $urlOfThisPage");
                        exit;
                    }
                ?>
                <?php if (isset($meetingsOpened) && !empty($meetingsOpened)) : ?>
                    <select name="meeting" id="meetingID">
                        <option disabled>-- Pilih Pertemuan --</option>
                        <?php foreach ($meetingsOpened as $meeting) : ?>
                            <?php if ($meeting['nomor_pert'] == $noPertemuan) : ?>
                                <option data-link="./meeting.php?kelas=<?=$kodeKelas?>&pert=<?=$meeting['nomor_pert']?>" selected>Pertemuan <?=$meeting['nomor_pert']?></option>
                            <?php else : ?>
                                <option data-link="./meeting.php?kelas=<?=$kodeKelas?>&pert=<?=$meeting['nomor_pert']?>">Pertemuan <?=$meeting['nomor_pert']?></option>
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
                                    <li><a href="./meeting.php?kelas=<?=$kodeKelas?>&pert=<?=$noPertemuan?>&menu=<?=$mn?>" class="menu active"><?=$mn?></a></li>
                                    <?php else : ?>
                                        <li><a href="./meeting.php?kelas=<?=$kodeKelas?>&pert=<?=$noPertemuan?>&menu=<?=$mn?>" class="menu"><?=$mn?></a></li>
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
                        $materiResult = $conn->query("SELECT * FROM Materi WHERE pertemuan='$kodeMeeting'");
                        if (last_query_error($conn)) $_SESSION['alert'] = last_query_error($conn);
                    ?>
                    <?php if ($materiResult && $materiResult->num_rows > 0) : ?>
                        <?php while ($materi = $materiResult->fetch_assoc()) : ?>
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
                        $tugasResult = $conn->query("SELECT * FROM Tugas WHERE pertemuan='$kodeMeeting' ORDER BY deadline ASC, kode ASC");
                        if (last_query_error($conn)) $_SESSION['alert'] = last_query_error($conn);

                        // menghandle form submit tugas
                        if (isset($_POST['upload_tugas'])) {
                            $noTugas = htmlspecialchars($_POST['no_tugas']);
                            $kodeTugas = htmlspecialchars($_POST['kode_tugas']);
                            $kodeSubmit = htmlspecialchars($_POST['kode_submit']);
                            $nim = htmlspecialchars($_POST['nim_mhs']);

                            $isKodeSubmitExist = $conn->query("SELECT EXISTS(SELECT kode FROM Submit_Tugas WHERE kode='$kodeSubmit') AS is_exists");

                            // jika sudah ada file yang dikumpulkan untuk tugas ini
                            if ($isKodeSubmitExist && $isKodeSubmitExist->fetch_row()[0]) {
                                echo "<p>Anda sudah mengumpulkan file untuk <b>Tugas $noTugas.</b> Silahkan hapus file lama jika ingin mengumpulkan file baru!</p>";

                            } else {
                                $fileTugas = $_FILES['file_tugas'];

                                // mendapatkan nama file & ekstensinya
                                $breakFileName = break_filename($fileTugas);
                                $fileName = $breakFileName['name'];
                                $fileExt = $breakFileName['ext'];
                                $newFileName = $kodeSubmit.'_'.$fileName.'.'.$fileExt;          // nama file baru
                                $fileDestination = './db/'.$newFileName;                       // lokasi tujuan penyimpanan file

                                $uploadRespons = upload_file($fileTugas, $fileDestination);

                                if ($uploadRespons['error']) {
                                    echo "<p>".$uploadRespons['message']."</p>";

                                } else {
                                    $stmt = $conn->prepare("INSERT INTO Submit_Tugas (kode, mahasiswa, tugas, file_tugas, mimetype) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->bind_param('sssss', $kodeSubmit, $nim, $kodeTugas, $newFileName, $fileTugas['type']);

                                    if ($stmt->execute()) {
                                        echo "<p>File untuk <b>Tugas $noTugas</b> berhasil dikumpulkan.</p>";
                                    }
                                }
                            }
                        }

                        // menghandle form hapus tugas
                        if (isset($_POST['hapus_tugas'])) {
                            $kodeSubmit = $_POST['kode_submit'];
                            $namaFile = $_POST['nama_file'];
                            $nomorTugas = $_POST['nomor_tugas'];

                            $deleteRespons = $conn->query("DELETE FROM Submit_Tugas WHERE kode='$kodeSubmit'");

                            if ($deleteRespons) {
                                if (file_exists("./db/$namaFile")) {
                                    if (unlink("./db/$namaFile")) echo "<p>File yang Anda kumpulkan untuk <b>Tugas $nomorTugas</b> berhasil dihapus.</p>";
                                } else {
                                    echo "<p>File yang Anda kumpulkan untuk <b>Tugas $nomorTugas</b> sudah tidak ada.</p>";
                                }
                            } else {
                                // memberikan respon gagal
                                echo "<p>Terjadi kesalahan saat menghapus data.<br><i>$conn->error!</i>";
                            }
                        }
                    ?>
                    <?php if ($tugasResult && $tugasResult->num_rows > 0) : ?>
                        <?php $i = 1; ?>
                        <?php while ($tugas = $tugasResult->fetch_assoc()) : ?>
                            <article>
                                <?php
                                    // mendapatkan waktu server saat ini
                                    $getCurrentTime = $conn->query("SELECT NOW()");
                                    $currentTime = strtotime($getCurrentTime->fetch_row()[0]);

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
                                    $submitResult = $conn->query("SELECT * FROM Submit_Tugas WHERE mahasiswa='$nim' && tugas='$kodeTugas'");
                                    $submitTugas = ($submitResult && $submitResult->num_rows === 1) ? $submitResult->fetch_assoc() : null;
                                    if ($error = last_query_error($conn)) echo "<p>".$error['message']."</p>";
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
                                    <form action="./meeting.php?kelas=<?=$kodeKelas?>&pert=<?=$noPertemuan?>&menu=Tugas" method="post" enctype="multipart/form-data">
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
                        $ujianResult = $conn->query("SELECT * FROM Ujian WHERE pertemuan = '$kodeMeeting'");
                        if ($error = last_query_error($conn)) echo "<p>".$error['message']."</p>";
                    ?>
                    <?php if ($ujianResult && $ujianResult->num_rows > 0) : ?>
                        <?php $i = 1 ?>
                        <?php while ($ujian = $ujianResult->fetch_assoc()) : ?>
                            <article>
                                <h3>Ujian <?=$i?></h3>
                                <a href="./exam.php?kode=<?=$ujian['kode']?>" class="btn">
                                    <i class="material-icons-outlined">task</i>
                                    <span>Kerjakan Sekarang</span>
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
                        if ($error = last_query_error($conn)) echo "<p>".$error['message']."</p>";
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
                    <p>Menu "<b><?=$menu?></b>" tidak ditemukan!</p>
                </section>
            <?php endif; ?>
        </main>
    <?php endif; ?>
    <script src="./script/navbar.js"></script>
    <script src="./script/meeting.js"></script>
</body>
</html>
