<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit();
}
$role = $_SESSION['role'];
$profile_link = ($role == 'admin') ? '../admin/admin_profile.php' : 'officer_profile.php';

$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    header("Location: officer_quiz.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'draft') {
        // Save as Draft
        $stmt = $conn->prepare("UPDATE quiz SET status = 'draft' WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $stmt->close();
        
        // Success message
        $success_message = "Quiz saved as draft successfully!";
        
    } elseif ($action === 'publish') {
        // Save & Publish
        $stmt = $conn->prepare("UPDATE quiz SET status = 'published' WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to quiz menu
        header("Location: officer_quiz.php");
        exit();
    }
}

$quiz_stmt = $conn->prepare("SELECT * FROM quiz WHERE quiz_id = ?");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
$quiz = $quiz_result->fetch_assoc();

$q_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM question WHERE quiz_id = ?");
$q_count_stmt->bind_param("i", $quiz_id);
$q_count_stmt->execute();
$q_count_result = $q_count_stmt->get_result();
$q_count = $q_count_result->fetch_assoc()['total'];

if (!$quiz) {
    echo "<p>Quiz not found.</p>";
    exit();
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
    <span class = "page-title">Quiz /Create Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-seperator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-seperator">/</span>
            <a href = "officer_create_quiz.php" class = "breadcrumb-link">Create Quiz</a>
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
            Quiz <span class="arrow">&#9662;</span>
        </a>
        <div id="quizMenu" class="dropdown-container" style="display: flex; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
            <a href="officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
        </div>

        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content quiz-page quiz-summary-page">
        <div class = "quiz-summary-box">
            <a href = "officer_quiz.php" class = "close-btn">
                <img src = "../../assets/images/close-icon.png">
            </a>

            <h2>Quiz Summary</h2>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class = "summary-section">

                <div class = "summary-row">
                    <strong>Title:</strong>
                    <span><?= htmlspecialchars($quiz['title']) ?></span>
                </div>

                <div class = "summary-row">
                    <strong>Description:</strong>
                    <span><?= htmlspecialchars($quiz['description']) ?></span>
                </div>

                <div class = "summary-row">
                    <strong>Time Limit:</strong>
                    <span><?= htmlspecialchars($quiz['time_limit']) ?> minutes</span>
                </div>

                <div class = "summary-row">
                    <strong>Total Questions:</strong>
                    <span><?= $q_count ?></span>
                </div>

                <div class = "summary-row">
                    <strong>Cover Image:</strong>
                    <?php if (!empty($quiz['picture'])): ?>
                        <img src = "../../uploads/quiz/<?= htmlspecialchars($quiz['picture']) ?>" class = "summary-image">
                    <?php else: ?>
                        <span>No cover image</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class = "summary-footer">
                <a href = "officer_create_questions.php?quiz_id=<?= $quiz_id ?>" class = "btn back-btn">
                    Back
                </a>

                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="draft">
                    <button type="submit" class = "btn draft-btn" onclick="return confirm('Save this quiz as draft?')">
                        Save as Draft
                    </button>
                </form>

                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="publish">
                    <button type="submit" class = "btn publish-btn" onclick="return confirm('Publish this quiz? It will be available for users.')">
                        Save & Publish
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

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