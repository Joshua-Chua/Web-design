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

if (!isset($_GET['quiz_id']) || !is_numeric($_GET['quiz_id'])) {
    header("Location: officer_quiz.php");
    exit();
}

$quiz_id = intval($_GET['quiz_id']);

$quiz_data = null;
$total_questions = 0;
$participants_count = 0;
$average_score = 0;
$passing_rate = 0;
$attempts_data = [];

try {
    // Fetch quiz details
    $quiz_query = "SELECT * FROM quiz WHERE quiz_id = ?";
    $stmt = $conn->prepare($quiz_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare quiz query: " . $conn->error);
    }
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute quiz query: " . $stmt->error);
    }
    $quiz_result = $stmt->get_result();
    $quiz_data = $quiz_result->fetch_assoc();
    $stmt->close();
    
    if (!$quiz_data) {
        $_SESSION['error'] = "Quiz not found.";
        header("Location: officer_quiz.php");
        exit();
    }
    
    // Count total questions for this quiz
    $questions_query = "SELECT COUNT(*) as total FROM question WHERE quiz_id = ?";
    $stmt = $conn->prepare($questions_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare questions query: " . $conn->error);
    }
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute questions query: " . $stmt->error);
    }
    $questions_result = $stmt->get_result();
    $questions_data = $questions_result->fetch_assoc();
    $total_questions = $questions_data['total'];
    $stmt->close();
    
    // Fetch quiz attempts directly from quiz_attempt table (no JOIN needed)
    $attempts_query = "
        SELECT 
            user_id,
            score,
            attempted_date
        FROM quiz_attempt 
        WHERE quiz_id = ? AND quiz_completed = 'Completed'
        ORDER BY attempted_date DESC
    ";
    
    $stmt = $conn->prepare($attempts_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare attempts query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute attempts query: " . $stmt->error);
    }
    $attempts_result = $stmt->get_result();
    $attempts_data = $attempts_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Calculate statistics
    $participants_count = count($attempts_data);
    $total_score = 0;
    $passed_count = 0;
    
    foreach ($attempts_data as $attempt) {
        $score = $attempt['score'] ?? 0;
        $total_score += $score;
        
        // Calculate passing based on percentage (assuming 50% is passing as per your code)
        if ($score >= 50) {
            $passed_count++;
        }
    }
    
    // Calculate average score and passing rate
    if ($participants_count > 0) {
        // Average score is already in percentage, just calculate average
        $average_score = round($total_score / $participants_count, 1);
        $passing_rate = round(($passed_count / $participants_count) * 100, 1);
    } else {
        $average_score = 0;
        $passing_rate = 0;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Database error occurred: " . $e->getMessage();
    error_log("Quiz detail error: " . $e->getMessage());
    header("Location: officer_quiz.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability - Quiz Details</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_quiz.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
</head>
<body>
    
<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Quiz / View Quiz</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">

        <a href="officer_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>

        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="officer_quiz.php" class="breadcrumb-link">Quiz</a>
            <span class="breadcrumb-separator">/</span>
            <a href="officer_quiz.php" class="breadcrumb-link">View Quiz</a>
        </span>
    </div>

    <div class="topbar-right">
        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="officer_profile.php">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="officer_main.php">Main Menu</a>
        <a href="#">Monthly Report</a>
        <a href="#">Events</a>
        <a href="#">Smart Tips</a>

        <div class="sidebar-group">
            <a href="officer_quiz.php" class="active">Quiz</a>
            <a href="officer_quiz.php" class="sub-link active">View Quiz</a>
            <a href="officer_my_quiz.php" class="sub-link">My Quiz</a>
        </div>

        <a href="#">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content quiz-detail-page">
        <a href="officer_quiz.php" class="back-button">
            <img src="../../assets/images/back-icon.png" alt="Back">
            Back to Quiz List
        </a>
        
        <div class="quiz-header-section">
            <div class="quiz-image-container">
                <img src="../../uploads/quiz/<?= htmlspecialchars($quiz_data['picture']) ?>" 
                     alt="Quiz Image" 
                     class="quiz-image"
                     onerror="this.src='../../assets/images/default-quiz.png'">
            </div>
            
            <div class="quiz-info">
                <h1 class="quiz-title"><?= htmlspecialchars($quiz_data['title']) ?></h1>
                <p class="quiz-description"><?= htmlspecialchars($quiz_data['description']) ?></p>
                
                <div class="quiz-meta">
                    <div class="meta-item">
                        <img src="../../assets/images/clock-icon.png" alt="Time">
                        <span>Time Limit: <?= htmlspecialchars($quiz_data['time_limit']) ?> minutes</span>
                    </div>
                    <div class="meta-item">
                        <img src="../../assets/images/question-icon.png" alt="Questions">
                        <span>Total Questions: <?= $total_questions ?></span>
                    </div>
                    <div class="meta-item">
                        <img src="../../assets/images/progress-icon.png" alt="Status">
                        <span>Status: <?= htmlspecialchars(ucfirst($quiz_data['status'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="statistics-section">
            <h2 class="section-title">Quiz Statistics</h2>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?= $participants_count ?></div>
                    <div class="stat-label">People Participated</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-circle">
                        <svg class="circle-svg" viewBox="0 0 36 36">
                            <path class="circle-bg"
                                d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke-width="2"
                            />
                            <path class="circle-progress"
                                d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#2c7be5"
                                stroke-width="2"
                                stroke-dasharray="<?= $average_score ?>, 100"
                                stroke-linecap="round"
                            />
                        </svg>
                        <div class="circle-text"><?= $average_score ?>%</div>
                    </div>
                    <div class="circle-label">Average Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-circle">
                        <svg class="circle-svg" viewBox="0 0 36 36">
                            <path class="circle-bg"
                                d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke-width="2"
                            />
                            <path class="circle-progress"
                                d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#28a745"
                                stroke-width="2"
                                stroke-dasharray="<?= $passing_rate ?>, 100"
                                stroke-linecap="round"
                            />
                        </svg>
                        <div class="circle-text"><?= $passing_rate ?>%</div>
                    </div>
                    <div class="circle-label">Passing Rate</div>
                </div>
            </div>
        </div>
        
        <div class="participants-section">
            <div class="participants-header">
                <h2 class="section-title">Participants</h2>
                <div class="participants-count">Total: <?= $participants_count ?> students</div>
            </div>
            
            <?php if ($participants_count > 0): ?>
                <div class="table-responsive">
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Attempt Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attempts_data as $attempt): 
                                $score = $attempt['score'] ?? 0;
                                $percentage = $score; // Score is already percentage
                                $status_class = $score >= 50 ? 'score-pass' : 'score-fail'; // Using 50% as passing threshold
                                $status_text = $score >= 50 ? 'Passed' : 'Failed';
                            ?>
                                <tr>
                                    <td>Student #<?= htmlspecialchars($attempt['user_id']) ?></td>
                                    <td><?= $score ?>%</td>
                                    <td>
                                        <span class="score-indicator <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($attempt['attempted_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <img src="../../assets/images/no-data-icon.png" alt="No Data" class="no-data-icon">
                    <p>No participants yet. Students haven't attempted this quiz.</p>
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

document.addEventListener("DOMContentLoaded", function () {
    
    // Animate the progress circles
    const circles = document.querySelectorAll('.circle-progress');
    circles.forEach(circle => {
        const length = circle.getTotalLength();
        circle.style.strokeDasharray = length;
        circle.style.strokeDashoffset = length;
        
        setTimeout(() => {
            const value = parseFloat(circle.getAttribute('stroke-dasharray').split(',')[0]);
            const offset = length - (value / 100) * length;
            circle.style.strokeDashoffset = offset;
        }, 100);
    });
});
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>