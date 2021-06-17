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
