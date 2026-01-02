<?php
// ============================================
// REMINO - FORGOT PASSWORD (SECURE FINAL FIX)
// ============================================

// DEBUG (MATIKAN DI PRODUCTION)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

$error   = "";
$success = "";

/* =====================================================
   HARD RESET SESSION (HANYA AKSES PERTAMA KALI)
   ===================================================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    !isset($_GET['sent']) &&
    !isset($_SESSION['RESET_FLOW_STARTED'])
) {
    // Bersihkan session lama
    session_unset();
    session_destroy();
    session_start();

    // Penanda bahwa flow reset sudah dimulai
    $_SESSION['RESET_FLOW_STARTED'] = true;
}

/* =====================================================
   ALERT SETELAH OTP TERKIRIM
   ===================================================== */
if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $success = "OTP berhasil dikirim ke email Anda.";
}

/* =====================================================
   SEND OTP
   ===================================================== */
if (isset($_POST['send_otp'])) {

    $gmail = trim($_POST['email'] ?? '');

    if ($gmail === "") {
        $error = "Email tidak boleh kosong!";
    } else {

        // Cek email terdaftar
        $stmt = $conn->prepare("
            SELECT ID_USER 
            FROM USERS 
            WHERE GMAIL = :gmail 
            LIMIT 1
        ");
        $stmt->execute([':gmail' => $gmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Email tidak terdaftar!";
        } else {

            $userID = $user['ID_USER'];

            // Cek OTP aktif
            $otpCheck = $conn->prepare("
                SELECT ID
                FROM restore_account
                WHERE USER_ID = :uid
                AND EXPIRED_AT > NOW()
                LIMIT 1
            ");
            $otpCheck->execute([':uid' => $userID]);

            if ($otpCheck->fetch()) {
                $error = "OTP masih aktif. Harap tunggu beberapa menit.";
            } else {

                // Set session reset (BELUM OTP VERIFIED)
                $_SESSION['RESET_USER_ID'] = $userID;
                $_SESSION['RESET_EMAIL']   = $gmail;
                unset($_SESSION['OTP_VERIFIED']);
                unset($_SESSION['RESET_STEP']);

                // Jalankan binary OTP (Go)
                $escapedEmail = escapeshellarg($gmail);
                $cmd = "/www/wwwroot/remino.online/program/otp $escapedEmail > /dev/null 2>&1 &";
                exec($cmd);

                header("Location: forgot_password.php?sent=1");
                exit;
            }
        }
    }
}

/* =====================================================
   CHECK OTP
   ===================================================== */
if (isset($_POST['check_otp'])) {

    $otp    = trim($_POST['otp'] ?? '');
    $userID = $_SESSION['RESET_USER_ID'] ?? null;

    if ($otp === "") {
        $error = "OTP tidak boleh kosong!";
    } elseif (!$userID) {
        $error = "Session reset tidak valid. Silakan ulangi.";
    } else {

        $stmt = $conn->prepare("
            SELECT ID
            FROM restore_account
            WHERE USER_ID = :uid
            AND OTP_CODE = :otp
            AND EXPIRED_AT > NOW()
            ORDER BY ID DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':uid' => $userID,
            ':otp' => $otp
        ]);

        if ($stmt->fetch()) {

            // OTP VALID → KUNCI AKSES KE STEP BERIKUTNYA
            $_SESSION['OTP_VERIFIED'] = true;
            $_SESSION['RESET_STEP']   = 'OTP_OK';

            header("Location: new_password.php");
            exit;

        } else {
            $error = "OTP salah atau sudah kadaluarsa!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Remino</title>

    <link rel="stylesheet" href="style/forgot_password_02.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

<!-- LEFT -->
<div class="left-section">
    <img src="asset/no_bg.png" alt="logo-remino" class="logo-small">
</div>

<!-- RIGHT -->
<div class="right-section">

    <h1 class="title">Reset Password</h1>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">

        <!-- EMAIL -->
        <label>Email Address</label>
        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($_SESSION['RESET_EMAIL'] ?? '') ?>"
            placeholder="Email Address"
            required
        >

        <!-- OTP -->
        <label>Enter OTP Code</label>

        <div class="otp-container">
            <input
                type="text"
                name="otp"
                placeholder="6 Digit OTP"
                maxlength="6"
            >

            <button type="submit" name="send_otp" class="otp-btn">
                Send OTP
            </button>
        </div>

        <button type="submit" name="check_otp" class="btn-primary">
            Check OTP
        </button>

    </form>

    <p class="back-login">
        <a href="login.php">← Back to Login</a>
    </p>

</div>
</div>

</body>
</html>
