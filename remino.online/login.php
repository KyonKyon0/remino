<?php
// ========================
// LOGIN_2.PHP - REMINO
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

// ========================
// PROSES LOGIN
// ========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // SESUAIKAN DENGAN FORM LOGIN_2
    $identifier = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

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
    <title>Login - Remino</title>

    <link rel="stylesheet" href="style/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

<!-- LEFT -->
   <div class="left-section">
        <div class="left-content">

            <!-- TOP -->
            <h1 class="welcome-title">Welcome To</h1>

            <!-- CENTER -->
            <img src="../asset/Logo tanpa Background ada buletan.png" alt="logo-remino" class="logo-small">  

            <!-- BOTTOM -->
            <h2 class="welcome-brand">BY ICIKIWIR CORE TEAM</h2>

        </div>
    </div>

<!-- RIGHT -->
<div class="right-section">

    <h1 class="title">Login</h1>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <label>Username or Email</label>
        <input 
            type="text" 
            name="email"
            placeholder="Username or Email"
            required
        >

        <label>Password</label>
        <input 
            type="password" 
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit" class="btn-primary">
            Login
        </button>

    </form>

    <p class="back-login">
        <a href="signup.php">No Account? Sign Up</a>
    </p>
    
    <p class="back-login">
        <a href="forgot_password.php">Forget Password?</a>
    </p

</div>
</div>

</body>
</html>
