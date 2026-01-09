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

try {
    // Fetch quiz details
    $quiz_query = "SELECT * FROM quiz WHERE quiz_id = ? AND status = 'published'";
    $stmt = $conn->prepare($quiz_query);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $quiz_result = $stmt->get_result();
    $quiz = $quiz_result->fetch_assoc();
    $stmt->close();

    if (!$quiz) {
        $_SESSION['error'] = 'Quiz not found or not published!';
        header("Location: student_quiz.php");
        exit();
    }

    // Check if the student has already completed this quiz
    $completed_query = "SELECT * FROM quiz_attempt WHERE user_id = ? AND quiz_id = ? AND quiz_completed = 'Completed' LIMIT 1";
    $stmt = $conn->prepare($completed_query);
    $stmt->bind_param("ii", $user_id, $quiz_id);
    $stmt->execute();
    $completed_result = $stmt->get_result();
    $stmt->close();

    if ($completed_result->num_rows > 0) {
        $_SESSION['error'] = 'You have already completed this quiz.';
        header("Location: student_quiz.php");
        exit();
    }

    // Get previous attempts count
    $attempt_count_query = "SELECT COUNT(*) as count FROM quiz_attempt WHERE user_id = ? AND quiz_id = ? AND quiz_completed = 'Not Completed'";
    $stmt = $conn->prepare($attempt_count_query);
    $stmt->bind_param("ii", $user_id, $quiz_id);
    $stmt->execute();
    $attempt_count_result = $stmt->get_result();
    $attempt_count_data = $attempt_count_result->fetch_assoc();
    $attempt_count = $attempt_count_data['count'] ?? 0;
    $stmt->close();

    // Calculate the attempt number
    $attempt_number = $attempt_count + 1;

    // Fetch all questions for this quiz
    $question_query = "SELECT * FROM question WHERE quiz_id = ? ORDER BY question_number ASC";
    $stmt = $conn->prepare($question_query);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $questions_result = $stmt->get_result();
    $questions = [];
    while ($row = $questions_result->fetch_assoc()) {
        $questions[] = $row;
    }
    $stmt->close();

    $total_questions = count($questions);

    if ($total_questions === 0) {
        $_SESSION['error'] = 'This quiz has no questions yet.';
        header("Location: student_quiz.php");
        exit();
    }

    // Initialize session for quiz attempt if not exists
    if (!isset($_SESSION['quiz_attempt']) || $_SESSION['quiz_attempt']['quiz_id'] != $quiz_id) {
        $_SESSION['quiz_attempt'] = [
            'quiz_id' => $quiz_id,
            'attempt_number' => $attempt_number,
            'answers' => array_fill(0, $total_questions, null),
            'start_time' => time(),
            'current_question' => 0,
            'total_questions' => $total_questions,
            'quiz_duration' => $quiz['time_limit'] ?? 0,
            'questions' => $questions,
            'timer_paused' => false,
            'paused_at' => null,
            'elapsed_time' => 0,
            'last_activity' => time() // Track last activity for timer calculation
        ];
    } else {
        // Update existing session with correct duration from database
        $_SESSION['quiz_attempt']['quiz_duration'] = $quiz['time_limit'] ?? 0;
    }

    // Get current question from session or GET parameter
    if (isset($_GET['question'])) {
        $current_question = intval($_GET['question']);
        // Validate question index
        if ($current_question >= 0 && $current_question < $total_questions) {
            $_SESSION['quiz_attempt']['current_question'] = $current_question;
        } else {
            $current_question = $_SESSION['quiz_attempt']['current_question'] ?? 0;
        }
    } else {
        $current_question = $_SESSION['quiz_attempt']['current_question'] ?? 0;
    }

    // Save incomplete attempt
    function saveQuitAttempt($conn, $user_id, $quiz_id, $attempt_number) {
        try {
            $score = 0.00;
            $quiz_completed = 'Not Completed';

            $insert_attempt = "INSERT INTO quiz_attempt (user_id, quiz_id, score, quiz_completed, attempted_count) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_attempt);
            $stmt->bind_param(
                "iidsi",
                $user_id,
                $quiz_id,
                $score,
                $quiz_completed,
                $attempt_number
            );

            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to save quiz attempt: ' . $e->getMessage();
            return false;
        }
    }

    // Handle quit quiz
    if (isset($_GET['quit'])) {
        if (isset($_SESSION['quiz_attempt'])) {
            // Save quit attempt with score 0
            if (saveQuitAttempt($conn, $user_id, $quiz_id, $attempt_number)) {
                // Check for redirect parameter
                $redirect_url = 'student_quiz.php';
                
                // Get the specified redirect
                if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                    // Sanitize the redirect URL
                    $redirect_url = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
                    if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+\.php$/', $redirect_url)) {
                        $redirect_url = 'student_quiz.php';
                    }
                }
                
                // Clear session
                unset($_SESSION['quiz_attempt']);
                header("Location: $redirect_url");
                exit();
            } else {
                $_SESSION['error'] = 'Failed to save quiz attempt. Please try again.';
                header("Location: student_quiz.php");
                exit();
            }
        } else {
            header("Location: student_quiz.php");
            exit();
        }
    }

    // Handle answer auto-save via AJAX or immediate save
    if (isset($_POST['save_answer'])) {
        try {
            $selected_option = $_POST['answer'] ?? null;
            $current_q_id = $_POST['question_id'] ?? 0;
            $question_index = $_POST['question_index'] ?? $current_question;
            
            // Validate question index
            if ($question_index >= 0 && $question_index < $total_questions) {
                // Store answer in session
                $_SESSION['quiz_attempt']['answers'][$question_index] = [
                    'question_id' => $current_q_id,
                    'selected_option' => $selected_option,
                    'submitted' => true
                ];
                
                // Return success response for AJAX
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => true, 'message' => 'Answer saved']);
                    exit();
                }
            }
        } catch (Exception $e) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => 'Failed to save answer']);
                exit();
            } else {
                $_SESSION['error'] = 'Failed to save answer. Please try again.';
            }
        }
    }

    // Handle answer submission and navigation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
        try {
            $selected_option = $_POST['answer'] ?? null;
            $current_q_id = $_POST['question_id'] ?? 0;
            $question_index = $_POST['question_index'] ?? $current_question;

            // Store answer in session (if not already saved)
            if (!isset($_SESSION['quiz_attempt']['answers'][$question_index]) || 
                $_SESSION['quiz_attempt']['answers'][$question_index]['selected_option'] !== $selected_option) {
                $_SESSION['quiz_attempt']['answers'][$question_index] = [
                    'question_id' => $current_q_id,
                    'selected_option' => $selected_option,
                    'submitted' => true
                ];
            }

            // Move to next question
            $current_question++;
            $_SESSION['quiz_attempt']['current_question'] = $current_question;

            if ($current_question >= $total_questions) {
                // Pause timer before going to summary page
                $_SESSION['quiz_attempt']['timer_paused'] = true;
                $_SESSION['quiz_attempt']['paused_at'] = time();
                header("Location: student_quiz_summary.php?quiz_id=$quiz_id");
                exit();
            } else {
                header("Location: student_start_quiz.php?quiz_id=$quiz_id&question=$current_question");
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to process answer. Please try again.';
            header("Location: student_start_quiz.php?quiz_id=$quiz_id&question=$current_question");
            exit();
        }
    }

    // Resume timer if coming from summary page
    if (isset($_GET['resume']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'student_quiz_summary.php') !== false)) {
        if ($_SESSION['quiz_attempt']['timer_paused']) {
            $paused_at = $_SESSION['quiz_attempt']['paused_at'] ?? time();
            $elapsed_pause_time = time() - $paused_at;
            $_SESSION['quiz_attempt']['elapsed_time'] += $elapsed_pause_time;
            $_SESSION['quiz_attempt']['timer_paused'] = false;
            $_SESSION['quiz_attempt']['paused_at'] = null;
            
            // Update last activity time
            $_SESSION['quiz_attempt']['last_activity'] = time();
            
            // If we came with a resume parameter, redirect to remove it from URL
            if (isset($_GET['resume'])) {
                header("Location: student_start_quiz.php?quiz_id=$quiz_id&question=$current_question");
                exit();
            }
        }
    }

    // Get current question data
    $current_question_data = $questions[$current_question] ?? null;

    // Calculate time remaining
    $time_remaining = 0;
    $quiz_duration = $_SESSION['quiz_attempt']['quiz_duration'] ?? 0;
    $has_timer = $quiz_duration > 0;

    if ($has_timer) {
        $start_time = $_SESSION['quiz_attempt']['start_time'];
        $duration_seconds = $quiz_duration * 60;
        $elapsed_time = $_SESSION['quiz_attempt']['elapsed_time'] ?? 0;
        
        // Calculate time spent on quiz (excluding pauses)
        $current_time = time();
        $last_activity = $_SESSION['quiz_attempt']['last_activity'] ?? $start_time;
        
        if ($_SESSION['quiz_attempt']['timer_paused'] && isset($_SESSION['quiz_attempt']['paused_at'])) {
            // Timer is paused - use paused time
            $time_spent = ($_SESSION['quiz_attempt']['paused_at'] - $start_time) - $elapsed_time;
        } else {
            // Timer is running
            $time_spent = ($current_time - $start_time) - $elapsed_time;
            
            // Update last activity
            $_SESSION['quiz_attempt']['last_activity'] = $current_time;
        }
        
        $time_remaining = max(0, $duration_seconds - $time_spent);
    }

    // Check current answer status
    $answers = $_SESSION['quiz_attempt']['answers'] ?? [];
    
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred while loading the quiz. Please try again.';
    header("Location: student_quiz.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - APU Energy Sustainability</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_quiz.css">
    <link rel="stylesheet" href="../../assets/css/student/student_quiz.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    <div class="content quiz-taking-page">
        <div class="quiz-header-bar">
            <div class="quiz-progress">
                <div style="font-size: 14px; color: #6c757d; margin-top: 5px;">
                    <?= htmlspecialchars($quiz['title']) ?>
                </div>
            </div>

            <div class="quiz-timer-section">
                <div class="timer-container" style="<?= !$has_timer ? 'opacity: 0.5;' : '' ?>">
                    <img src="../../assets/images/clock-icon.png" alt="Timer" class="timer-icon">
                    <span class="timer-display" id="timer">
                        <?php if ($has_timer && $time_remaining > 0): ?>
                            <?= floor($time_remaining / 60) ?>:<?= str_pad($time_remaining % 60, 2, '0', STR_PAD_LEFT) ?>
                        <?php elseif ($has_timer): ?>
                            00:00
                        <?php else: ?>
                            No Limit
                        <?php endif; ?>
                    </span>
                </div>

                <div style="display: flex; gap: 10px;">
                    <a href="student_quiz_summary.php?quiz_id=<?= $quiz_id ?>" class="nav-btn btn-summary" style="background: #17a2b8;">
                        <img src="../../assets/images/summary-icon.png" alt="Summary">
                        View Summary
                    </a>

                    <a href="?quiz_id=<?= $quiz_id ?>&quit=true" class="quit-btn" onclick="return confirm('Are you sure you want to quit this quiz attempt?');">
                        <img src="../../assets/images/close-icon.png" alt="Close">
                        Quit Quiz
                    </a>
                </div>
            </div>
        </div>

        <div class="question-container">
            <form method="POST" action="" id="quizForm">
                <input type="hidden" name="question_id" value="<?= $current_question_data['question_id'] ?? '' ?>">
                <input type="hidden" name="question_index" value="<?= $current_question ?>">
                <input type="hidden" name="submit_answer" value="1">

                <div class="question-number">Question <?= $current_question + 1 ?></div>

                <div class="question-text">
                    <?= htmlspecialchars($current_question_data['question'] ?? '') ?>
                </div>

                <?php if (!empty($current_question_data['picture'])): ?>
                    <div class="question-image-container">
                        <img src="../../uploads/questions/<?= htmlspecialchars($current_question_data['picture']) ?>" alt="Question Image" class="question-image">
                    </div>
                <?php endif; ?>

                <div class="options-grid">
                    <?php
                    $options = [
                        'A' => $current_question_data['option_a'] ?? '',
                        'B' => $current_question_data['option_b'] ?? '',
                    ];

                    if (!empty($current_question_data['option_c'])) {
                        $options['C'] = $current_question_data['option_c'];
                    }

                    if (!empty($current_question_data['option_d'])) {
                        $options['D'] = $current_question_data['option_d'];
                    }

                    foreach ($options as $letter => $text):
                        if (!empty($text)):
                            $is_selected = false;
                            $current_answer = $answers[$current_question] ?? null;
                            if ($current_answer && $current_answer['selected_option'] === $letter) {
                                $is_selected = true;
                            }
                    ?>
                    <div class="option-rectangle <?= $is_selected ? 'selected' : '' ?>" 
                         data-letter="<?= $letter ?>" 
                         onclick="selectAndSaveOption('<?= $letter ?>', <?= $current_question_data['question_id'] ?? 0 ?>, <?= $current_question ?>)">
                        <input type="radio" name="answer" value="<?= $letter ?>" id="option<?= $letter ?>" class="option-input" <?= $is_selected ? 'checked' : '' ?>>
                        <label for="option<?= $letter ?>" class="option-label">
                            <div class="option-letter"><?= $letter ?></div>
                            <div class="option-text"><?= htmlspecialchars($text) ?></div>
                        </label>
                    </div>
                    <?php 
                        endif;
                    endforeach;
                    ?>
                </div>
            </form>

            <div class="question-progress">
                <?php for ($i = 0; $i < $total_questions; $i++):
                    $answer = $answers[$i] ?? null;
                    $status = '';
                    if ($i == $current_question) {
                        $status = 'current';
                    } elseif ($answer && $answer['selected_option'] !== null) {
                        $status = 'answered';
                    } elseif ($answer && $answer['selected_option'] === null && $answer['submitted'] === true) {
                        $status = 'skipped';
                    }
                ?>
                <a href="?quiz_id=<?= $quiz_id ?>&question=<?= $i ?>" class="progress-dot <?= $status ?>">
                    <?= $i + 1 ?>
                    <?php if ($status === 'answered' && isset($answers[$i]['selected_option'])): ?>
                        <span class="progress-label"><?= $answers[$i]['selected_option'] ?></span>
                    <?php elseif ($status === 'skipped'): ?>
                        <span class="progress-label">Skip</span>
                    <?php endif; ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>

        <div class="quiz-navigation">
            <?php if ($current_question > 0): ?>
                <a href="?quiz_id=<?= $quiz_id ?>&question=<?= $current_question - 1 ?>" class="nav-btn btn-prev">
                    <img src="../../assets/images/back-icon.png" alt="Back">
                    Previous
                </a>
            <?php else: ?>
                <button class="nav-btn btn-prev" disabled>
                    <img src="../../assets/images/back-icon.png" alt="Back">
                    Previous
                </button>
            <?php endif; ?>

            <div style="display: flex; gap: 10px;">
                <button type="button" class="nav-btn" style="background: #ffc107;" onclick="skipQuestion()">
                    Skip Question
                    <img src="../../assets/images/next-icon.png" alt="Skip">
                </button>

                <?php if ($current_question < $total_questions - 1): ?>
                    <button type="submit" form="quizForm" class="nav-btn btn-next">
                        Next Question
                        <img src="../../assets/images/next-icon.png" alt="Next">
                    </button>
                <?php else: ?>
                    <button type="submit" form="quizForm" class="nav-btn btn-summary">
                        Go to Summary
                        <img src="../../assets/images/summary-icon.png" alt="Summary">
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Flag to track allowed navigation
let isAllowedNavigation = false;

<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function () {

    // Intercept sidebar navigation clicks
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave this quiz? Your current attempt will be marked as "Not Completed".')) {
                // Redirect to quit page with the desired URL as redirect parameter
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                isAllowedNavigation = true;
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
                // Redirect to quit page with the desired URL as redirect parameter
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                isAllowedNavigation = true;
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
                // Redirect to quit page with the desired URL as redirect parameter
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                isAllowedNavigation = true;
                window.location.href = quitUrl;
            }
        });
    }

    // Intercept more menu links
    document.querySelectorAll('.more-menu-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave this quiz? Your current attempt will be marked as "Not Completed".')) {
                // Redirect to quit page with the desired URL as redirect parameter
                const quitUrl = `?quiz_id=<?= $quiz_id ?>&quit=true&redirect=${encodeURIComponent(this.href)}`;
                isAllowedNavigation = true;
                window.location.href = quitUrl;
            }
        });
    });

    // Mark allowed navigation for quiz action
    document.querySelectorAll('.nav-btn, .quit-btn, .btn-summary, .progress-dot').forEach(button => {
        button.addEventListener('click', function() {
            isAllowedNavigation = true;
        });
    });

    // Mark allowed navigation for form submission
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        quizForm.addEventListener('submit', function() {
            isAllowedNavigation = true;
        });
    }

    // Timer functionality
    <?php if ($has_timer && $time_remaining > 0 && !$_SESSION['quiz_attempt']['timer_paused']): ?>
    let timeRemaining = <?= $time_remaining ?>;
    const timerElement = document.getElementById('timer');

    function updateTimer() {
        if (timeRemaining <= 0) {
            // Time's up
            timerElement.textContent = "00:00";
            alert("Time's up! Redirecting to summary...");
            window.location.href = 'student_quiz_summary.php?quiz_id=<?= $quiz_id ?>&time_expired=1';
            return;
        }
    
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
    
        timerElement.textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
    
        // Add warning class when less than 5 minutes
        if (timeRemaining < 300) {
            timerElement.classList.add('warning');
        }
    
        timeRemaining--;
    }

    // Update timer every second
    setInterval(updateTimer, 1000);
    updateTimer();
    <?php endif; ?>
});

// Save answer immediately when option is selected
function selectAndSaveOption(letter, questionId, questionIndex) {
    // Remove selected class from all options
    document.querySelectorAll('.option-rectangle').forEach(option => {
        option.classList.remove('selected');
    });

    // Add selected class to clicked option
    const selectedOption = document.querySelector(`#option${letter}`).closest('.option-rectangle');
    if (selectedOption) {
        selectedOption.classList.add('selected');
        document.getElementById(`option${letter}`).checked = true;
        
        // Save answer immediately via AJAX
        saveAnswerImmediately(letter, questionId, questionIndex);
    }
}

// Function to save answer immediately via AJAX
function saveAnswerImmediately(answer, questionId, questionIndex) {
    $.ajax({
        url: '',
        type: 'POST',
        data: {
            save_answer: 1,
            answer: answer,
            question_id: questionId,
            question_index: questionIndex,
            ajax: 1
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    // Update progress dot visually
                    const progressDot = document.querySelector(`.progress-dot[href*="question=${questionIndex}"]`);
                    if (progressDot) {
                        progressDot.classList.add('answered');
                        progressDot.querySelector('.progress-label').textContent = answer;
                    }
                }
            } catch (e) {
                // Response wasn't JSON, but that's okay - the answer was saved on the server
            }
        },
        error: function() {
            console.log('Error saving answer');
        }
    });
}

// Function to skip question
function skipQuestion() {
    // Mark as allowed navigation
    isAllowedNavigation = true;
    
    // Uncheck all radio buttons
    document.querySelectorAll('input[name="answer"]').forEach(radio => {
        radio.checked = false;
    });

    // Submit the form to save skipped status
    document.getElementById('quizForm').submit();
}

// Prevent accidental page refresh only for non-quiz navigation
window.addEventListener('beforeunload', function(e) {
    if (!isAllowedNavigation) {
        e.preventDefault();
        e.returnValue = 'Your quiz progress will be lost if you leave this page. Are you sure?';
    }
});
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>