<?php
// ========================
// SIGNUP.PHP - REMINO
// ========================

// DEBUG (hapus di production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Koneksi DB
require_once 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['USERNAME'] ?? '');
    $gmail    = trim($_POST['GMAIL'] ?? '');
    $password = $_POST['PASSWORD'] ?? '';

    // ========================
    // VALIDASI INPUT
    // ========================
    if ($username === "" || $gmail === "" || $password === "") {
        $error = "Semua field wajib diisi!";
    } elseif (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {

        try {
            // ========================
            // CEK DUPLIKASI USER
            // ========================
            $check = $conn->prepare("
                SELECT ID_USER FROM USERS
                WHERE USERNAME = :username OR GMAIL = :gmail
                LIMIT 1
            ");
            $check->execute([
                ':username' => $username,
                ':gmail'    => $gmail
            ]);

            if ($check->rowCount() > 0) {
                $error = "Username atau Email sudah terdaftar!";
            } else {

                // ========================
                // HASH PASSWORD
                // ========================
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // ========================
                // INSERT USER BARU
                // ========================
                $stmt = $conn->prepare("
                    INSERT INTO USERS (USERNAME, GMAIL, PASSWORD)
                    VALUES (:username, :gmail, :password)
                ");
                $stmt->execute([
                    ':username' => $username,
                    ':gmail'    => $gmail,
                    ':password' => $hashedPassword
                ]);

                // ========================
                // REDIRECT KE LOGIN
                // ========================
                header("Location: login.php?register=success");
                exit;
            }

        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - Remino</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style/signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

<!-- LEFT SECTION -->
<div class="left-section">
    <img src="asset/no_bg.png" alt="logo-remino" class="logo-small">
</div>

<!-- RIGHT SECTION -->
<div class="right-section">

    <h1 class="title">Sign Up</h1>

    <!-- ERROR MESSAGE -->
    <?php if (!empty($error)): ?>
        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <label>Username</label>
        <input
            type="text"
            name="USERNAME"
            placeholder="Enter Username"
            required
        >

        <label>Email</label>
        <input
            type="email"
            name="GMAIL"
            placeholder="Enter Email"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="PASSWORD"
            placeholder="Enter Password"
            required
        >

        <button type="submit" class="btn-primary">
            Create Account
        </button>

    </form>

    <p class="back-login">
        Already have an account?
        <a href="login.php">Login</a>
    </p>

</div>
</div>

</body>
</html>
