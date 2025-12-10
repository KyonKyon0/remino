<?php
// ========================
// SIGNUP PROCESS
// ========================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['USERNAME'] ?? '');
    $gmail    = trim($_POST['GMAIL'] ?? '');
    $password = $_POST['PASSWORD'] ?? '';

    if ($username === "" || $gmail === "" || $password === "") {
        $error = "Semua field wajib diisi!";
    } else {
        try {
            // ✅ CEK USERNAME / EMAIL SUDAH ADA ATAU BELUM
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
                // ✅ HASH PASSWORD
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // ✅ INSERT USER BARU
                $sql = "INSERT INTO USERS (USERNAME, GMAIL, PASSWORD)
                        VALUES (:username, :gmail, :password)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':gmail'    => $gmail,
                    ':password' => $hashedPassword
                ]);

                // ✅ BERHASIL → KE LOGIN
                header("Location: login.php?register=success");
                exit;
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
    <title>Remino - Sign Up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style/signup.css">
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
        <h2 class="login-title">Sign Up</h2>

        <!-- ERROR -->
        <?php if (!empty($error)): ?>
            <div style="color:red; margin-bottom:15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- SIGNUP FORM -->
        <form class="signup-form" method="POST" action="">
            <label>Email</label>
            <input type="email" name="GMAIL" required>

            <label>Username</label>
            <input type="text" name="USERNAME" required>

            <label>Password</label>
            <input type="password" name="PASSWORD" required>

            <button type="submit" class="login-btn">Sign Up</button>
        </form>

        <div style="margin-top:15px;">
            Sudah punya akun? <a href="login.php">Log in</a>
        </div>
    </div>

</div>
</body>
</html>
