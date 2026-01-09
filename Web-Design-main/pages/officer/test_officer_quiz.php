<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

$_SESSION['role'] = 'officer';
$_SESSION['user_id'] = 1;
// Auth bypassed
// if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
//    header("Location: ../auth/login.php");
//    exit();
// }

$user_id = $_SESSION['user_id'];

/* Fetch quizzes created */
$search = $_GET['search'] ?? '';
$db_error = false;
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
    $_SESSION['error'] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_quiz.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
</head>
<body>
    
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Quiz /View Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">View Quiz</a>
        </span>
    </div>

    <div class = "topbar-right">
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
            Quiz <span class="arrow">&#9652;</span> <!-- Pre-opened arrow style if active, or just stick to standard -->
        </a>
        <!-- Ensure dropdown is OPEN or consistent if we are ON the quiz page? User said "Quize (drop down...)" -->
        <!-- I will set it to display:flex since we are on the quiz page? Or keep closed? -->
        <!-- Let's keep closed but easy to open, OR if active, open. -->
        <!-- For simplicity now, let's keep consistent with others, maybe active class on "Quiz" link? -->
        
        <div id="quizMenu" class="dropdown-container" style="display: flex; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="officer_quiz.php" class="active" style="font-size: 0.9em;">View Quiz</a>
            <a href="officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
        </div>

        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = 'content quiz-page'>
        <div class = "quiz-header">
            <h2>Quiz</h2>

            <form method = "GET" class = "quiz-search">
                <div class = "search-wrapper">
                    <img src = "../../assets/images/search-icon.png" class = "search-icon">
                    <input type = "text" name = "search" placeholder = "Search quiz..." value = "<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <a href = "officer_create_quiz.php" class = "create-quiz-btn">
            <img src = "../../assets/images/plus-icon.png">
        </a>

        <div class = "quiz-grid">

        <?php if (isset($db_error)): ?>
            <div class = "no-quiz-text">Error: <?= htmlspecialchars($_SESSION['error'] ?? 'Unknown Error') ?></div>

        <?php elseif ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class = "quiz-card">
                    <img src = "../../uploads/quiz/<?= htmlspecialchars($row['picture']) ?>" alt = "Quiz Image">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class = "no-quiz-text">
                No quiz found. Click <strong>Create Quiz</strong> to get started.
            </div>

        <?php endif; ?>

        </div>

    </div>
</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

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