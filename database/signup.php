<?php
// signup_process.php

// 1. Panggil koneksi database
require_once 'db.php';

// 2. Cek apakah form dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data dari form HTML
    $username = $_POST['USERNAME'];
    $gmail    = $_POST['GMAIL'];
    $password = $_POST['PASSWORD'];

    // 3. KEAMANAN: Hash password (Jangan simpan password asli/polos!)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Siapkan query SQL (Pakai :placeholder biar anti-hack/SQL Injection)
        $sql = "INSERT INTO users (GMAIL, USERNAME, PASSWORD) VALUES (:GMAIL, :USERNAME, :PASSWORD)";
        $stmt = $conn->prepare($sql);

        // Eksekusi simpan data
        $stmt->execute([
            ':USERNAME' => $username,
            ':GMAIL'    => $gmail,
            ':PASSWORD' => $hashed_password
        ]);

        // Jika berhasil, tampilkan pesan atau redirect
        header("Location: ../login.html");
        exit();

    } catch (PDOException $e) {
        // Cek jika error karena email/username sudah ada (Duplicate entry)
        if ($e->getCode() == 23000) {
            echo "<script>alert('Email atau Username sudah terdaftar!'); window.history.back();</script>";
        } else {
            echo "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
