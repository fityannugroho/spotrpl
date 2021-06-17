<?php
    session_start();

    // menghancurkan atau menghapus sesi login
    session_unset();
    session_destroy();

    // mengarahkan ke halaman beranda
    header('location: ./index.php');
    exit;
?>
