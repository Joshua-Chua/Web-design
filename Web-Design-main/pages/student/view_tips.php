<?php
session_start();
require '../../config/db.php';

// Allow student, officer, and admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'officer', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get info for topbar/sidebar
$user_name = $_SESSION['username']; 
// Role links
$home_link = 'student_main.php';
$profile_link = 'student_profile.php';

if ($role == 'student') {
    // Student already set defaults
} elseif ($role == 'officer') {
    $home_link = '../officer/officer_main.php';
    $profile_link = '../officer/officer_profile.php';
} elseif ($role == 'admin') {
    $home_link = '../admin/admin_main.php';
    $profile_link = '../admin/admin_profile.php';
}

// Fetch Tip details
if (!isset($_GET['id'])) {
    header("Location: browse_tips.php");
    exit();
}

$tip_id = intval($_GET['id']);
$query = "SELECT t.*, u.username as author 
          FROM smart_tips t 
          JOIN user u ON t.created_by = u.user_id 
          WHERE t.tip_id = $tip_id LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Tip not found.";
    exit();
}

$tip = mysqli_fetch_assoc($result);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tip['title']); ?> - Smart Tips</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <style>
        .tip-detail-container {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            max-width: 900px;
            margin: 0 auto;
        }
        .tip-detail-img {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .tip-detail-title {
            font-size: 2rem;
            color: #1a1a1a;
            margin-bottom: 15px;
        }
        .tip-detail-meta {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .tip-detail-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
            white-space: pre-wrap; /* Preserve newlines */
        }
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2E8B57;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Smart Tips</span>

    <div class = "topbar-left breadcrumb">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "<?php echo $home_link; ?>" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>
        <span class = "breadcrumb-separator">/</span>
        <a href = "browse_tips.php" class = "breadcrumb-link">Smart Tips</a>
        <span class = "breadcrumb-separator">/</span>
        <span class = "breadcrumb-link">View Tip</span>
    </div>

    <div class = "topbar-right">
        <a href = "<?php echo $profile_link; ?>" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "<?php echo $profile_link; ?>">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "<?php echo $home_link; ?>">Main Menu</a>
        
        <?php if($role == 'officer' || $role == 'admin'): ?>
            <a href = "../officer/officer_monthly_report.php">Monthly Report</a>
            <a href = "#">Events</a>
            <a href = "browse_tips.php" class = "active">Smart Tips</a>
            
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('quizMenu', this)">
                Quiz <span class="arrow">&#9662;</span>
            </a>
            <div id="quizMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="../officer/officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
                <a href="../officer/officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
            </div>

            <a href = "#">Forum</a>
        <?php else: ?>
             <!-- Student default -->
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('eventMenu', this)">
                Events <span class="arrow">&#9662;</span>
            </a>
            <div id="eventMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="#" style="font-size: 0.9em;">Event Registration</a>
                <a href="#" style="font-size: 0.9em;">Upcoming Event</a>
            </div>

            <a href = "browse_tips.php" class = "active">Smart Tips</a>
            <a href = "student_quiz.php">Quiz</a>
            <a href = "#">Achievement</a>
            <a href = "#">Forum</a>
        <?php endif; ?>

        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <a href="browse_tips.php" class="btn-back">&larr; Back to Tips</a>

        <div class="tip-detail-container">
            <?php if (!empty($tip['thumbnail'])): ?>
                <img src="../../uploads/tips/<?php echo htmlspecialchars($tip['thumbnail']); ?>" alt="Tip Thumbnail" class="tip-detail-img">
            <?php endif; ?>

            <h1 class="tip-detail-title"><?php echo htmlspecialchars($tip['title']); ?></h1>
            
            <div class="tip-detail-meta">
                <span>By: <?php echo htmlspecialchars($tip['author']); ?></span>
                 | 
                <span><?php echo date('M d, Y', strtotime($tip['created_at'])); ?></span>
            </div>

            <div class="tip-detail-content">
                <?php echo nl2br(htmlspecialchars($tip['content'])); ?>
            </div>
        </div>
    </div>
</div>

<script>
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

    /* Sidebar Dropdown Toggle */
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
