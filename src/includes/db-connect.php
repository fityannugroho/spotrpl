<?php
    require 'Database.php';

    $serverName = 'localhost';
    $username = 'root';
    $password = '';

    $conn = new Database($serverName, $username, $password);
    $conn->select_db('spotrpl') or die('Database tidak ditemukan');
?>
