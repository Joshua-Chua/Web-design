<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$profile_link = ($role == 'admin') ? '../admin/admin_profile.php' : 'officer_profile.php';

/* Filters */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

/* Base query */
$query = "SELECT * FROM quiz WHERE user_id = ?";
$params = [$user_id];
$types = "i";

/* Status filter */
if ($status === 'draft' || $status === 'published') {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

/* Search filter */
if (!empty($search)) {
    $query .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
    <span class = "page-title">Quiz /My Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_quiz" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_my_quiz" class = "breadcrumb-link">My Quiz</a>
        </span>
    </div>

    <div class = "topbar-right">
        <a href = "<?php echo $profile_link; ?>" class = "user-link">
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
            Quiz <span class="arrow">&#9652;</span> <!-- Pre-opened arrow style if active -->
        </a>
        
        <div id="quizMenu" class="dropdown-container" style="display: flex; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
            <a href="officer_my_quiz.php" class="active" style="font-size: 0.9em;">My Quiz</a>
        </div>

        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content quiz-page">
        <div class = "quiz-header">
            <h2>My Quiz</h2>

            <form method = "GET" class = "quiz-search">
                <div class = "search-wrapper">
                    <img src = "../../assets/images/search-icon.png" class = "search-icon">
                    <input type = "text" name = "search" placeholder = "Search quiz..." value = "<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <div class = "quiz-filter">
            <a href = "?status=all" class = "<?= $status === 'all' ? 'active' : '' ?>">All</a>
            <a href = "?status=draft" class = "<?= $status === 'draft' ? 'active' : '' ?>">Draft</a>
            <a href = "?status=published" class = "<?= $status === 'published' ? 'active' : '' ?>">Published</a>
        </div>

        <div class = "quiz-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class = "quiz-card">

                        <img src = "../../uploads/quiz/<?= htmlspecialchars($row['picture']) ?>">

                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>

                        <span class = "quiz-status <?= $row['status'] ?> ">
                            <?= ucfirst($row['status']) ?>
                        </span>

                        <?php if ($row['status'] === 'draft'): ?>
                            <a href = "officer_create_quiz.php?quiz_id=<?= $row['quiz_id'] ?>" class = "quiz-action">Continue Editing</a>
                        <?php else: ?>
                            <a href = "officer_view_quiz.php?id=<?= $row['quiz_id'] ?>" class = "quiz-action">View</a>
                        <?php endif; ?>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class = "no-quiz-text">
                    No <?= $status !== 'all' ? $status : '' ?> quiz found.
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
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