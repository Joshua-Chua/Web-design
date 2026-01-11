<?php
session_start();
require '../../config/db.php';

// Allow officer only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['officer'])) {
    header("Location: ../auth/login.php");
    exit();
}

$officer_id = $_SESSION['user_id'];

// Fetch posts and JOIN with both student and officer tables to get names
// We use COALESCE to prioritize the name from whichever table has the matching ID
$post_query = "SELECT post.*, 
               student.name AS student_name, 
               officer.name AS officer_name 
               FROM post 
               LEFT JOIN student ON post.student_id = student.student_id 
               LEFT JOIN officer ON post.officer_id = officer.officer_id 
               ORDER BY post.post_id DESC";
$posts_result = mysqli_query($conn, $post_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Forum - APU Energy Sustainability</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/student/student_forum.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
</head>
<body>
<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Officer Forum</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
        <a href="officer_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>
        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="officer_main.php" class="breadcrumb-link">Officer</a>
            <span class="breadcrumb-separator">/</span>
            <a href="officer_forum.php" class="breadcrumb-link">My Forum</a>
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
        <a href="share_tips.php">Smart Tips</a>
        <a href="officer_quiz.php">Quiz</a>
        <a href="officer_forum.php" class="active">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content event-page">
        <div class="Event-header">
            <div class="header-left">
                <h2>Forum</h2>
            </div>
            <div class="header-right">
                <a href="officer_create_forum.php" class="btn-add-post">
                    <span class="plus-icon">+</span>
                    <span class="btn-text">New Announcement</span>
                </a>
            </div>
        </div>

        <div class="forum-container">
            <?php while($post = mysqli_fetch_assoc($posts_result)): ?>
                <article class="post-card">
                    <div class="post-header">
                        <span class="author">
                            Posted by: <?php 
                                // Display Officer Name if it exists, otherwise Student Name
                                if (!empty($post['officer_name'])) {
                                    echo htmlspecialchars($post['officer_name']) . " (Officer)";
                                } elseif (!empty($post['student_name'])) {
                                    echo htmlspecialchars($post['student_name']);
                                } else {
                                    echo "Anonymous";
                                }
                            ?>
                        </span>
                        <span class="date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                    </div>

                    <h3 class="post-title"><?php echo htmlspecialchars($post['post_subject']); ?></h3>
                    <p class="post-content"><?php echo nl2br(htmlspecialchars($post['post_details'])); ?></p>

                    <?php if (!empty($post['picture'])): ?>
                        <img src="../../assets/uploads/posts/<?php echo $post['picture']; ?>" alt="Post Image" class="post-img">
                    <?php endif; ?>

                    <div class="post-actions" style="display: flex; gap: 10px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                        <button class="action-btn" style="flex: 1;">React (<?php echo $post['react_id'] ?? 0; ?>)</button>
                        <button class="action-btn" style="flex: 1;">Comments</button>
                        
                        <form action="process_report.php" method="POST" style="display:inline;" onsubmit="return handleReport(this)">
                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                            <input type="hidden" name="officer_id" value="<?php echo $officer_id; ?>">
                            <input type="hidden" name="report_details" value="">
                            <button type="submit" class="action-btn" title="Report Post" style="color: #d9534f; border: 1px solid #f5c6cb; width: 45px; height: 35px; cursor: pointer;">🚩</button>
                        </form>
                    </div>

                    <div class="comment-section" style="margin-top: 25px;"> 
                        <div class="existing-comments" style="margin-bottom: 15px;">
                            <?php
                                $current_post_id = $post['post_id'];
                                // Fetch comments and JOIN with student table
                                
                                $comment_sql = "SELECT comments.*, student.name 
                                                FROM comments 
                                                LEFT JOIN student ON comments.user_id = student.student_id 
                                                WHERE comments.post_id = '$current_post_id' 
                                                ORDER BY comments.created_at ASC";
                                $comment_res = mysqli_query($conn, $comment_sql);

                                if (mysqli_num_rows($comment_res) > 0):
                                    while ($comment = mysqli_fetch_assoc($comment_res)): ?>
                                        <div class="comment-bubble" style="margin-bottom: 10px;">
                                            <strong><?php echo htmlspecialchars($comment['name'] ?? 'User #'.$comment['user_id']); ?>:</strong> 
                                            <?php echo htmlspecialchars($comment['comment_text']); ?>
                                            <div style="font-size: 0.75em; color: #888; margin-top: 4px;">
                                                <?php echo date('M d, H:i', strtotime($comment['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endwhile;
                                else: ?>
                                    <p style="font-size: 0.85em; color: #999; padding-left: 10px;">No comments yet.</p>
                            <?php endif; ?>
                        </div>
                        
                        <form action="add_comment.php" method="POST" class="comment-form" style="display: flex; align-items: center; gap: 15px;">
                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                            <input type="text" name="comment_text" placeholder="Post an official reply..." class="comment-input" required style="flex: 1;">
                            <button type="submit" class="send-comment-btn" style="width: 36px; height: 36px; border-radius: 50%; background: #004684; color: white; border: none; cursor: pointer;">➤</button>
                        </form>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function handleReport(form) {
    const details = prompt("Please provide details for this report:");
    if (details === null || details.trim() === "") {
        alert("Report cancelled. Details are required.");
        return false;
    }
    form.report_details.value = details;
    return confirm("Are you sure you want to report this post?");
}
</script>
</body>
</html>