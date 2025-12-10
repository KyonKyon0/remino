<?php
// database/db.php

$host = "127.0.0.1";
$username = "remino_admin";
$password = "REMINOictgroup2025";
$dbname = "remino_db";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Koneksi berhasil";
} catch (PDOException $e) {
    die("[ERROR] FAIL TO CONNECT: " . $e->getMessage());
}
