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
$message = "";
$editData = null;

/* =====================
   DELETE CONTACT
===================== */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM contact 
        WHERE ID = :ID AND USER_ID = :USER_ID
    ");
    $stmt->execute([
        ':ID' => $id,
        ':USER_ID' => $userId
    ]);

    header("Location: contact.php");
    exit;
}

/* =====================
   EDIT MODE (AMBIL DATA)
===================== */
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $conn->prepare("
        SELECT * FROM contact
        WHERE ID = :ID AND USER_ID = :USER_ID
    ");
    $stmt->execute([
        ':ID' => $id,
        ':USER_ID' => $userId
    ]);

    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =====================
   ADD / UPDATE
===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama    = trim($_POST['NAMA']);
    $telepon = trim($_POST['TELEPON']);
    $gmail   = trim($_POST['GMAIL']);
    $id      = $_POST['ID'] ?? '';

    if ($nama === "" || $telepon === "" || $gmail === "") {
        $message = "Semua field wajib diisi!";
    } else {

        if ($id === "") {
            // ADD
            $stmt = $conn->prepare("
                INSERT INTO contact (USER_ID, NAMA, TELEPON, GMAIL)
                VALUES (:USER_ID, :NAMA, :TELEPON, :GMAIL)
            ");
            $stmt->execute([
                ':USER_ID' => $userId,
                ':NAMA'    => $nama,
                ':TELEPON' => $telepon,
                ':GMAIL'   => $gmail
            ]);
        } else {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE contact 
                SET NAMA=:NAMA, TELEPON=:TELEPON, GMAIL=:GMAIL
                WHERE ID=:ID AND USER_ID=:USER_ID
            ");
            $stmt->execute([
                ':NAMA'    => $nama,
                ':TELEPON' => $telepon,
                ':GMAIL'   => $gmail,
                ':ID'      => $id,
                ':USER_ID' => $userId
            ]);
        }

        header("Location: contact.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Remino - Contact</title>
    <link rel="stylesheet" href="style/contact_02.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
      .contact-items-wrapper::-webkit-scrollbar {
          width: 8px;
      }
      .contact-items-wrapper::-webkit-scrollbar-track {
          background: rgba(0, 0, 0, 0.1);
          border-radius: 10px;
      }
      .contact-items-wrapper::-webkit-scrollbar-thumb {
          background: rgba(255, 255, 255, 0.3);
          border-radius: 10px;
      }
      .contact-items-wrapper::-webkit-scrollbar-thumb:hover {
          background: rgba(255, 255, 255, 0.6);
      }

      /* =========================================
         HOVER & FOCUS EFFECTS UNTUK INPUT
      ========================================= */
      .contact-form input[type="text"],
      .contact-form input[type="email"] {
          width: 100%;
          padding: 12px 15px;
          margin: 8px 0;
          display: inline-block;
          
          /* Background Putih & Border Abu */
          background-color: #ffffff; 
          border: 1px solid #ccc; 
          border-radius: 10px;
          
          box-sizing: border-box;
          font-size: 14px;
          color: #333;
          font-family: inherit;
          
          transition: all 0.3s ease;
      }

      /* HOVER: Border hijau */
      .contact-form input[type="text"]:hover,
      .contact-form input[type="email"]:hover {
          border-color: #016B61;
      }

      /* FOKUS: Border hijau + Glow */
      .contact-form input[type="text"]:focus,
      .contact-form input[type="email"]:focus {
          outline: none;
          border-color: #016B61;
          box-shadow: 0 0 0 4px rgba(1, 107, 97, 0.1);
          background-color: #ffffff;
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
            <li><a href="task.php">Task</a></li>
            <li><a href="contact.php" class="active">Contact</a></li>
        </ul>
    </div>

    <div class="nav-right">
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-user-circle"></i> Log Out
        </a>
    </div>
</nav>

<div class="container">

<!-- ================= CONTACT LIST ================= -->
<div class="contact-list">

    <div class="contact-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 class="black" style="margin: 0;">CONTACT LIST</h2>

        <form method="GET" action="contact.php" style="position: relative; width: 250px;">
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
                "
            >
            <button type="submit" style="
                position: absolute; 
                right: 15px; 
                top: 50%; 
                transform: translateY(-50%); 
                background: none; 
                border: none; 
                cursor: pointer;
                padding: 0;
            ">
                <img src="../asset/search-icon.png" alt="Search" style="width: 17px; opacity: 0.5;">
            </button>
        </form>
    </div>

    <div class="contact-column" style="
        display: grid; 
        grid-template-columns: 15px 1.5fr 1.5fr 2fr 100px;
        gap: 10px; 
        font-weight: bold; 
        margin-bottom: 10px;
        padding-left: 20px;
        padding-right: 5px; 
        align-items: center;
    ">
        <span></span> <span>Name</span>
        <span style="text-align: right; padding-right: 106px;">Phone Number</span>
        <span style="text-align: right; padding-right: 122px;">Gmail</span>
        <span style="text-align: right; padding-right: 34px;">Action</span>
    </div>

    <div class="contact-items-wrapper" style="
        max-height: 400px; 
        overflow-y: auto;
        direction: rtl;
        padding-left: 5px; 
        display: flex; 
        flex-direction: column; 
        gap: 10px;
    "> 
        
        <?php
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sql = "SELECT * FROM contact WHERE USER_ID = :USER_ID";

        if (!empty($search)) {
            $sql .= " AND NAMA LIKE :search";
        }

        $sql .= " ORDER BY ID DESC";

        $stmt = $conn->prepare($sql);
        $params = [':USER_ID' => $userId];

        if (!empty($search)) {
            $params[':search'] = "%" . $search . "%";
        }

        $stmt->execute($params);

        foreach ($stmt as $row):
        ?>
        <div class="contact-item" style="
            direction: ltr;
            grid-template-columns:20px 1fr 1fr 1fr 80px;
        ">
            <div class="dot"></div>
            <div><strong><?= htmlspecialchars($row['NAMA']) ?></strong></div>
            <div><?= htmlspecialchars($row['TELEPON']) ?></div>
            <div><?= htmlspecialchars($row['GMAIL']) ?></div>

            <div style="display:flex; gap:12px;">
                <a href="?edit=<?= $row['ID'] ?>">
                    <i class="fa-solid fa-pen"></i>
                </a>
                <a href="?delete=<?= $row['ID'] ?>" onclick="return confirm('Hapus contact ini?')">
                    <i class="fa-solid fa-trash"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<!-- ================= FORM ================= -->
<div class="add-container">

    <div class="add-header">
        <?= $editData ? 'EDIT CONTACT' : 'ADD CONTACT' ?>
    </div>

    <?php if ($message): ?>
        <div style="color:red"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

        <form class="contact-form" method="POST">
            <div class="add-box">
    
                <input type="hidden" name="ID" value="<?= $editData['ID'] ?? '' ?>">
    
                <label>Nama</label>
                <input type="text" name="NAMA" placeholder="Enter your Contact Name"
                       value="<?= $editData['NAMA'] ?? '' ?>" required>
    
                <label>No Telepon</label>
                <input type="text" name="TELEPON" placeholder="Enter your Contact Telepon"
                       value="<?= $editData['TELEPON'] ?? '' ?>" required>
    
                <label>Gmail</label>
                <input type="email" name="GMAIL" placeholder="Enter your Contact Email"
                       value="<?= $editData['GMAIL'] ?? '' ?>" required>
    
                <button class="shadow-submit" type="submit">
                    Submit
                </button>
    
            </div>
        </form>

</div>

</div>

</body>
</html>
