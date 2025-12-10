<?php
session_start();

/* =========================
   PROTEKSI HALAMAN
   ========================= */
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Data user dari session
$userId   = $_SESSION['USER_ID'] ?? '-';
$username = $_SESSION['USERNAME'] ?? 'USER';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remino - Home Page</title>

    <link rel="stylesheet" href="../style/home.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <div class="nav-logo">
            <img src="../asset/Logo tanpa Background ada buletan.png" alt="Remino Logo">
        </div>
        <ul class="nav-links">
            <li><a href="home.php" class="active">Home</a></li>
            <li><a href="task.php">Task</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </div>

    <div class="nav-right">
        <!-- LOGOUT -->
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-user-circle"></i> Log Out
        </a>
    </div>
</nav>

<div class="main-container">

    <div class="left-section">
        <div class="hero-illustration">
            <img src="../asset/Elemen Home.png" alt="Smart Reminding Illustration">
        </div>

        <div class="hero-text">
            <h1>SMART REMINDING SYSTEM</h1>
            <p>By Icikiwir Core Team</p>
        </div>

        <div class="user-info-cards">
            <div class="info-card">
                <div class="icon-box"><i class="fas fa-id-card"></i></div>
                <div class="card-text">
                    <small>USER ID : <?= htmlspecialchars($userId) ?></small>
                </div>
            </div>
            <div class="info-card">
                <div class="icon-box"><i class="fas fa-user"></i></div>
                <div class="card-text">
                    <small>USERNAME : <?= htmlspecialchars($username) ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="right-section">
        <div class="todo-header">
            <h2>TO-DO List</h2>
        </div>

        <div class="task-list">
            <p>[SYSTEM] TASK ANDA KOSONG.</p>
        </div>

        <button class="edit-btn" onclick="window.location.href='task.html';">EDIT</button>
    </div>

</div>

</body>
</html>
