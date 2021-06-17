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
                $fileDestination = '../db/'.$newFileName;                       // lokasi tujuan penyimpanan file

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
            if (file_exists("../db/$namaFile")) {
                unlink("../db/$namaFile");
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
                        <td><a href="../db/<?=$tugas['lampiran']?>" target="_blank"><?=$tugas['lampiran']?></a></td>
                    <?php endif; ?>
                </tr>
                <form action="../page/meeting.php?kelas=<?=$kodeKelas?>&pertemuan=<?=$noPertemuan?>&menu=Tugas" method="post" enctype="multipart/form-data">
                    <?php if (!empty($submitTugas)) : ?>
                        <tr>
                            <th>Preview Tugas</th>
                            <td><a href="../db/<?=$submitTugas['file_tugas']?>" target="_blank" rel="noopener noreferrer"><?=$submitTugas['file_tugas']?></a></td>
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
                                <form action="../page/meeting.php?kode=<?=$kodeMeeting?>" method="post">
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
