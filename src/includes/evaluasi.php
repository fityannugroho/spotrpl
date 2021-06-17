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
            <a href="../page/exam.php?kode=<?=$ujian['kode']?>" class="btn">
                <i class="material-icons-outlined">task</i>
                Kerjakan Sekarang
            </a>
        </article>
        <?php $i++; ?>
    <?php endwhile; ?>
<?php else : ?>
    <p>Belum ada ujian untuk pertemuan ini.</p>
<?php endif; ?>
