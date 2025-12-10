<?php
// =========================
// CONTACT PAGE + INSERT (PER USER)
// =========================
session_start();

// Proteksi halaman (harus login)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

require_once '../db.php';

$userId  = $_SESSION['USER_ID']; // ✅ USER ID LOGIN
$message = "";

// =========================
// INSERT CONTACT
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama    = trim($_POST['NAMA'] ?? '');
    $telepon = trim($_POST['TELEPON'] ?? '');
    $gmail   = trim($_POST['GMAIL'] ?? '');

    if ($nama === "" || $telepon === "" || $gmail === "") {
        $message = "Semua field wajib diisi!";
    } else {
        try {
            $sql = "INSERT INTO contact (USER_ID, NAMA, TELEPON, GMAIL)
                    VALUES (:USER_ID, :NAMA, :TELEPON, :GMAIL)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':USER_ID' => $userId,      // ✅ IKAT KE USER LOGIN
                ':NAMA'    => $nama,
                ':TELEPON' => $telepon,
                ':GMAIL'   => $gmail
            ]);

            header("Location: contact.php?success=1");
            exit;

        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remino - Contact</title>

    <link rel="stylesheet" href="style/contact.css">
    
    
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
            <li><a href="home.php">Home</a></li>
            <li><a href="task.html">Task</a></li>
            <li><a href="contact.php" class="active">Contact</a></li>
        </ul>
    </div>
    <div class="nav-right">
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-user-circle"></i> Log Out
        </a>
    </div>
</nav>

<!-- MAIN -->
<div class="container">

    <!-- CONTACT LIST -->
    <div class="contact-list">
        <div class="contact-header">
            <h2 class="black">CONTACT LIST</h2>
        </div>

        <div class="contact-column">
            <span>Name</span>
            <span>Nomor Telepon</span>
            <span>Gmail</span>
        </div>

        <?php
        // =========================
        // TAMPILKAN CONTACT MILIK USER LOGIN
        // =========================
        $stmt = $conn->prepare("
            SELECT * FROM contact 
            WHERE USER_ID = :USER_ID 
            ORDER BY ID DESC
        ");
        $stmt->execute([':USER_ID' => $userId]);

        foreach ($stmt as $row):
        ?>
        <div class="contact-item">
            <div class="dot"></div>
            <div class="item-text">
                <strong><?= htmlspecialchars($row['NAMA']) ?></strong>
            </div>
            <div><?= htmlspecialchars($row['TELEPON']) ?></div>
            <div><?= htmlspecialchars($row['GMAIL']) ?></div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- ADD CONTACT -->
    <div class="add-container">

        <div class="add-header">ADD CONTACT</div>

        <?php if (!empty($message)): ?>
            <div style="color:red; margin-bottom:10px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div style="color:green; margin-bottom:10px;">
                Contact berhasil ditambahkan
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form class="contact-form" method="POST">
            <div class="add-box">

                <label>Nama</label>
                <input type="text" name="NAMA" required>

                <label>No Telepon</label>
                <input type="text" name="TELEPON" required>

                <label>Gmail</label>
                <input type="email" name="GMAIL" required>

                <button class="shadow-submit" type="submit">Submit</button>

            </div>
        </form>
    </div>

</div>

</body>
</html>
