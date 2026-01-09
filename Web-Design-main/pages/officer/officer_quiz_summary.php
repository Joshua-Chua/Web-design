<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    header("Location: officer_quiz.php");
    exit();
}

$success_message = "";
$quiz = null;
$q_count = 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'draft') {
            // Save as Draft
            $stmt = $conn->prepare("UPDATE quiz SET status = 'draft' WHERE quiz_id = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("i", $quiz_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to save as draft: " . $stmt->error);
            }
            $stmt->close();
            
            // Success message
            $success_message = "Quiz saved as draft successfully!";
            
        } elseif ($action === 'publish') {
            // Save & Publish
            $stmt = $conn->prepare("UPDATE quiz SET status = 'published' WHERE quiz_id = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("i", $quiz_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to publish quiz: " . $stmt->error);
            }
            $stmt->close();
            
            // Redirect to quiz menu
            header("Location: officer_quiz.php");
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: officer_quiz_summary.php?quiz_id=$quiz_id");
    exit();
}

try {
    $quiz_stmt = $conn->prepare("SELECT * FROM quiz WHERE quiz_id = ?");
    if (!$quiz_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $quiz_stmt->bind_param("i", $quiz_id);
    if (!$quiz_stmt->execute()) {
        throw new Exception("Failed to load quiz: " . $quiz_stmt->error);
    }
    $quiz_result = $quiz_stmt->get_result();
    $quiz = $quiz_result->fetch_assoc();
    $quiz_stmt->close();
    
    if (!$quiz) {
        $_SESSION['error'] = "Quiz not found.";
        header("Location: officer_quiz.php");
        exit();
    }
    
    $q_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM question WHERE quiz_id = ?");
    if (!$q_count_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $q_count_stmt->bind_param("i", $quiz_id);
    if (!$q_count_stmt->execute()) {
        throw new Exception("Failed to count questions: " . $q_count_stmt->error);
    }
    $q_count_result = $q_count_stmt->get_result();
    $q_count_row = $q_count_result->fetch_assoc();
    $q_count = $q_count_row['total'] ?? 0;
    $q_count_stmt->close();
} catch (Exception $e) {
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
        <a href = "#">Monthly Report</a>
        <a href = "#">Events</a>
        <a href = "#">Smart Tips</a>

        <div class = "sidebar-group">
            <a href = "officer_quiz.php" class = "active">Quiz</a>
            <a href = "officer_quiz.php" class = "sub-link active">View Quiz</a>
            <a href = "officer_my_quiz.php" class = "sub-link">My Quiz</a>
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
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>