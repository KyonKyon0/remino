<?php
// ========================
// LOGIN.PHP - REMINO
// ========================

// Tampilkan error (HAPUS di production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session
session_start();

// Koneksi DB
require_once 'db.php';

$error = "";

// Jika sudah login, langsung ke home
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: main/home.php");
    exit;
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identifier = trim($_POST['GMAIL'] ?? '');
    $password   = $_POST['PASSWORD'] ?? '';

    if ($identifier === "" || $password === "") {
        $error = "Form tidak boleh kosong!";
    } else {
        try {
            $sql = "SELECT * FROM USERS 
                    WHERE GMAIL = :ident OR USERNAME = :ident
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':ident', $identifier);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['PASSWORD'])) {

                // ✅ LOGIN SUKSES
                $_SESSION['USER_ID'] = $user['ID_USER'];
                $_SESSION['USERNAME'] = $user['USERNAME'];
                $_SESSION['is_logged_in'] = true;

                header("Location: main/home.php");
                exit;

            } else {
                $error = "Username / Email atau Password salah!";
            }

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remino - Log In</title>

    <!-- CSS -->
    <link rel="stylesheet" href="style/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
<div class="container">

    <!-- LEFT PANEL -->
    <div class="welcome-panel">
        <div class="logo-placeholder">
            <img src="asset/Logo tanpa Background ada buletan.png" alt="logo-remino">
        </div>
        <h1 class="welcome-title">WELCOME TO REMINO</h1>
        <p class="welcome-subtitle">SMART REMINDING SYSTEM</p>
        <p class="team-signature">By Icikiwir Core Team</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-panel">

        <div class="signup-link">
            No Account? <a href="signup.php">Sign up</a>
        </div>

        <h2 class="login-title">Log in</h2>

        <!-- ERROR MESSAGE -->
        <?php if (!empty($error)): ?>
            <div style="color:red; margin-bottom:15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form class="login-form" method="POST" action="">
            <label for="GMAIL">Enter your username or email address</label>
            <input type="text" id="GMAIL" name="GMAIL" placeholder="Username atau Email" required>

            <label for="PASSWORD">Enter your password</label>
            <input type="password" id="PASSWORD" name="PASSWORD" placeholder="Password" required>

            <button type="submit" class="login-btn">Log in</button>
        </form>

    </div>
</div>
</body>
</html>
