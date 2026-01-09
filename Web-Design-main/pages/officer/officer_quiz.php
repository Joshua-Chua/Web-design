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

$user_id = $_SESSION['user_id'];

/* Fetch quizzes created */
$search = $_GET['search'] ?? '';
$db_error = false;
$result = false;

try {
    // Use prepared statements instead of mysqli_real_escape_string for better security
    $query = "SELECT * FROM quiz WHERE status = 'published'";
    
    if (!empty($search)) {
        $query .= " AND title LIKE ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $search_param = "%$search%";
            $stmt->bind_param("s", $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
    } else {
        $result = mysqli_query($conn, $query);
        if (!$result) {
            throw new Exception("Database error: " . mysqli_error($conn));
        }
    }
} catch (Exception $e) {
    $db_error = true;
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
    <span class = "page-title">Quiz /View Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">View Quiz</a>
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

    <div class = 'content quiz-page'>
        <div class = "quiz-header">
            <h2>Quiz</h2>

            <form method = "GET" class = "quiz-search">
                <div class = "search-wrapper">
                    <img src = "../../assets/images/search-icon.png" class = "search-icon">
                    <input type = "text" name = "search" placeholder = "Search quiz..." value = "<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <a href = "officer_create_quiz.php" class = "create-quiz-btn">
            <img src = "../../assets/images/plus-icon.png">
        </a>

        <div class = "quiz-grid">

        <?php if ($db_error): ?>
            <div class = "no-quiz-text">Something went wrong. Please try again later.</div>

        <?php elseif ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="quiz-card">
                    <a href="officer_quiz_detail.php?quiz_id=<?= $row['quiz_id'] ?>" class="quiz-card-link">
                        <img src="../../uploads/quiz/<?= htmlspecialchars($row['picture']) ?>" alt="Quiz Image">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>
                    </a>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class = "no-quiz-text">
                No quiz found. Click <strong>Create Quiz</strong> to get started.
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