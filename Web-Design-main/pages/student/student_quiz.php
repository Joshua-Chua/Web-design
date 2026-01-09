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

/* Fetch quizzes created */
$search = $_GET['search'] ?? '';
$db_error = false;
$result = false;

try {
    // Build the query with prepared statements to avoid SQL injection
    $base_query = "SELECT * FROM quiz WHERE status = 'published'";
    
    // Check if we need to filter out completed quizzes
    $check_completion_query = "SELECT quiz_id FROM quiz_attempt WHERE user_id = ? AND quiz_completed = 'Completed'";
    $stmt_check = $conn->prepare($check_completion_query);
    if (!$stmt_check) {
        throw new Exception("Failed to prepare completion check: " . $conn->error);
    }
    $stmt_check->bind_param("i", $user_id);
    if (!$stmt_check->execute()) {
        throw new Exception("Failed to execute completion check: " . $stmt_check->error);
    }
    $completion_result = $stmt_check->get_result();
    
    $completed_quiz_ids = [];
    while ($row = $completion_result->fetch_assoc()) {
        $completed_quiz_ids[] = $row['quiz_id'];
    }
    $stmt_check->close();
    
    // Build the main query
    $query = $base_query;
    
    // Add condition to exclude completed quizzes if there are any
    if (!empty($completed_quiz_ids)) {
        $placeholders = implode(',', array_fill(0, count($completed_quiz_ids), '?'));
        $query .= " AND quiz_id NOT IN ($placeholders)";
    }
    
    // Add search condition if provided
    if (!empty($search)) {
        $search_param = "%$search%";
        $query .= " AND title LIKE ?";
    }
    
    // Prepare and execute the main query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Bind parameters
    $param_types = "";
    $params = [];
    
    // Add completed quiz IDs parameters
    if (!empty($completed_quiz_ids)) {
        $param_types .= str_repeat("i", count($completed_quiz_ids));
        $params = array_merge($params, $completed_quiz_ids);
    }
    
    // Add search parameter if provided
    if (!empty($search)) {
        $param_types .= "s";
        $params[] = $search_param;
    }
    
    // Bind parameters if we have any
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $stmt->close();
} catch (Exception $e) {
    $db_error = true;
    $_SESSION['error'] = "Database error occurred. Please try again.";
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
    <link rel = "stylesheet" href = "../../assets/css/student/student_quiz.css">
</head>
<body>
    
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "student_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "student_quiz.php" class = "breadcrumb-link">Quiz</a>
        </span>
    </div>

    <div class = "topbar-right">
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "student_profile.php">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">

    <div class = "sidebar">
        <a href = "student_main.php">Main Menu</a>
        <a href = "#">Events</a>
        <a href = "browse_tips.php">Smart Tips</a>
        <a href = "student_quiz.php" class = "active">Quiz</a>
        <a href = "student_achievement.php">Achievement</a>
        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = 'content quiz-page quiz-details-page'>
        <div class = "quiz-header">
            <h2>Quiz</h2>

            <form method = "GET" class = "quiz-search">
                <div class = "search-wrapper">
                    <img src = "../../assets/images/search-icon.png" class = "search-icon">
                    <input type = "text" name = "search" placeholder = "Search quiz..." value = "<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <div class = "quiz-grid">

        <?php if ($db_error): ?>
            <div class = "no-quiz-text">Something went wrong. Please try again later.</div>

        <?php elseif ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="quiz-card">
                    <a href="student_quiz_detail.php?quiz_id=<?= $row['quiz_id'] ?>" class="quiz-card-link">
                        <img src="../../uploads/quiz/<?= htmlspecialchars($row['picture']) ?>" alt="Quiz Image">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>
                    </a>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class = "no-quiz-text">
                No quiz found. <?= !empty($search) ? 'Try a different search term.' : 'All quizzes have been completed or no quizzes are available.' ?>
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
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>