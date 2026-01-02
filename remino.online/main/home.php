<?php
session_start();

/* =========================
   PAGE PROTECTION
   ========================= */
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// ENSURE DATABASE CONNECTION IS AVAILABLE
require_once '../db.php'; 

// User data from session
$userId    = $_SESSION['USER_ID'] ?? null;
$username = $_SESSION['USERNAME'] ?? 'USER';

/* =========================
   DATA RETRIEVAL & WIDGET COUNTS LOGIC
   ========================= */

$homeTasks = [];
$totalTasks = 0;
$totalContacts = 0;
$totalPendingTasks = 0; 
$totalSentTasks = 0;    

if ($userId) {
    try {
        // 1. Get Total Tasks (All Statuses)
        $stmtTotalTask = $conn->prepare("SELECT COUNT(id) FROM task WHERE user_id = ?");
        $stmtTotalTask->execute([$userId]);
        $totalTasks = $stmtTotalTask->fetchColumn();
        
        // 2. Get Total Pending Tasks (For List Header)
        $stmtTotalPendingTask = $conn->prepare("SELECT COUNT(id) FROM task WHERE user_id = ? AND status = 'pending'");
        $stmtTotalPendingTask->execute([$userId]);
        $totalPendingTasks = $stmtTotalPendingTask->fetchColumn();

        // 3. Get Total Sent Tasks (For New Widget)
        $stmtTotalSentTask = $conn->prepare("SELECT COUNT(id) FROM task WHERE user_id = ? AND status = 'sent'");
        $stmtTotalSentTask->execute([$userId]);
        $totalSentTasks = $stmtTotalSentTask->fetchColumn();

        // 4. Get Total Contacts
        $stmtTotalContact = $conn->prepare("SELECT COUNT(id) FROM contact WHERE user_id = ?");
        $stmtTotalContact->execute([$userId]);
        $totalContacts = $stmtTotalContact->fetchColumn();


        // 5. Query to get only the 5 closest PENDING tasks
        $sqlHomeTask = "
            SELECT 
                t.task, 
                t.date_time, 
                t.message,
                t.status, 
                c.NAMA AS contact_name,
                c.GMAIL AS contact_gmail,
                c.TELEPON AS contact_phone
            FROM task t
            JOIN contact c ON t.contact_id = c.ID
            WHERE t.user_id = ?
            AND t.status = 'pending' /* Filtered to PENDING only */
            ORDER BY t.date_time ASC
            LIMIT 5
        ";

        $stmtHomeTask = $conn->prepare($sqlHomeTask);
        $stmtHomeTask->execute([$userId]);
        $homeTasks = $stmtHomeTask->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in home.php: " . $e->getMessage());
        $homeTasks = []; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
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
             <a href="home.php">
                <img src="../asset/Logo tanpa Background ada buletan.png" alt="Remino Logo">
            </a>
        </div>
        <ul class="nav-links">
            <li><a href="home.php" class="active">Home</a></li>
            <li><a href="task.php">Task</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </div>

    <div class="nav-right">
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
            <h2>UPCOMING TASKS (<?= (int)$totalPendingTasks ?> PENDING)</h2> 
        </div>

        <div class="task-list">
            <?php if (empty($homeTasks)): ?>
                <div class="task-card" style="text-align: center; border-left: 5px solid #e74c3c;">
                    <p style="opacity: 0.8; font-weight: 500;">[SYSTEM] There are no Pending Tasks due soon.</p>
                </div>
            <?php else: ?>
                <?php foreach ($homeTasks as $task): 
                    // Since the query is filtered to PENDING, we can set the status directly
                    $statusClass = 'red'; 
                    $statusText = 'Pending';
                ?>
                    <div class="task-card">
                        <div class="card-header">
                            <span class="status-dot <?= $statusClass ?>"></span>
                            <h3><?= htmlspecialchars($task['task']) ?></h3>
                            
                            <div class="header-details">
                                <span class="badge red-text">
                                    DUE: <?= date('d M H:i', strtotime($task['date_time'])) ?>
                                </span>
                                <span class="status-label <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($task['message'])): ?>
                        <div class="card-body">
                            <p><i class="fas fa-file-alt"></i> Notes: <?= htmlspecialchars($task['message']) ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="card-footer" style="flex-wrap: wrap;">
                            <span style="flex: 100%; margin-bottom: 5px;"><i class="fas fa-user-tag"></i> Contact: <?= htmlspecialchars($task['contact_name']) ?></span>
                            <span style="flex: 1;"><i class="fas fa-phone"></i> <?= htmlspecialchars($task['contact_phone']) ?></span> <span style="flex: 1; text-align: right;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($task['contact_gmail']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="bottom-widgets">
            <div class="mini-widget">
                <div class="widget-icon primary-bg"><i class="fas fa-clipboard-list"></i></div>
                <div class="widget-info">
                    <span><?= (int)$totalTasks ?></span>
                    <small>Total Tasks</small>
                </div>
            </div>
            
            <div class="mini-widget">
                <div class="widget-icon green-bg"><i class="fas fa-paper-plane"></i></div>
                <div class="widget-info">
                    <span><?= (int)$totalSentTasks ?></span>
                    <small>Sent Tasks</small>
                </div>
            </div>

            <div class="mini-widget">
                <div class="widget-icon purple-bg"><i class="fas fa-address-book"></i></div>
                <div class="widget-info">
                    <span><?= (int)$totalContacts ?></span>
                    <small>Total Contacts</small>
                </div>
            </div>
        </div>
        
        <button class="edit-btn" onclick="window.location.href='task.php';">VIEW & MANAGE ALL TASKS</button>
    </div>

</div>

</body>
</html>
