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

$quiz_id = $_GET['quiz_id'] ?? null;
$quiz_data = null;

if ($quiz_id) {
    // Get the quiz data for the quiz
    try {
        $stmt = $conn->prepare("SELECT * FROM quiz WHERE quiz_id = ? AND user_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("ii", $quiz_id, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to load quiz: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $quiz_data = $result->fetch_assoc();
        } else {
            $quiz_id = null;
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: officer_create_quiz.php");
        exit();
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $time_limit = intval($_POST['time_limit'] ?? 0);

    if ($title === "" || $description === "" || $time_limit <= 0) {
        $error = "Please fill in all fields.";
    } else {

        /* Handle image upload */
        $image_name = $quiz_data['picture'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../../uploads/quiz/";

            /* Auto create upload folder if not exists */
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    $error = "Failed to create upload directory";
                }
            }

            if (!$error) {
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_ext)) {
                    $error = "Only JPG, JPEG, PNG and GIF files are allowed.";
                } else {

                    /* Delete old image if exists */
                    if (!empty($quiz_data['picture']) && file_exists($target_dir . $quiz_data['picture'])) {
                        if (!unlink($target_dir . $quiz_data['picture'])) {
                            $error = "Failed to delete old image";
                        }
                    }
                    
                    if (!$error) {
                        $image_name = uniqid("quiz_") . "." . $ext;
                        $target_file = $target_dir . $image_name;

                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                            $error = "Failed to upload image";
                            $image_name = $quiz_data['picture'] ?? null;
                        }
                    }
                }
            }
        }

        if ($error === "") {
            try {
                if ($quiz_id) {
                    // Update existing draft
                    $stmt = $conn->prepare("UPDATE quiz SET title = ?, description = ?, picture = ?, time_limit = ? WHERE quiz_id = ? AND user_id = ?");
                    if (!$stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    $stmt->bind_param("sssiii", $title, $description, $image_name, $time_limit, $quiz_id, $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update quiz: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO quiz (title, description, picture, time_limit, user_id, status) VALUES (?, ?, ?, ?, ?, 'draft')");
                    if (!$stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    $stmt->bind_param("sssii", $title, $description, $image_name, $time_limit, $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create quiz: " . $stmt->error);
                    }
                    $quiz_id = $stmt->insert_id;
                    $stmt->close();
                }

                header("Location: officer_create_questions.php?quiz_id=" . $quiz_id);
                exit();
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: officer_create_quiz.php" . ($quiz_id ? "?quiz_id=$quiz_id" : ""));
                exit();
            }
        }
    }
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
    <span class = "page-title">Quiz /Create Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "<?= $main_menu_link ?>" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-seperator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-seperator">/</span>
            <a href = "officer_create_quiz.php" class = "breadcrumb-link">Create Quiz</a>
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
            <a href = "officer_quiz.php" class = "sub-link active">View Quiz</a>
            <a href = "officer_my_quiz.php" class = "sub-link">My Quiz</a>
        </div>

        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content quiz-page create-quiz-page">
        
        <div class = "create-quiz-box">

            <a href = "officer_quiz.php" class = "close-btn">
                <img src = "../../assets/images/close-icon.png" alt = "Close">
            </a>

            <h2>New Quiz</h2>

            <?php if ($error): ?>
                <p style = "color:red"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method = "POST" enctype = "multipart/form-data">
                <div class = "form-row">
                    <label>Quiz Title</label>
                    <input type = "text" name = "title" value = "<?=htmlspecialchars($quiz_data['title'] ?? '') ?>" required>
                </div>

                <div class = "form-row">
                    <label>Description</label>
                    <textarea name = "description" rows = "4" required><?= htmlspecialchars($quiz_data['description'] ?? '') ?></textarea>
                </div>

                <div class = "form-row">
                    <label>Cover Image</label>
                    <?php if (!empty($quiz_data['picture'])): ?>
                        <img src = "../../uploads/quiz/<?= htmlspecialchars($quiz_data['picture']) ?>" alt = "Current Image" style = "width: 100px; margin-bottom: 10px;">
                    <?php endif; ?>
                    <input type = "file" name = "image" id = "quizImage" accept = "image/*">
                </div>

                <div class = "form-row">
                    <label>Time Limit (minutes)</label>
                    <input type = "number" name = "time_limit" min = "1" value = "<?= htmlspecialchars($quiz_data['time_limit'] ?? '') ?>" required>
                </div>

                <div class = "quiz-form-footer">
                    <button type = "submit" class = "next-btn">
                        <img src = "../../assets/images/next-icon.png" alt = "Next">
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

document.getElementById('quizImage').addEventListener('change', function(e) {
    const file = this.files[0];
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    if (file && !allowedTypes.includes(file.type)) {
        this.value = "";
        alert("Only JPG, JPEG, PNG and GIF files are allowed.")
    }
});
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>