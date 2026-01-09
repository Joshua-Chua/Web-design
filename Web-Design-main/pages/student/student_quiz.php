<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'admin', 'officer'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$profile_link = 'student_profile.php';
if($role == 'officer') $profile_link = '../officer/officer_profile.php';
if($role == 'admin') $profile_link = '../admin/admin_profile.php';

/* Fetch quizzes created */
$search = $_GET['search'] ?? '';
$db_error = false;
$error_msg = '';
$result = false;

try {
    $query = "SELECT * FROM quiz WHERE status = 'published'";

    if (!empty($search)) {
        $search_sql = mysqli_real_escape_string($conn, $search);
        if (!$search_sql) {
            throw new Exception("Failed to sanitize search input");
        }
        $query .= " AND title LIKE '%$search_sql%'";
    }

    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    $db_error = true;
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability - Quizzes</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css"> <!-- Reuse officer styles -->
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_quiz.css"> <!-- Reuse quiz grid styles -->
    <style>
        .quiz-card {
            cursor: default; /* Since we have a button */
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .start-btn {
            margin-top: auto;
            background: #2E8B57;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
        }
        .start-btn:hover {
            background: #3CB371;
        }
    </style>
</head>
<body>
    
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "student_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "student_quiz.php" class = "breadcrumb-link">Quiz</a>
        </span>
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
        <a href = "student_main.php">Main Menu</a>
        
        <?php if ($role == 'officer' || $role == 'admin'): ?>
            <a href = "../officer/officer_monthly_report.php">Monthly Report</a>
            <a href = "#">Events</a>
            <a href = "browse_tips.php">Smart Tips</a>
            <a href = "../officer/officer_quiz.php">View Quiz</a>
            <a href = "../officer/officer_my_quiz.php">My Quiz</a>
            <a href = "#">Forum</a>
        <?php else: ?>
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('eventMenu', this)">
                Events <span class="arrow">&#9662;</span>
            </a>
            <div id="eventMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="#" style="font-size: 0.9em;">Event Registration</a>
                <a href="#" style="font-size: 0.9em;">Upcoming Event</a>
            </div>
            
            <a href = "browse_tips.php">Smart Tips</a>
            <a href = "student_quiz.php" class="active">Quiz</a>
            <a href = "#">Achievement</a>
            <a href = "#">Forum</a>
        <?php endif; ?>

        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = 'content quiz-page'>
        <div class = "quiz-header">
            <h2>Available Quizzes</h2>

            <form method = "GET" class = "quiz-search">
                <div class = "search-wrapper">
                    <img src = "../../assets/images/search-icon.png" class = "search-icon">
                    <input type = "text" name = "search" placeholder = "Search quiz..." value = "<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <div class = "quiz-grid">

        <?php if (isset($db_error) && $db_error): ?>
            <div class = "no-quiz-text">Error: <?= htmlspecialchars($error_msg) ?></div>

        <?php elseif ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class = "quiz-card">
                    <img src = "../../uploads/quiz/<?= htmlspecialchars($row['picture']) ?>" alt = "Quiz Image" style="width:100%; height: 150px; object-fit: cover; border-radius: 8px;">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <a href="student_attempt_quiz.php?quiz_id=<?= $row['quiz_id'] ?>" class="start-btn">Start Quiz</a>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class = "no-quiz-text">
                No quizzes available at the moment.
            </div>

        <?php endif; ?>

        </div>

    </div>
</div>

<script>
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

document.addEventListener("DOMContentLoaded", function () {

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

    /* Auto-close when clicking outside */
    document.addEventListener("click", function(e) {
        
        /*  Close sidebar */
        if (
            sidebar &&
            sidebar.classList.contains("active") &&
            !sidebar.contains(e.target) &&
            e.target !== menuBtn
        ){
            sidebar.classList.remove("active");
        }

        /* Close more menu */
        if (
            moreMenu &&
            moreMenu.classList.contains("active") &&
            !moreMenu.contains(e.target) &&
            e.target !== moreBtn
        ){
            moreMenu.classList.remove("active")
        }
    });
});
</script>

</body>
</html>
