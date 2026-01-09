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
$attempt_data = [];
$questions = [];
$answers = [];
$total_questions = 0;
$current_question = 0;
$timer_expired = false;
$answered_count = 0;
$skipped_count = 0;
$not_answered_count = 0;

try {
    // Fetch quiz details
    $quiz_query = "SELECT * FROM quiz WHERE quiz_id = ? AND status = 'published'";
    $stmt = $conn->prepare($quiz_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    $quiz_result = $stmt->get_result();
    $quiz = $quiz_result->fetch_assoc();
    $stmt->close();

    if (!$quiz) {
        $_SESSION['error'] = "Quiz not found!";
        header("Location: student_quiz.php");
        exit();
    }

    // Check if there's a quiz attempt in session
    if (!isset($_SESSION['quiz_attempt']) || $_SESSION['quiz_attempt']['quiz_id'] != $quiz_id) {
        $_SESSION['error'] = "No active quiz attempt found. Please start a new quiz.";
        header("Location: student_quiz.php");
        exit();
    }

    $attempt_data = $_SESSION['quiz_attempt'];
    $questions = $attempt_data['questions'] ?? [];
    $answers = $attempt_data['answers'] ?? [];
    $total_questions = count($questions);

    $current_question = $attempt_data['current_question'] ?? 0;
    if ($current_question >= $total_questions) {
        $current_question = $total_questions - 1;
    }
    if ($current_question < 0) {
        $current_question = 0;
    }

    $timer_expired = false;
    if (isset($_GET['time_expired']) && $_GET['time_expired'] == '1') {
        // Timer expired from start quiz page
        $timer_expired = true;
        // Store in session so it persists
        $_SESSION['quiz_timer_expired'] = [
            'quiz_id' => $quiz_id,
            'expired' => true
        ];
    } elseif (isset($_SESSION['quiz_timer_expired']) && $_SESSION['quiz_timer_expired']['quiz_id'] == $quiz_id) {
        // Timer expired stored in session
        $timer_expired = $_SESSION['quiz_timer_expired']['expired'];
    }

    // Handle submit for grading
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_for_grading'])) {
        $_SESSION['quiz_attempt']['submitted_for_grading'] = true;
        
        // Redirect to quiz result page
        header("Location: student_quiz_result.php?quiz_id=$quiz_id&submitted=true");
        exit();
    }

    // Calculate statistics for summary
    $answered_count = 0;
    $skipped_count = 0;
    $not_answered_count = 0;

    foreach ($answers as $index => $answer) {
        if ($answer && isset($answer['submitted']) && $answer['submitted'] === true) {
            if (isset($answer['selected_option']) && $answer['selected_option'] !== null) {
                $answered_count++;
            } else {
                $skipped_count++;
            }
        } else {
            $not_answered_count++;
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to load quiz summary: " . $e->getMessage();
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
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
    <link rel="stylesheet" href="../../assets/css/student/student_quiz.css">
</head>
<body>
    
<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Quiz</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">

        <a href="student_main.php" class="home-btn" id="homeBtn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>

        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="student_quiz.php" class="breadcrumb-link" id="breadcrumbQuiz">Quiz</a>
        </span>
    </div>

    <div class="topbar-right">
        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="student_profile.php" class="more-menu-link">Profile</a>
            <a href="../auth/logout.php" class="more-menu-link">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">

    <div class="sidebar">
        <a href="student_main.php" class="sidebar-link">Main Menu</a>
        <a href="#" class="sidebar-link">Events</a>
        <a href="browse_tips.php" class="sidebar-link">Smart Tips</a>
        <a href="student_quiz.php" class="sidebar-link active">Quiz</a>
        <a href="student_achievement.php" class="sidebar-link">Achievement</a>
        <a href="#" class="sidebar-link">Forum</a>
        <a href="../auth/logout.php" class="sidebar-link">Logout</a>
    </div>

    <div class="content quiz-summary-page">
        <div class="summary-header">
            <h1>Quiz Summary</h1>
            <h2><?= htmlspecialchars($quiz['title']) ?></h2>
            
            <?php if ($timer_expired): ?>
            <div class="timer-expired-message">
                <img src="../../assets/images/clock-icon.png" alt="Time Expired">
                <span><strong>Time's Up!</strong> Your quiz time has expired. Please submit your answers for grading.</span>
            </div>
            <?php endif; ?>
            
            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="../../assets/images/question-icon.png" alt="Total Questions">
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $total_questions ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                </div>
                
                <div class="stat-card answered-stat">
                    <div class="stat-icon">
                        <img src="../../assets/images/answered-icon.png" alt="Answered">
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $answered_count ?></div>
                        <div class="stat-label">Answered</div>
                    </div>
                </div>
                
                <div class="stat-card skipped-stat">
                    <div class="stat-icon">
                        <img src="../../assets/images/skipped-icon.png" alt="Skipped">
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $skipped_count ?></div>
                        <div class="stat-label">Skipped</div>
                    </div>
                </div>
                
                <div class="stat-card not-answered-stat">
                    <div class="stat-icon">
                        <img src="../../assets/images/not-answered-icon.png" alt="Not Answered">
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $not_answered_count ?></div>
                        <div class="stat-label">Not Answered</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="questions-summary">
            <h3>Question Overview</h3>
            <p class="summary-description">Review your answers before submitting for grading. Click on any question to go back and modify your answer.</p>
            
            <div class="summary-table-container">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Question No.</th>
                            <th>Question</th>
                            <th>Answer Status</th>
                            <th>Your Answer</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $index => $question): 
                            $answer = $answers[$index] ?? null;
                            $question_number = $index + 1;
                            
                            // Determine status
                            $status = 'Not Answered';
                            $status_class = 'not-answered';
                            $your_answer = '-';
                            
                            if ($answer && $answer['submitted'] === true) {
                                if ($answer['selected_option'] !== null) {
                                    $status = 'Answered';
                                    $status_class = 'answered';
                                    $your_answer = htmlspecialchars($answer['selected_option']);
                                } else {
                                    $status = 'Skipped';
                                    $status_class = 'skipped';
                                }
                            }
                        ?>
                        <tr class="<?= $status_class ?>">
                            <td class="question-number"><?= $question_number ?></td>
                            <td class="question-text">
                                <?= htmlspecialchars(substr($question['question'], 0, 50)) ?>
                                <?= strlen($question['question']) > 50 ? '...' : '' ?>
                            </td>
                            <td class="answer-status">
                                <span class="status-badge <?= $status_class ?>">
                                    <?php if ($status_class === 'answered'): ?>
                                        <img src="../../assets/images/answered-icon.png" alt="Answered">
                                    <?php elseif ($status_class === 'skipped'): ?>
                                        <img src="../../assets/images/skipped-icon.png" alt="Skipped">
                                    <?php else: ?>
                                        <img src="../../assets/images/not-answered-icon.png" alt="Not Answered">
                                    <?php endif; ?>
                                    <?= $status ?>
                                </span>
                            </td>
                            <td class="your-answer"><?= $your_answer ?></td>
                            <td class="action-cell">
                                <?php if ($timer_expired): ?>
                                    <span class="goto-btn disabled-link">
                                        <img src="../../assets/images/edit-icon.png" alt="Edit">
                                        Go to Question
                                    </span>
                                <?php else: ?>
                                    <a href="student_start_quiz.php?quiz_id=<?= $quiz_id ?>&question=<?= $index ?>" class="goto-btn">
                                        <img src="../../assets/images/edit-icon.png" alt="Edit">
                                        Go to Question
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="summary-actions">
            <?php if ($timer_expired): ?>
                <span class="nav-btn btn-continue disabled">
                    <img src="../../assets/images/back-icon.png" alt="Continue">
                    Back to Quiz
                </span>
            <?php else: ?>
                <a href="student_start_quiz.php?quiz_id=<?= $quiz_id ?>&question=<?= $current_question ?>" class="nav-btn btn-continue">
                    <img src="../../assets/images/back-icon.png" alt="Continue">
                    Back to Quiz
                </a>
            <?php endif; ?>
            
            <form method="POST" action="" class="submit-form" onsubmit="return confirmSubmit();">
                <button type="submit" name="submit_for_grading" class="nav-btn btn-submit">
                    <img src="../../assets/images/submit-icon.png" alt="Submit">
                    Submit for Grading
                </button>
            </form>
        </div>
    </div>
</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function () {

    <?php if (!$timer_expired): ?>
    // Only allow navigation if timer hasn't expired

    // Intercept sidebar navigation clicks
    document.querySelectorAll('.sidebar-link:not(.active)').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave this quiz? Your current attempt will be marked as "Not Completed".')) {
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                window.location.href = quitUrl;
            }
        });
    });

    // Intercept home button click
    const homeBtn = document.getElementById('homeBtn');
    if (homeBtn) {
        homeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave this quiz? Your current attempt will be marked as "Not Completed".')) {
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                window.location.href = quitUrl;
            }
        });
    }

    // Intercept breadcrumb quiz link click
    const breadcrumbQuiz = document.getElementById('breadcrumbQuiz');
    if (breadcrumbQuiz) {
        breadcrumbQuiz.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave this quiz? Your current attempt will be marked as "Not Completed".')) {
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                window.location.href = quitUrl;
            }
        });
    }

    // Intercept more menu links
    document.querySelectorAll('.more-menu-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave this quiz? Your current attempt will be marked as "Not Completed".')) {
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                window.location.href = quitUrl;
            }
        });
    });
    <?php else: ?>
    // Timer expired - disable all navigation
    document.querySelectorAll('.sidebar-link, .home-btn, .breadcrumb-link, .more-menu-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Quiz time has expired. Please submit your answers.');
        });
    });
    <?php endif; ?>
});

function confirmSubmit() {
    const notAnswered = <?= $not_answered_count ?>;
    const skipped = <?= $skipped_count ?>;
    const timerExpired = <?= $timer_expired ? 'true' : 'false' ?>;
    
    let message = "Are you sure you want to submit this quiz for grading?\n\n";
    
    if (timerExpired) {
        message = "Time's up! You must submit your quiz now.\n\n";
    }
    
    if (notAnswered > 0) {
        message += `You have ${notAnswered} unanswered question(s).\n`;
    }
    if (skipped > 0) {
        message += `You have ${skipped} skipped question(s).\n`;
    }
    
    message += "\nOnce submitted, you cannot change your answers.";
    
    return confirm(message);
}
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>