<?php
// ============================================
// REMINO - NEW PASSWORD (ENTERPRISE SECURE FINAL)
// ============================================

// DEBUG (MATIKAN DI PRODUCTION)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

$error = "";

/* =====================================================
   HARD ACCESS LOCK (ANTI DIRECT URL + ANTI REPLAY)
===================================================== */
$accessValid = (
    isset($_SESSION['RESET_USER_ID']) &&
    isset($_SESSION['OTP_VERIFIED']) &&
    $_SESSION['OTP_VERIFIED'] === true &&
    isset($_SESSION['RESET_STEP']) &&
    $_SESSION['RESET_STEP'] === 'OTP_OK'
);

if (!$accessValid) {

    // TOTAL SESSION & COOKIE WIPE
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header("Location: forgot_password.php");
    exit;
}

$userID = $_SESSION['RESET_USER_ID'];

/* =====================================================
   PROCESS NEW PASSWORD
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password === "" || $confirm === "") {
        $error = "Password tidak boleh kosong!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak sama!";
    } else {

        try {
            // HASH PASSWORD
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // UPDATE PASSWORD
            $stmt = $conn->prepare("
                UPDATE USERS
                SET PASSWORD = :password
                WHERE ID_USER = :uid
                LIMIT 1
            ");
            $stmt->execute([
                ':password' => $hashed,
                ':uid'      => $userID
            ]);

            // DELETE OTP RECORD (ANTI REUSE TOTAL)
            $conn->prepare("
                DELETE FROM restore_account
                WHERE USER_ID = :uid
            ")->execute([
                ':uid' => $userID
            ]);

            // FINAL SESSION DESTROY
            $_SESSION = [];
            session_destroy();

            header("Location: login.php?reset=success");
            exit;

        } catch (PDOException $e) {
            $error = "Gagal menyimpan password baru!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Remino</title>

    <link rel="stylesheet" href="style/new_password_06.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

    <div class="left-section">
        <img src="asset/new.png" alt="logo-remino" class="logo-small">
    </div>

    <div class="right-section">

        <h1 class="title">Set New Password</h1>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <label>New Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" class="btn-primary">
                Save New Password
            </button>

        </form>

        <p class="back-login">
            <a href="login.php">← Back to Login</a>
        </p>

    </div>
</div>

</body>
</html>
