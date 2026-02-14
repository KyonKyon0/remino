<?php
// database/db.php

$host = "localhost";
$username = "root";       
$password = "";           
$dbname = "remino_db"; 

try {
    // Membuat koneksi dengan PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Mengatur mode error agar jika ada salah, PHP ngasih tau (Exception)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opsional: Cek koneksi (matikan ini nanti saat sudah production)
    // echo "Koneksi berhasil!"; 
    
} catch(PDOException $e) {
    // Jika gagal, tampilkan pesan error
    die("Koneksi gagal: " . $e->getMessage());
}
?>
