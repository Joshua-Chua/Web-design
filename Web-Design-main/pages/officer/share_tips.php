<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title) || empty($content)) {
        $error_msg = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO smart_tips (title, content, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $content, $user_id);
        
        if ($stmt->execute()) {
            $success_msg = "Tip shared successfully!";
        } else {
            $error_msg = "Error sharing tip. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability - Share Smart Tips</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <style>
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="text"], 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        .submit-btn {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .submit-btn:hover {
            background: #218838;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Share Smart Tips</span>

    <div class = "topbar-left breadcrumb">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>
        <span class = "breadcrumb-separator">/</span>
        <a href = "share_tips.php" class = "breadcrumb-link">Share Tips</a>
    </div>

    <div class = "topbar-right">
        <a href = "officer_profile.php" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "officer_profile.php">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "officer_main.php">Main Menu</a>
        <a href = "officer_monthly_report.php">Monthly Report</a>
        <a href = "#">Events</a>
        <a href = "../student/browse_tips.php">Smart Tips</a>
        
        <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('quizMenu', this)">
            Quiz <span class="arrow">&#9662;</span>
        </a>
        <div id="quizMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
            <a href="officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
        </div>

        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <h2>Share a New Smart Tip</h2>
        <p>Add a new tip to help the community save energy.</p>

        <div class="form-container">
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Tip Title</label>
                    <input type="text" id="title" name="title" placeholder="e.g. Turn off lights when leaving" required>
                </div>

                <div class="form-group">
                    <label for="content">Tip Content</label>
                    <textarea id="content" name="content" placeholder="Describe the tip in detail..." required></textarea>
                </div>

                <button type="submit" class="submit-btn">Share Tip</button>
            </form>
        </div>
    </div>
</div>

<script>
window.toggleDropdown = function(id, el) {
    var dropdown = document.getElementById(id);
    if (dropdown.style.display === "none" || dropdown.style.display === "") {
        dropdown.style.display = "flex";
        if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9652;'; // Up arrow
    } else {
        dropdown.style.display = "none";
            if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9662;'; // Down arrow
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.querySelector(".sidebar");
    const moreBtn = document.getElementById("moreBtn");
    const moreMenu = document.getElementById("moreMenu");

    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });
    }

    if (moreBtn && moreMenu) {
        moreBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            moreMenu.classList.toggle("active");
        });
    }

    document.addEventListener("click", function(e) {
        if (sidebar && sidebar.classList.contains("active") && !sidebar.contains(e.target) && e.target !== menuBtn) {
            sidebar.classList.remove("active");
        }
        if (moreMenu && moreMenu.classList.contains("active") && !moreMenu.contains(e.target) && e.target !== moreBtn) {
            moreMenu.classList.remove("active");
        }
    });
});
</script>

</body>
</html>
