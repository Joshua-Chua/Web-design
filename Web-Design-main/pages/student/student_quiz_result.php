<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || !isset($_POST['quiz_id'])) {
    header("Location: student_quiz.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$quiz_id = intval($_POST['quiz_id']);
$user_answers = $_POST['answers'] ?? [];

// Fetch Quiz Info
$q_stmt = $conn->prepare("SELECT * FROM quiz WHERE quiz_id = ?");
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$quiz = $q_stmt->get_result()->fetch_assoc();

if (!$quiz) {
    echo "Quiz Error.";
    exit();
}

// Fetch Correct Answers
$a_stmt = $conn->prepare("SELECT question_id, answer, question FROM question WHERE quiz_id = ?");
$a_stmt->bind_param("i", $quiz_id);
$a_stmt->execute();
$result_questions = $a_stmt->get_result();

$score = 0;
$total_questions = 0;
$results_detail = [];

while ($row = $result_questions->fetch_assoc()) {
    $total_questions++;
    $qid = $row['question_id'];
    $correct_answer = $row['answer'];
    $user_answer = $user_answers[$qid] ?? '';
    
    $is_correct = ($user_answer === $correct_answer);
    if ($is_correct) {
        $score++;
    }

    $results_detail[] = [
        'question' => $row['question'],
        'correct_answer' => $correct_answer,
        'user_answer' => $user_answer,
        'is_correct' => $is_correct
    ];
}

$percentage = ($total_questions > 0) ? round(($score / $total_questions) * 100) : 0;

$profile_link = 'student_profile.php';
if ($role == 'officer') $profile_link = '../officer/officer_profile.php';
if ($role == 'admin') $profile_link = '../admin/admin_profile.php';

// (Optional) Save result to database if there was a `quiz_attempts` table.
// User schema doesn't seem to have one, so we just display it.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <style>
        .result-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .score-display {
            font-size: 4rem;
            font-weight: 800;
            color: #2E8B57;
            margin: 20px 0;
        }
        .score-text {
            font-size: 1.2rem;
            color: #666;
        }
        .detail-list {
            text-align: left;
            margin-top: 40px;
        }
        .detail-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-item.correct {
            border-left: 5px solid #2ecc71;
            background: #f0fff4;
        }
        .detail-item.wrong {
            border-left: 5px solid #e74c3c;
            background: #fff5f5;
        }
        .btn-home {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: #333;
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Quiz Result</span>
    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
    </div>
    <div class="topbar-right">
        <a href="<?php echo $profile_link; ?>" class="user-link">
            <img src="../../assets/images/user-icon.png" class="user-icon">
        </a>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="student_main.php">Main Menu</a>
        <a href="student_quiz.php">Back to Quiz List</a>
    </div>

    <div class="content">
        <div class="result-card">
            <h2>You Scored</h2>
            <div class="score-display"><?php echo $percentage; ?>%</div>
            <p class="score-text"><?php echo $score; ?> out of <?php echo $total_questions; ?> Correct</p>

            <a href="student_quiz.php" class="btn-home">Done</a>
        </div>

        <div style="max-width: 800px; margin: 40px auto;">
            <h3>Review</h3>
            <div class="detail-list">
                <?php foreach ($results_detail as $idx => $res): ?>
                    <div class="detail-item <?php echo $res['is_correct'] ? 'correct' : 'wrong'; ?>">
                        <p><strong>Q<?php echo $idx+1; ?>: <?php echo htmlspecialchars($res['question']); ?></strong></p>
                        <p>Your Answer: <?php echo htmlspecialchars($res['user_answer']); ?> 
                           <?php if($res['is_correct']): ?> 
                               <span style="color:green">&#10004;</span>
                           <?php else: ?>
                               <span style="color:red">&#10008;</span>
                           <?php endif; ?>
                        </p>
                        <?php if(!$res['is_correct']): ?>
                            <p style="color: #2ecc71; font-size: 0.9em;">Correct Answer: <?php echo htmlspecialchars($res['correct_answer']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.querySelector(".sidebar");
    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });
    }
</script>

</body>
</html>
