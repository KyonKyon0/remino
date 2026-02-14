<?php
// login.php

// 1. Mulai Session (Wajib di paling atas untuk login)
session_start();

// 2. Panggil koneksi database
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data dari form (name="email" di HTML dipakai untuk username/email)
    $identifier = $_POST['GMAIL']; 
    $password   = $_POST['PASSWORD'];

    try {
        // 3. Cari user berdasarkan Email ATAU Username
        // Kita pakai satu input untuk cek ke dua kolom di database
        $sql = "SELECT * FROM users WHERE GMAIL = :ident OR USERNAME = :ident";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':ident' => $identifier]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Cek apakah user ketemu DAN password cocok
        if ($user && password_verify($password, $user['PASSWORD'])) {
            
            // LOGIN SUKSES!
            // Simpan data penting ke Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['USERNAME'] = $user['USERNAME'];
            $_SESSION['is_logged_in'] = true;

            // Redirect ke halaman utama (misal index.php)
            header("Location: ../home.html"); 
            exit();

        } else {
            // LOGIN GAGAL (Password salah atau user tidak ada)
            echo "<script>
                    alert('Username/Email atau Password salah!');
                    window.location.href = '../login.html';
                  </script>";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
