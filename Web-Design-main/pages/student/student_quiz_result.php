<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

// Set the correct timezone for Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

// Fetch quiz details
$quiz = null;
$questions = [];
$attempt = null;
$user_answers_details = [];
$total_questions = 0;
$correct_answers = 0;
$score = 0;
$percentage = 0;
$passed = false;
$show_retry_button = false;
$from_summary = false;
$current_date = date('M d, Y h:i A');

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

    // Check if session has quiz attempt with answers
    if (isset($_SESSION['quiz_attempt']) && $_SESSION['quiz_attempt']['quiz_id'] == $quiz_id) {
        $attempt_data = $_SESSION['quiz_attempt'];
        $answers = $attempt_data['answers'] ?? [];
        $attempt_number = $attempt_data['attempt_number'] ?? 1;
        $from_summary = true;
    }

    // Fetch all questions for this quiz
    $question_query = "SELECT * FROM question WHERE quiz_id = ? ORDER BY question_number ASC";
    $stmt = $conn->prepare($question_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    $questions_result = $stmt->get_result();
    while ($row = $questions_result->fetch_assoc()) {
        $questions[] = $row;
    }
    $stmt->close();

    $total_questions = count($questions);

    if ($from_summary) {
        $correct_answers = 0;
        $user_answers_details = [];

        // Calculate score based on session answers
        foreach ($questions as $index => $question) {
            $user_answer = $answers[$index] ?? null;
            $user_selected_option = $user_answer['selected_option'] ?? null;
            
            // Get the actual text for the selected option
            $user_selected_text = null;
            if ($user_selected_option) {
                // Map option letter to actual text
                switch ($user_selected_option) {
                    case 'A': $user_selected_text = $question['option_a'] ?? ''; break;
                    case 'B': $user_selected_text = $question['option_b'] ?? ''; break;
                    case 'C': $user_selected_text = $question['option_c'] ?? ''; break;
                    case 'D': $user_selected_text = $question['option_d'] ?? ''; break;
                }
            }
            
            $correct_answer_text = $question['answer'] ?? '';
            
            // Compare the actual text, not the option letters
            $is_correct = ($user_selected_text == $correct_answer_text);
            
            // Store user's answer details for display
            $user_answers_details[$index] = [
                'selected_option' => $user_selected_option,
                'selected_text' => $user_selected_text,
                'correct_text' => $correct_answer_text,
                'is_correct' => $is_correct
            ];
            
            if ($is_correct) {
                $correct_answers++;
            }
        }
        
        // Calculate score
        $score = $total_questions > 0 ? ($correct_answers / $total_questions) * 100 : 0;
        $score = round($score, 2);
        
        // Determine completion status based on passing mark
        $passing_mark = 50;
        $quiz_completed = ($score >= $passing_mark) ? 'Completed' : 'Not Completed';
        
        // Save the attempt to database
        $insert_query = "INSERT INTO quiz_attempt (user_id, quiz_id, score, quiz_completed, attempted_count) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("iidsi", $user_id, $quiz_id, $score, $quiz_completed, $attempt_number);
        
        if ($stmt->execute()) {
            $attempt_id = $stmt->insert_id;
            $stmt->close();

            if ($quiz_completed === 'Completed') {
                try {
                    // Include the achievement system
                    if (file_exists('../../system/achievement_helpers.php')) {
                        require_once '../../system/achievement_helpers.php';
                        
                        // Award achievements for quiz completion
                        $new_achievements = awardAchievementsAfterQuiz($conn, $user_id);
                        
                        // Store new achievements in session for notification on achievements page
                        if (!empty($new_achievements)) {
                            $_SESSION['new_achievements'] = $new_achievements;
                            
                            // Store a simpler version for immediate notification
                            $_SESSION['recent_achievements'] = array_map(function($ach) {
                                return is_array($ach) ? $ach['name'] : $ach;
                            }, $new_achievements);
                        }
                    }
                } catch (Exception $achievement_error) {
                    // Log achievement error but don't break the quiz result
                    error_log("Achievement system error: " . $achievement_error->getMessage());
                }
            }
            
            // Fetch the newly created attempt for display
            $attempt_query = "SELECT * FROM quiz_attempt WHERE attempt_id = ?";
            $stmt = $conn->prepare($attempt_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param("i", $attempt_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            $attempt_result = $stmt->get_result();
            $attempt = $attempt_result->fetch_assoc();
            $stmt->close();
            
            // Clear session after processing
            unset($_SESSION['quiz_attempt']);
        } else {
            throw new Exception("Failed to save quiz results: " . $stmt->error);
        }
    } else {
        if (!$attempt_id) {
            $_SESSION['error'] = "Quiz attempt not found!";
            header("Location: student_quiz.php");
            exit();
        }
        
        // Fetch attempt details
        $attempt_query = "SELECT * FROM quiz_attempt WHERE attempt_id = ? AND user_id = ? AND quiz_id = ?";
        $stmt = $conn->prepare($attempt_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("iii", $attempt_id, $user_id, $quiz_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        $attempt_result = $stmt->get_result();
        $attempt = $attempt_result->fetch_assoc();
        $stmt->close();

        if (!$attempt) {
            $_SESSION['error'] = "Quiz attempt not found!";
            header("Location: student_quiz.php");
            exit();
        }
        
        // Calculate correct answers from score for display
        $score = $attempt['score'];
        $correct_answers = round(($score / 100) * $total_questions);
        
        // Create dummy user answers details
        $user_answers_details = [];
        foreach ($questions as $index => $question) {
            $user_answers_details[$index] = [
                'selected_option' => null,
                'selected_text' => null,
                'correct_text' => $question['answer'] ?? '',
                'is_correct' => false
            ];
        }
    }

    // Calculate statistics
    $percentage = round($attempt['score'], 1);

    // Determine if passed
    $passing_mark = 50;
    $passed = $attempt['score'] >= $passing_mark;

    // Check if quiz is completed to determine if "Take Quiz Again" button should be shown
    $show_retry_button = ($attempt['quiz_completed'] !== 'Completed');
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to load quiz results: " . $e->getMessage();
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
    <span class="page-title">Quiz Results</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">

        <a href="student_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>

        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="student_quiz.php" class="breadcrumb-link">Quiz</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Results</span>
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
        <a href="#">Achievement</a>
        <a href="#">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content quiz-result-page">
        <div class="result-header">
            <h1>Quiz Results</h1>
            <h2><?= htmlspecialchars($quiz['title']) ?></h2>
            
            <div class="result-summary <?= $passed ? 'passed' : 'failed' ?>">
                <div class="result-icon">
                    <?php if ($passed): ?>
                        <img src="../../assets/images/success-icon.png" alt="Passed">
                    <?php else: ?>
                        <img src="../../assets/images/failed-icon.png" alt="Failed">
                    <?php endif; ?>
                </div>
                <div class="result-text">
                    <h3><?= $passed ? 'Congratulations! You Passed!' : 'You Did Not Pass' ?></h3>
                    <p>Attempt <?= $attempt['attempted_count'] ?> • <?= $attempt['quiz_completed'] ?> • <?= $current_date ?></p>
                </div>
            </div>
        </div>

        <div class="result-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../../assets/images/score-icon.png" alt="Score">
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $percentage ?>%</div>
                    <div class="stat-label">Score</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../../assets/images/correct-icon.png" alt="Correct">
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $correct_answers ?>/<?= $total_questions ?></div>
                    <div class="stat-label">Correct Answers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../../assets/images/passing-icon.png" alt="Passing Mark">
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $passing_mark ?>%</div>
                    <div class="stat-label">Passing Mark</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../../assets/images/question-icon.png" alt="Total Questions">
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $total_questions ?></div>
                    <div class="stat-label">Total Questions</div>
                </div>
            </div>
        </div>

        <div class="result-details">
            <h3>Question-by-Question Review</h3>
            <p class="result-description">Review your answers and compare with the correct answers.</p>
            
            <table class="questions-table">
                <thead>
                    <tr>
                        <th width="10%">No.</th>
                        <th width="45%">Question</th>
                        <th width="15%">Your Answer</th>
                        <th width="15%">Correct Answer</th>
                        <th width="15%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $index => $question): 
                        $user_answer = $user_answers_details[$index];
                        $user_selected_option = $user_answer['selected_option'] ?? null;
                        $user_selected_text = $user_answer['selected_text'] ?? null;
                        $correct_answer_text = $user_answer['correct_text'];
                        $is_correct = $user_answer['is_correct'];
                        $question_number = $index + 1;
                    ?>
                    <tr>
                        <td class="question-number"><?= $question_number ?></td>
                        <td class="question-cell">
                            <?= htmlspecialchars($question['question']) ?>
                            <?php if (!empty($question['picture'])): ?>
                                <img src="../../uploads/questions/<?= htmlspecialchars($question['picture']) ?>" 
                                     alt="Question Image" 
                                     class="question-image-small"
                                     onclick="this.style.maxWidth='400px'; this.style.maxHeight='400px';">
                            <?php endif; ?>
                        </td>
                        <td class="user-answer-cell">
                            <?php if ($user_selected_text !== null): ?>
                                <span class="<?= $is_correct ? 'correct-answer' : 'incorrect-answer' ?>">
                                    <?= htmlspecialchars($user_selected_text) ?>
                                </span>
                            <?php else: ?>
                                <span class="not-answered">Not Answered</span>
                            <?php endif; ?>
                        </td>
                        <td class="correct-answer-cell">
                            <span class="correct-answer">
                                <?= htmlspecialchars($correct_answer_text) ?>
                            </span>
                        </td>
                        <td class="status-cell">
                            <?php if ($user_selected_text !== null): ?>
                                <span class="status-badge <?= $is_correct ? 'status-correct' : 'status-incorrect' ?>">
                                    <?= $is_correct ? 'Correct' : 'Incorrect' ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-not-answered">Not Answered</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="result-actions">
            <a href="student_quiz.php" class="nav-btn btn-back">
                <img src="../../assets/images/back-icon.png" alt="Back">
                Back to Quiz List
            </a>
            
            <?php if ($show_retry_button): ?>
            <a href="student_quiz_detail.php?quiz_id=<?= $quiz_id ?>" class="nav-btn btn-retry" style="background: #28a745;">
                <img src="../../assets/images/retry-icon.png" alt="Retry">
                Take Quiz Again
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['recent_achievements'])): ?>
    alert("Congratulations! You've earned new achievements: <?php echo addslashes(implode(', ', $_SESSION['recent_achievements'])); ?>");
    <?php unset($_SESSION['recent_achievements']); ?>
<?php endif; ?>
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>