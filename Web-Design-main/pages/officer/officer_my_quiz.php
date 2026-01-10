<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['officer', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Set dynamic navigation based on role
if ($user_role === 'admin') {
    $main_menu_link = '../../pages/admin/admin_main.php';
    $profile_link = '../../pages/admin/admin_profile.php';
} else {
    $main_menu_link = 'officer_main.php';
    $profile_link = 'officer_profile.php';
}

/* Filters */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

/* Base query */
$query = "SELECT * FROM quiz WHERE user_id = ?";
$params = [$user_id];
$types = "i";

/* Status filter */
if ($status === 'draft' || $status === 'published') {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

/* Search filter */
if (!empty($search)) {
    $query .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$result = false;
try {
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute query: " . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception("Failed to get result: " . mysqli_error($conn));
    }
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
    <span class = "page-title">Quiz /My Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "<?= $main_menu_link ?>" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_quiz" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_my_quiz" class = "breadcrumb-link">My Quiz</a>
        </span>
    </div>

    <div class = "topbar-right">
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "<?= $profile_link ?>">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">

    <div class = "sidebar">
        <a href = "<?= $main_menu_link ?>">Main Menu</a>
        <a href = "officer_monthly_report.php">Monthly Report</a>
        <a href = "officer_event.php">Events</a>
        <a href = "../../pages/student/browse_tips.php">Smart Tips</a>

        <div class = "sidebar-group">
            <a href = "officer_quiz.php" class = "active">Quiz</a>
            <a href = "officer_quiz.php" class = "sub-link">View Quiz</a>
            <a href = "officer_my_quiz.php" class = "sub-link active">My Quiz</a>
        </div>

        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content quiz-page">
        <div class = "quiz-header">
            <h2>My Quiz</h2>

            <form method = "GET" class = "quiz-search">
                <div class = "search-wrapper">
                    <img src = "../../assets/images/search-icon.png" class = "search-icon">
                    <input type = "text" name = "search" placeholder = "Search quiz..." value = "<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <div class = "quiz-filter">
            <a href = "?status=all" class = "<?= $status === 'all' ? 'active' : '' ?>">All</a>
            <a href = "?status=draft" class = "<?= $status === 'draft' ? 'active' : '' ?>">Draft</a>
            <a href = "?status=published" class = "<?= $status === 'published' ? 'active' : '' ?>">Published</a>
        </div>

        <div class = "quiz-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class = "quiz-card">

                        <img src = "../../uploads/quiz/<?= htmlspecialchars($row['picture']) ?>">

                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>

                        <span class = "quiz-status <?= $row['status'] ?> ">
                            <?= ucfirst($row['status']) ?>
                        </span>

                        <?php if ($row['status'] === 'draft'): ?>
                            <a href = "officer_create_quiz.php?quiz_id=<?= $row['quiz_id'] ?>" class = "quiz-action">Continue Editing</a>
                        <?php else: ?>
                            <a href = "officer_quiz_detail.php?quiz_id=<?= $row['quiz_id'] ?>" class = "quiz-action">View</a>
                        <?php endif; ?>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class = "no-quiz-text">
                    No <?= $status !== 'all' ? $status : '' ?> quiz found.
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