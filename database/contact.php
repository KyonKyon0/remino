<?php
// insert_contact.php

// 1. Panggil koneksi database
require_once 'db.php';

// 2. Cek apakah data dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $whatsapp = $_POST['WHATSAPP'];
    $tanggal  = $_POST['TANGGAL'];
    $gmail    = $_POST['GMAIL'];
    $noted    = $_POST['NOTED'];

    try {
        // 3. Query SQL menggunakan prepared statement (aman dari SQL Injection)
        $sql = "INSERT INTO contacts (WHATSAPP, TANGGAL, GMAIL, NOTED) 
                VALUES (:WHATSAPP, :TANGGAL, :GMAIL, :NOTED)";
        
        $stmt = $conn->prepare($sql);

        // 4. Eksekusi
        $stmt->execute([
            ':WHATSAPP' => $whatsapp,
            ':TANGGAL'  => $tanggal,
            ':GMAIL'    => $gmail,
            ':NOTED'    => $noted
        ]);

        // 5. Redirect setelah berhasil simpan
        echo "<script>alert('Berhasil Menambahkan kontak'); window.location.href='../contact_list.html';</script>";
        exit();

    } catch (PDOException $e) {
        echo "Mohon lengkapi semua data sebelum melanjutkan." . $e->getMessage();
    }
}
?>
