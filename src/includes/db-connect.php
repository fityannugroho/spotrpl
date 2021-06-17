<?php
    $serverName = 'localhost';
    $username = 'root';
    $password = '';

    $conn = mysqli_connect($serverName, $username, $password);

    mysqli_select_db($conn, 'spotrpl') or die('Database tidak ditemukan');
?>
