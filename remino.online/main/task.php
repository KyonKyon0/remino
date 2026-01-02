<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

$userId = $_SESSION['USER_ID'];

/* =========================
   DELETE TASK
========================= */
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmtDel = $conn->prepare("DELETE FROM task WHERE id = ? AND user_id = ?");
    $stmtDel->execute([$deleteId, $userId]);
    header("Location: task.php");
    exit;
}

/* =========================
   EDIT DATA LOAD
========================= */
$editData = null;

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];

    $stmtEdit = $conn->prepare("
        SELECT id, contact_id, task, date_time, message, status
        FROM task
        WHERE id = ? AND user_id = ?
    ");
    $stmtEdit->execute([$editId, $userId]);
    $editData = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if (!$editData) {
        header("Location: task.php");
        exit;
    }
}

/* =========================
   INSERT / UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'] ?? '';
    $contact_id = $_POST['contact_id'] ?? null;
    $date_time = $_POST['date_time'] ?? null;
    $message = $_POST['message'] ?? '';

    // UPDATE
    if (isset($_POST['task_id']) && $_POST['task_id'] !== '') {
        $taskId = (int)$_POST['task_id'];

        // Ambil status lama agar user tidak bisa ubah
        $stmtStatus = $conn->prepare("SELECT status FROM task WHERE id = ? AND user_id = ?");
        $stmtStatus->execute([$taskId, $userId]);
        $oldStatus = $stmtStatus->fetchColumn();

        $stmtU = $conn->prepare("
            UPDATE task 
            SET task = ?, contact_id = ?, date_time = ?, message = ?, status = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmtU->execute([$task, $contact_id, $date_time, $message, $oldStatus, $taskId, $userId]);
    } 
    // INSERT
    else {
        $stmt1 = $conn->prepare("
            INSERT INTO task (user_id, contact_id, task, date_time, message, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt1->execute([$userId, $contact_id, $task, $date_time, $message]);
    }

    header("Location: task.php");
    exit;
}


/* CONTACT LIST (Dropdown) */
$stmtContact = $conn->prepare("SELECT ID, NAMA, GMAIL, TELEPON FROM contact WHERE user_id = ? ORDER BY NAMA ASC");
$stmtContact->execute([$userId]);
$contacts = $stmtContact->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TASK LIST (LOGIKA SEARCH)
========================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT 
        t.id,
        t.task, 
        t.status, 
        t.date_time,
        c.NAMA AS contact_name
    FROM task t
    JOIN contact c ON t.contact_id = c.ID
    WHERE t.user_id = ?
";

$params = [$userId];

if (!empty($search)) {
    $sql .= " AND t.task LIKE ? ";
    $params[] = "%" . $search . "%";
}

$sql .= "
    ORDER BY 
        CASE 
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'sent' THEN 2
            ELSE 3
        END,
        t.date_time ASC
";

$stmtTask = $conn->prepare($sql);
$stmtTask->execute($params);
$taskList = $stmtTask->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style/task_update_02.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <title>Remino - Task</title>
  
  <style>
      /* 1. SETUP GLOBAL & TEXTAREA */
      ::-webkit-scrollbar { width: 8px; height: 6px; }
      ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
      ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
      ::-webkit-scrollbar-thumb:hover { background: #555; }

      /* 2. SETUP KHUSUS LIST TASK */
      .list-container::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); }
      .list-container::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.4) !important; }
      .list-container::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.8) !important; }
      .contact-row div::-webkit-scrollbar-track { background: transparent; }
      .contact-row div::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.4) !important; }
      textarea { resize: none; }

      /* =========================================
         3. DESIGN INPUT TEXT, DATE & TEXTAREA
      ========================================= */
      .task-form input[type="text"],
      .task-form input[type="datetime-local"],
      .task-form textarea {
          width: 100%;
          padding: 12px 15px;
          margin: 8px 0;
          display: inline-block;
    
          /* Background Putih Polos & Border Abu */
          background-color: #ffffff; 
          border: 1px solid #ccc; 
          border-radius: 10px;
    
          box-sizing: border-box;
          font-size: 14px;
          color: #333;
          font-family: inherit;
    
          transition: all 0.3s ease;
      }

      /* HOVER: Border jadi hijau */
      .task-form input[type="text"]:hover,
      .task-form input[type="datetime-local"]:hover,
      .task-form textarea:hover {
          border-color: #016B61;
      }

      /* FOKUS: Border hijau + Glow hijau halus */
      .task-form input[type="text"]:focus,
      .task-form input[type="datetime-local"]:focus,
      .task-form textarea:focus {
          outline: none; /* Hilangkan outline biru default browser */
          border-color: #016B61;
          box-shadow: 0 0 0 4px rgba(1, 107, 97, 0.1); /* Glow hijau seperti dropdown */
          background-color: #ffffff;
      }

      /* =========================================
         4. DESIGN DROPDOWN: CLEAN WHITE (Sesuai Pattern)
      ========================================= */
      .task-form select {
          width: 100%;
          padding: 12px 15px; /* Spasi standar agar sama dengan input lain */
          margin: 8px 0;      /* Jarak atas bawah */
          display: inline-block;
          
          /* KUNCI: Background Putih Polos & Border Abu */
          background-color: #ffffff; 
          border: 1px solid #ccc; 
          border-radius: 10px; /* Lengkungan sudut */
          
          box-sizing: border-box;
          font-size: 14px;
          color: #333; /* Warna teks gelap */
          cursor: pointer;
          font-family: inherit;

          /* Menghilangkan panah bawaan browser */
          appearance: none; 
          -webkit-appearance: none;
          -moz-appearance: none;

          /* Panah Custom Hijau (Agar tetap ada nuansa tema) */
          background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23016B61%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
          background-repeat: no-repeat;
          background-position: right 15px center;
          background-size: 12px auto;
          
          transition: all 0.3s ease;
      }

      /* HOVER: Hanya ubah warna garis pinggir jadi hijau */
      .task-form select:hover {
          border-color: #016B61;
      }

      /* FOKUS (Saat diklik): Garis hijau + Glow halus */
      .task-form select:focus {
          outline: none;
          border-color: #016B61; /* Border jadi Hijau */
          box-shadow: 0 0 0 4px rgba(1, 107, 97, 0.1); /* Glow Hijau Sangat Halus */
          background-color: #ffffff; /* Background TETAP Putih */
      }
      
      /* OPSI DALAM: Pastikan background putih */
      .task-form select option {
          background-color: white;
          color: #333;
          padding: 10px;
      }

      /* =========================================
         5. TEXTAREA KHUSUS (Height lebih tinggi)
      ========================================= */
      .task-form textarea {
          min-height: 100px;
          padding-top: 12px;
      }

      /* =========================================
         6. COMPACT FORM LAYOUT (No Scrolling)
      ========================================= */
      .task-form {
          margin-top: 0;
      }

      .task-form label {
          margin-top: 5px;
          margin-bottom: 2px;
          display: block;
          font-size: 14px;
      }

      .task-form input[type="text"],
      .task-form input[type="datetime-local"],
      .task-form select {
          margin: 3px 0 5px 0;
      }

      .task-form textarea {
          margin: 3px 0 5px 0;
          min-height: 60px;
      }

      .submit-btn {
          margin-top: 8px;
      }
  </style>
  
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
            <li><a href="home.php">Home</a></li>
            <li><a href="#" class="active">Task</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </div>
    <div class="nav-right">
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-user-circle"></i> Log Out
        </a>
    </div>
</nav>

<main class="task-container">

<section class="form-section" style="height: 630px !important; padding: 20px !important; box-sizing: border-box !important; overflow: hidden !important;">
  
  <div style="
      width: 100%;
      background-color: #016B61;
      color: white;
      padding: 15px 0;
      border-radius: 10px;
      text-align: center;
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 8px;
      letter-spacing: 1px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  ">
      <?= $editData ? "EDIT TASK" : "ADD TASK" ?>
  </div>

  <form class="task-form" method="POST" action="task.php">
    <label>Title</label>

    <input type="text" placeholder="Title" name="task" 
           value="<?= $editData ? htmlspecialchars($editData['task']) : '' ?>" required>

    <label>Pilih Contact</label>
    <select name="contact_id" required>
        <option value="">-- pilih contact --</option>
        <?php foreach ($contacts as $c): ?>
        <option value="<?= $c['ID'] ?>" 
            <?= ($editData && $editData['contact_id'] == $c['ID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['NAMA']) ?> (<?= htmlspecialchars($c['GMAIL']) ?>) | <?= htmlspecialchars($c['TELEPON']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <label>Date</label>
    <input type="datetime-local" name="date_time" 
           value="<?= $editData ? date('Y-m-d\TH:i', strtotime($editData['date_time'])) : '' ?>" required>

    <label>Message</label>
    <textarea name="message"><?= $editData ? htmlspecialchars($editData['message']) : '' ?></textarea>

    <?php if ($editData): ?>
    <input type="hidden" name="task_id" value="<?= htmlspecialchars($editData['id']) ?>">
    <?php endif; ?>

    <button type="submit" class="submit-btn">
        <?= $editData ? "Update" : "Submit" ?>
    </button>
  </form>
</section>

<section class="contact-section" style="height: 630px !important; display: block !important; padding: 20px !important; box-sizing: border-box !important;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <h2 class="title" style="margin: 0;">TASK LIST</h2>
      
      <form method="GET" action="task.php" style="position: relative; width: 250px;">
          <input 
            type="text" 
            name="search" 
            placeholder="Type and press Enter..." 
            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
            style="
                width: 100%; 
                padding: 10px 45px 10px 20px; 
                border-radius: 50px; 
                border: none; 
                outline: none; 
                font-size: 14px;
                background-color: white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            "
          />
          <button type="submit" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0;">
              <img src="../asset/search-icon.png" alt="Search" style="width: 18px; opacity: 0.5;">
          </button>
      </form>
  </div>

  <div class="contact-row header" style="
        display: grid; 
        grid-template-columns: 2fr 2fr 1fr 140px; 
        gap: 10px; 
        margin-bottom: 10px; 
        padding-right: 26px; 
        align-items: center;
        font-weight: bold;
    ">
        <span>Task</span>
        <span>Contact</span>
        <span>Status</span>
        <span style="text-align: right; padding-right: 42px;">Action</span>
    </div>

    <div class="list-container" style="max-height: 493px !important; overflow-y: auto !important; padding-right: 5px !important;">
            
        <?php foreach ($taskList as $t): ?>
        <div class="contact-row" style="
            display: grid; 
            grid-template-columns: 2fr 2fr 1fr 140px; 
            gap: 10px; 
            margin-bottom: 0px; 
            padding-bottom: 10px;
            padding-right: 17px;
            padding-top: 10px;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        ">
            <div style="
                max-width: 100%; 
                overflow-x: auto; 
                white-space: nowrap; 
                padding-bottom: 3px; 
            ">
                <span style="font-weight: 500;">
                    <?= htmlspecialchars($t['task']) ?>
                </span>
            </div>
            
            <div style="max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?= htmlspecialchars($t['contact_name']) ?>
            </div>
            
            <span><?= htmlspecialchars($t['status']) ?></span>
    
            <span style="text-align: center;">
                <a href="task.php?edit=<?= (int)$t['id'] ?>"><i class="fa-solid fa-pen"></i></a> | 
                <a href="task.php?delete=<?= (int)$t['id'] ?>" 
                   onclick="return confirm('Hapus task ini?')"><i class="fa-solid fa-trash"></i></a>
            </span>
        </div>
        <?php endforeach; ?>
    
        <?php if (empty($taskList)): ?>
            <p style="text-align: center; padding: 15px; color: white; opacity: 0.7;">
                Data tidak ditemukan.
            </p>
        <?php endif; ?>
    
    </div>
  </div>
</section>

</main>

</body>
</html>