<?php
// insert_contact.php

session_start();
// 1. Panggil koneksi database
require_once 'db.php';

// 2. Cek apakah data dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $nama = $_POST['NAMA'];
    $telepon = $_POST['TELEPON'];
    $gmail    = $_POST['GMAIL'];

    try {
        // 3. Query SQL menggunakan prepared statement (aman dari SQL Injection)
        $sql = "INSERT INTO contact (NAMA, TELEPON, GMAIL) 
                VALUES (:NAMA, :TELEPON, :GMAIL)";
        
        $stmt = $conn->prepare($sql);

        // 4. Eksekusi
        $stmt->execute([
            ':NAMA' => $nama,
            ':TELEPON'  => $telepon,
            ':GMAIL'    => $gmail,
        ]);

        // 5. Redirect setelah berhasil simpan
        echo "<script>alert('Berhasil Menambahkan kontak'); window.location.href='../contact.html';</script>";
        exit();

    } catch (PDOException $e) {
        echo "Mohon lengkapi semua data sebelum melanjutkan." . $e->getMessage();
    }
}
?>
