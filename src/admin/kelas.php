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


    // mendapatkan kode dari kelas yang akan diakses
    $kodeKelas = $_GET['kode'];

    // mendapatkan data kelas
    $klsResult = $conn->query("SELECT * FROM Kelas WHERE kode='$kodeKelas'");

    if ($klsResult->num_rows !== 1) {
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

    // menangani form hapus kelas
    if (isset($_POST['hapus_kelas'])) {
        $kodeKls = htmlspecialchars($_POST['kode_kls']);
        $kodeMK = htmlspecialchars($_POST['kode_mk']);

        $deleteRespons = $conn->query("DELETE FROM Kelas WHERE kode='$kodeKls'");

        if ($deleteRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Data Kelas berhasil dihapus."
            );
            header("location: ./matkul.php?kode=$kodeMK");
            exit;

        } else {
            // memberikan respon gagal
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan saat menghapus data.<br><i>$conn->error!</i>"
            );
        }
    }


    // menghandle form ubah data kelas
    if (isset($_POST['ubah_kelas'])) {
        $nama = substr($kelas['nama'], 0, 1).htmlspecialchars($_POST['nama']);
        $kapasitas = htmlspecialchars($_POST['kapasitas']);

        $updateResult = $conn->query("UPDATE Kelas SET nama='$nama', kapasitas=$kapasitas WHERE kode='$kodeKelas'");

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


    // menghandle form buat pertemuan baru
    if (isset($_POST['buat_pertemuan'])) {
        $nomor_pert = htmlspecialchars($_POST['nomor_pert']);
        $topik = htmlspecialchars($_POST['topik']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $tgl_akses = htmlspecialchars($_POST['tgl_akses']);
        $jam_akses = htmlspecialchars($_POST['jam_akses']);
        $waktu_akses = $tgl_akses.' '.$jam_akses;
        $kode = '';

        // mencari kode yang belum terpakai
        do {
            $kode = code_generator(5, 'PRT');
            $checkPK = $conn->query("SELECT * FROM Pertemuan WHERE kode='$kode'");
        } while ($checkPK !== FALSE && $checkPK->num_rows > 0);

        // menambahkan data pertemuan baru
        $insertRespons = $conn->query("INSERT INTO Pertemuan (kode, kelas, nomor_pert, topik, deskripsi, waktu_akses)
            VALUES ('$kode', '$kodeKelas', $nomor_pert, '$topik', '$deskripsi', '$waktu_akses')");

        // memberikan respons
        if ($insertRespons) {
            $_SESSION['alert'] = array(
                'error' => FALSE,
                'message' => "Pertemuan baru berhasil ditambahkan."
            );
            header("location: $urlOfThisPage");
            exit;

        } else {
            $duplicatePKErr = 1062;
            if ($conn->errno === $duplicatePKErr) {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Kode Pertemuan <b>'$kode'</b> sudah terpakai! Harap gunakan kode lain."
                );
            } else {
                $_SESSION['alert'] = array(
                    'error' => TRUE,
                    'message' => "Terjadi kesalahan saat menambahkan data pertemuan.<br><i>$conn->error!</i>"
                );
            }
        }
    }


    // mendapatkan data mata kuliah dari kelas ini
    $kodeMK = $kelas['mata_kuliah'];
    $mkResult = $conn->query("SELECT * FROM Mata_Kuliah WHERE kode='$kodeMK'");

    if ($mkResult && $mkResult->num_rows === 1) {
        // mendapatkan data mata kuliah
        $matkul = $mkResult->fetch_assoc();

    } else {
        if ($mkResult->num_rows === 0) {
            // jika data mata kuliah tidak ditemukan pada database
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Data mata kuliah yang terkait dengan kelas ini tidak dapat ditemukan"
            );
        } else {
            $_SESSION['alert'] = array(
                'error' => TRUE,
                'message' => "Terjadi kesalahan! <i>$conn->error</i>"
            );
        }
        // mengarahkan kembali ke halaman utama admin
        header("location: ../admin.php");
        exit;
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
    <title>Kelas <?=$kelas['nama']?> <?=$matkul['nama']?> | Admin | SPOT RPL</title>
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
                <li class="breadcrumb-item active" aria-current="page">Kelas <?=$kelas['nama']?></li>
            </ol>
        </nav>
        <section class="mb-4">
            <h1>Kelas <?=$kelas['nama']?></h1>
            <h2>Mata Kuliah <?=$matkul['nama']?></h2>
        </section>
        <section class="mb-5">
            <div class="accordion" id="menuPanel">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <span class="material-icons">description</span>
                            <span>Detail Kelas</span>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <table class="table">
                                <tr>
                                    <th>Kode Kelas</th>
                                    <td><?=$kelas['kode']?></td>
                                </tr>
                                <tr>
                                    <th>Nama Kelas</th>
                                    <td><?=$kelas['nama']?></td>
                                </tr>
                                <tr>
                                    <th>Mata Kuliah</th>
                                    <td><?=$matkul['nama']?> - <?=$matkul['kode']?></td>
                                </tr>
                                <tr>
                                    <th>Kapasitas Kelas</th>
                                    <td><?=$kelas['kapasitas']?> orang</td>
                                </tr>
                            </table>
                            <hr>
                            <form action="./kelas.php?kode=<?=$kelas['kode']?>" method="post">
                                <input type="hidden" name="kode_kls" value="<?=$kelas['kode']?>">
                                <input type="hidden" name="kode_mk" value="<?=$matkul['kode']?>">
                                <button class="btn btn-danger d-inline-flex align-items-center gap-1" type="submit" name="hapus_kelas" onclick="return confirm('Anda yakin ingin menghapus Kelas ini beserta seluruh isinya (seperti pertemuan, materi, dll) ?')">
                                    <span class="material-icons">delete</span>
                                    <span>Hapus Kelas & Seluruh Isinya</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                            <span class="material-icons">list</span>
                            <span>Daftar Mahasiswa</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <?php
                                // mendapatkan data mahasiswa yang mengontrak kelas ini
                                $listMhs = call_procedure($conn, "list_mhs_di_kls('$kodeKelas')");

                                // memberikan respon gagal
                                if ($conn->errno !== 0) {
                                    $_SESSION['alert'] = array(
                                        'error' => TRUE,
                                        'message' => "Terjadi kesalahan! <i>$conn->error</i>"
                                    );
                                }
                            ?>
                            <?php if (sizeof($listMhs) > 0) : ?>
                                <p>Berikut adalah daftar mahasiswa yang mengontrak kelas ini.</p>
                                <div class="responsive-table">
                                    <table class="mt-3 table table-bordered table-striped table-hover">
                                        <thead class="text-center">
                                            <tr>
                                                <th scope="col">NIM</th>
                                                <th scope="col">Nama</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($listMhs as $mhs) : ?>
                                                <tr>
                                                    <td class="text-center"><?=$mhs['nim']?></td>
                                                    <td><?=$mhs['nama_lengkap']?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else : ?>
                                <p class="p-3 border bg-light">Belum ada mahasiswa yang mengontrak kelas ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                            <span class="material-icons">edit</span>
                            <span>Ubah Kelas</span>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <p>Silahkan ubah data di bawah ini jika memang diperlukan perubahan, lalu klik tombol <b>"Simpan Perubahan"</b>.</p>
                            <hr>
                            <form action="./kelas.php?kode=<?=$kelas['kode']?>" method="post">
                                <article class="mb-3">
                                    <label class="form-label" for="kodeKelas">Kode Kelas :</label>
                                    <input class="form-control" type="text" name="kode" id="kodeKelas" value="<?=$kelas['kode']?>" required disabled>
                                    <div class="form-text">Kode Kelas tidak dapat diubah.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="matkul">Mata Kuliah :</label>
                                    <input class="form-control" type="text" name="matkul" id="matkul" value="<?=$matkul['kode']?> - <?=$matkul['nama']?>" required disabled>
                                    <div class="form-text">Data Mata Kuliah tidak dapat diubah.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="namaKelas">Nama Kelas :</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?=substr($kelas['nama'], 0, 1)?></span>
                                        <input class="form-control" type="text" name="nama" id="namaKelas" value="<?=substr($kelas['nama'], 1, 1)?>" pattern="[A-Z]{1}" title="Masukkan 1 karakter huruf kapital" required>
                                    </div>
                                    <div class="form-text">Nama Kelas terdiri dari 2 karakter yang diawali dengan nomor semester. Masukkan 1 karakter huruf.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="kapasitas">Kapasitas :</label>
                                    <input class="form-control" type="number" min="1" name="kapasitas" id="kapasitas" value="<?=$kelas['kapasitas']?>" required>
                                    <div class="invalid-feedback">Kapasitas harus diisi dengan angka yang lebih dari 0 (nol).</div>
                                    <div class="form-text">Kapasitas kelas diisi dengan jumlah maksimal mahasiswa yang dapat ditampung.</div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="ubah_kelas" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">save</span>
                                        <span>Simpan Perubahan</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button d-inline-flex gap-2 align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                            <span class="material-icons">add</span>
                            <span>Buat Pertemuan Baru</span>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#menuPanel">
                        <div class="accordion-body">
                            <p>Silahkan <b>lengkapi kolom-kolom di bawah ini</b> sesuai petunjuk yang tersedia.</p>
                            <hr>
                            <form action="./kelas.php?kode=<?=$kodeKelas?>" method="post">
                                <?php
                                    // pertemuan-pertemuan yang sudah dibuat
                                    $meetingExists = call_procedure($conn, "already_exists_meeting('$kodeKelas')");
                                    $meetingList = array();
                                    foreach ($meetingExists as $row => $field) {
                                        array_push($meetingList, $field['nomor_pert']);
                                    }
                                ?>
                                <article class="mb-3">
                                    <label class="form-label" for="nomorPert">Pilih Nomor Pertemuan :</label>
                                    <select class="form-select" name="nomor_pert" id="nomorPert" required>
                                        <option value="" disabled>-- Pilih Nomor Pertemuan --</option>
                                        <?php for ($i = 1; $i <= $matkul['jml_pertemuan']; $i++) : ?>
                                            <?php if (!in_array($i, $meetingList)) : ?>
                                                <option value="<?=$i?>"><?=$i?></option>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="form-text">Nomor yang sudah pernah digunakan tidak dapat digunakan lagi.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="topik">Topik :</label>
                                    <input class="form-control" type="text" name="topik" id="topik" required>
                                    <div class="invalid-feedback">Topik harus tidak boleh kosong.</div>
                                    <div class="form-text">Masukkan topik yang akan dibahas pada pertemuan ini.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="deskripsi">Deskripsi (opsional) :</label>
                                    <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="1"></textarea>
                                    <div class="form-text">Masukkan deskripsi mengenai topik yang akan dibahas pada pertemuan ini.</div>
                                </article>
                                <article class="mb-3">
                                    <label class="form-label" for="tglAkses">Tanggal Akses :</label>
                                    <div class="input-group">
                                        <input class="form-control" type="date" name="tgl_akses" id="tglAkses" required>
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
                                        <input class="form-control" type="time" name="jam_akses" id="jamAkses" required>
                                        <span class="input-group-text" title="Pukul 14.30 (Sore)">Contoh: 02:30 PM</span>
                                    </div>
                                    <div class="form-text">
                                        Pilih pukul berapa pertemuan ini mulai bisa diakses oleh mahasiswa.<br>
                                        <b>Format penulisan: [Jam:Menit] diikuti AM atau PM.</b><br>
                                        AM = Pagi, PM = Siang/Sore/Malam.
                                    </div>
                                </article>
                                <article class="mb-3 d-flex gap-2">
                                    <button id="tambahBrg" type="submit" name="buat_pertemuan" class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons">add</span>
                                        <span>Buat Pertemuan Baru</span>
                                    </button>
                                </article>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </section>
        <section class="mb-4">
            <h2>Daftar Pertemuan</h2>
            <?php
                // mendapatkan data pertemuan pada kelas ini
                $meetingResult = $conn->query("SELECT * FROM Pertemuan WHERE kelas='$kodeKelas' ORDER BY nomor_pert");

                // memberikan respon gagal
                if (!$meetingResult) {
                    $_SESSION['alert'] = array(
                        'error' => TRUE,
                        'message' => "Terjadi kesalahan! <i>$conn->error</i>"
                    );
                }
            ?>
            <?php if ($meetingResult && $meetingResult->num_rows > 0) : ?>
                <div class="responsive-table">
                    <table class="mt-3 table table-bordered table-striped table-hover">
                        <thead class="text-center">
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Pertemuan Ke-</th>
                                <th scope="col">Topik</th>
                                <th scope="col">Waktu Akses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pertemuan = $meetingResult->fetch_assoc()) : ?>
                                <tr class="pertemuan" data-link="./pertemuan.php?kode=<?=$pertemuan['kode']?>">
                                    <td><?=$pertemuan['kode']?></td>
                                    <td class="text-center"><?=$pertemuan['nomor_pert']?></td>
                                    <td><?=$pertemuan['topik']?></td>
                                    <td><?=$pertemuan['waktu_akses']?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="p-3 border bg-light">Anda belum membuat pertemuan apapun untuk kelas ini.</p>
            <?php endif; ?>
        </section>
        <section>
            <a href="./matkul.php?kode=<?=$matkul['kode']?>" class="mt-3 btn btn-primary d-flex align-items-center justify-content-center gap-2">
                <span class="material-icons">arrow_back</span>
                <span>Kembali</span>
            </a>
        </section>
    </main>
    <script>
        const meetings = document.querySelectorAll('.pertemuan');
        meetings.forEach((meeting) => {
            meeting.addEventListener('click', () => {
                window.location.href = meeting.getAttribute('data-link');
            })
        });
    </script>
</body>
</html>
