<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once 'login_streak_system.php';
} catch (Exception $e) {
    // Set error in session if session is active
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['error'] = "Failed to load login streak system: " . $e->getMessage();
    }
    // Return default values
    return [];
}

/* Update login streak after successful student login */
function updateStudentLoginStreak($conn, $user_id, $user_role = 'student') {
    // Only track streaks for students
    if ($user_role !== 'student') {
        return null;
    }
    
    try {
        if (!$conn) {
            throw new Exception("Database connection is required");
        }
        
        if (!$user_id || $user_id <= 0) {
            throw new Exception("Valid user ID is required");
        }
        
        $streak_system = new LoginStreakSystem($conn, $user_id);
        $result = $streak_system->updateLoginStreak();
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to update student login streak: " . $e->getMessage());
        // Set session error if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to update login streak: " . $e->getMessage();
        }
        return null;
    }
}

/* Get student's current login streak */
function getStudentLoginStreak($conn, $user_id) {
    try {
        if (!$conn) {
            throw new Exception("Database connection is required");
        }
        
        if (!$user_id || $user_id <= 0) {
            throw new Exception("Valid user ID is required");
        }
        
        $streak_system = new LoginStreakSystem($conn, $user_id);
        return $streak_system->getCurrentStreakDays();
    } catch (Exception $e) {
        error_log("Failed to get student login streak: " . $e->getMessage());
        // Set session error if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to get login streak: " . $e->getMessage();
        }
        return 0;
    }
}

/* Get all streaks for admin view */
function getAllLoginStreaks($conn) {
    try {
        if (!$conn) {
            throw new Exception("Database connection is required");
        }
        
        $streak_system = new LoginStreakSystem($conn, null);
        return $streak_system->getAllStreaks();
    } catch (Exception $e) {
        error_log("Failed to get all login streaks: " . $e->getMessage());
        // Set session error if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to get all login streaks: " . $e->getMessage();
        }
        return [];
    }
}
?>