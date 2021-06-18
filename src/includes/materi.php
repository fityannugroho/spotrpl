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
