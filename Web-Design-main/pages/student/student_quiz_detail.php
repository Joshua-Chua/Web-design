<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

$quiz = null;
$is_completed = false;

try {
    // First query: Get quiz details
    $query = "SELECT * FROM quiz WHERE quiz_id = ? AND status = 'published'";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute query: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Failed to get result: " . mysqli_error($conn));
    }
    
    $quiz = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$quiz) {
        header("Location: student_quiz.php");
        exit();
    }

    // Second query: Check completion status
    $completion_check = "SELECT * FROM quiz_attempt WHERE user_id = ? AND quiz_id = ? AND quiz_completed = 'Completed' LIMIT 1";
    $stmt2 = mysqli_prepare($conn, $completion_check);
    
    if (!$stmt2) {
        throw new Exception("Failed to prepare completion check: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt2, "ii", $user_id, $quiz_id);
    
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception("Failed to execute completion check: " . mysqli_stmt_error($stmt2));
    }
    
    $completion_result = mysqli_stmt_get_result($stmt2);
    
    if (!$completion_result) {
        throw new Exception("Failed to get completion result: " . mysqli_error($conn));
    }
    
    $is_completed = mysqli_num_rows($completion_result) > 0;
    mysqli_stmt_close($stmt2);

    // If completed, show an alert and redirect back to quiz menu
    if ($is_completed) {
        $_SESSION['error'] = "You have already completed the quiz: " . htmlspecialchars($quiz['title']);
        header("Location: student_quiz.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: student_quiz.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_quiz.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
    <link rel="stylesheet" href="../../assets/css/student/student_quiz.css">
</head>
<body>
    
<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Quiz</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
        <a href="student_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>
        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="student_quiz.php" class="breadcrumb-link">Quiz</a>
        </span>
    </div>

    <div class="topbar-right">
        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="student_profile.php">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="student_main.php">Main Menu</a>
        <a href="#">Events</a>
        <a href="browse_tips.php">Smart Tips</a>
        <a href="student_quiz.php" class="active">Quiz</a>
        <a href="student_achievement.php">Achievement</a>
        <a href="#">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content quiz-detail-page">
        <div class="quiz-detail-container">
            <!-- Back button -->
            <a href="student_quiz.php" class="back-btn">
                <img src="../../assets/images/back-icon.png" alt="Back">
                Back to Quiz List
            </a>

            <!-- Quiz Details -->
            <div class="quiz-detail-card">
                <div class="quiz-detail-image">
                    <?php if (!empty($quiz['picture'])): ?>
                        <img src="../../uploads/quiz/<?= htmlspecialchars($quiz['picture']) ?>" 
                             alt="<?= htmlspecialchars($quiz['title']) ?>">
                    <?php else: ?>
                        <img src="../../assets/images/default-quiz.jpg" alt="Default Quiz Image">
                    <?php endif; ?>
                </div>
                
                <div class="quiz-detail-content">
                    <h1><?= htmlspecialchars($quiz['title']) ?></h1>
                    
                    <div class="quiz-meta">
                        <?php if (!empty($quiz['category'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value"><?= htmlspecialchars($quiz['category']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-label">Format:</span>
                            <span class="meta-value">Multiple Choice Questions</span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Duration:</span>
                            <span class="meta-value">
                                <?= !empty($quiz['time_limit']) ? htmlspecialchars($quiz['time_limit']) . ' minutes' : 'No time limit' ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Passing Score:</span>
                            <span class="meta-value">50% or higher</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($quiz['description'])): ?>
                    <div class="quiz-description">
                        <h3>Description</h3>
                        <p><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="quiz-instructions">
                        <h3>Instructions</h3>
                        <ul>
                            <li>Answer all questions to complete the quiz</li>
                            <li>Passing score is 50% or higher</li>
                            <li>You can retake the quiz until you achieve a passing score</li>
                            <?php if (!empty($quiz['time_limit'])): ?>
                            <li>Time limit: <?= htmlspecialchars($quiz['time_limit']) ?> minutes</li>
                            <?php endif; ?>
                            <li>Your score will be shown immediately after completion</li>
                        </ul>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="quiz-actions">
                        <a href="student_quiz.php" class="btn-secondary">
                            Cancel
                        </a>
                        
                        <a href="student_start_quiz.php?quiz_id=<?= $quiz_id ?>" 
                           class="btn-primary">
                            Start Quiz
                        </a>
                    </div>
                </div>
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