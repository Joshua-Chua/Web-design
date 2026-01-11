<?php
session_start();
require '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $post_id = mysqli_real_escape_string($conn, $_POST['post_id']);
    $officer_id = mysqli_real_escape_string($conn, $_POST['officer_id']);
    $report_details = mysqli_real_escape_string($conn, $_POST['report_details']);

    // Map fields to your database schema
    $query = "INSERT INTO report (report_details, officer_id, post_id, student_id, admin_id) 
              VALUES ('$report_details', '$officer_id', '$post_id', NULL, NULL)";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Report submitted successfully.'); window.location.href='officer_forum.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: officer_forum.php");
    exit();
}
?>