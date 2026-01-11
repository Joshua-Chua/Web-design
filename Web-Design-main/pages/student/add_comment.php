<?php
session_start();
require '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $post_id = mysqli_real_escape_string($conn, $_POST['post_id']);
    $user_id = $_SESSION['user_id']; // Current logged-in student
    $comment_text = mysqli_real_escape_string($conn, $_POST['comment_text']);

    if (!empty($comment_text)) {
        $query = "INSERT INTO comments (post_id, user_id, comment_text, created_at) 
                  VALUES ('$post_id', '$user_id', '$comment_text', NOW())";
        
        if (mysqli_query($conn, $query)) {
            // Redirect back to the forum page
            header("Location: student_forum.php");
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
} else {
    header("Location: student_forum.php");
    exit();
}
?>