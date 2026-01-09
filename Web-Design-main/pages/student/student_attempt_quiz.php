<?php
session_start();
require '../../config/db.php';

// Check auth
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'admin', 'officer'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$profile_link = 'student_profile.php';
if ($role == 'officer') $profile_link = '../officer/officer_profile.php';
if ($role == 'admin') $profile_link = '../admin/admin_profile.php';

if (!isset($_GET['quiz_id'])) {
    header("Location: student_quiz.php");
    exit();
}

$quiz_id = intval($_GET['quiz_id']);

// Fetch Quiz Info
$q_stmt = $conn->prepare("SELECT * FROM quiz WHERE quiz_id = ? AND status = 'published'");
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$quiz = $q_stmt->get_result()->fetch_assoc();

if (!$quiz) {
    echo "Quiz not found or not available.";
    exit();
}

// Fetch Questions
$quest_stmt = $conn->prepare("SELECT * FROM question WHERE quiz_id = ? ORDER BY question_number ASC");
$quest_stmt->bind_param("i", $quiz_id);
$quest_stmt->execute();
$questions = $quest_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Attempt Quiz</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <style>
        .question-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .question-text {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .options-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .option-label {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .option-label:hover {
            background: #f9f9f9;
        }
        .option-label input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #2E8B57;
        }
        .submit-quiz-btn {
            background: #2E8B57;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
        }
        .submit-quiz-btn:hover {
            background: #3CB371;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title"><?php echo htmlspecialchars($quiz['title']); ?></span>
    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
        <a href="student_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>
    </div>
    <div class="topbar-right">
        <a href="<?php echo $profile_link; ?>" class="user-link">
            <img src="../../assets/images/user-icon.png" class="user-icon">
        </a>
    </div>
</div>

<div class="dashboard">
    <!-- Sidebar (Simplified for Quiz Attempt - maybe no sidebar to reduce distraction? 
         User didn't specify, but standard consistent UI usually keeps it. 
         I'll keep it for consistency) -->
    <div class="sidebar">
        <a href="student_main.php">Main Menu</a>
        <?php if ($role == 'officer' || $role == 'admin'): ?>
            <a href="../officer/officer_monthly_report.php">Monthly Report</a>
            <a href="browse_tips.php">Smart Tips</a>
            <a href="../officer/officer_quiz.php">View Quiz</a>
        <?php else: ?>
            <a href="browse_tips.php">Smart Tips</a>
            <a href="student_quiz.php" class="active">Quiz</a>
        <?php endif; ?>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content">
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="margin-bottom: 30px;">
                <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <p style="color: #666;"><?php echo htmlspecialchars($quiz['description']); ?></p>
                <div style="margin-top: 10px; font-weight: 500; color: #2E8B57;">
                    Time Limit: <?php echo $quiz['time_limit']; ?> minutes
                </div>
            </div>

            <form action="student_quiz_result.php" method="POST">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                
                <?php 
                $q_count = 0;
                while ($q = $questions->fetch_assoc()): 
                    $q_count++;
                    $qid = $q['question_id'];
                ?>
                    <div class="question-card">
                        <div class="question-text">
                            <?php echo $q_count . ". " . htmlspecialchars($q['question']); ?>
                        </div>
                        
                        <?php if (!empty($q['picture'])): ?>
                            <img src="../../uploads/question/<?php echo htmlspecialchars($q['picture']); ?>" style="max-width: 100%; border-radius: 8px; margin-bottom: 15px;">
                        <?php endif; ?>

                        <div class="options-group">
                            <label class="option-label">
                                <input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($q['option_a']); ?>" required>
                                <span>A. <?php echo htmlspecialchars($q['option_a']); ?></span>
                            </label>
                            
                            <label class="option-label">
                                <input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($q['option_b']); ?>">
                                <span>B. <?php echo htmlspecialchars($q['option_b']); ?></span>
                            </label>
                            
                            <?php if (!empty($q['option_c'])): ?>
                            <label class="option-label">
                                <input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($q['option_c']); ?>">
                                <span>C. <?php echo htmlspecialchars($q['option_c']); ?></span>
                            </label>
                            <?php endif; ?>

                            <?php if (!empty($q['option_d'])): ?>
                            <label class="option-label">
                                <input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($q['option_d']); ?>">
                                <span>D. <?php echo htmlspecialchars($q['option_d']); ?></span>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <button type="submit" class="submit-quiz-btn">Submit Answers</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Simple Timer (Visual only for now, could auto-submit)
    const timeLimitMinutes = <?php echo $quiz['time_limit']; ?>;
    // ... Implementation optional regarding complexity, MVP first.
    
    // Sidebar Toggle
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
