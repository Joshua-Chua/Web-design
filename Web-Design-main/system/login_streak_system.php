<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class LoginStreakSystem {
    private $conn;
    private $user_id;
    
    public function __construct($conn, $user_id) {
        if (!$conn) {
            throw new Exception("Database connection is required");
        }
        
        $this->conn = $conn;
        $this->user_id = $user_id;
    }
    
    /* Update login streak for a student */
    public function updateLoginStreak() {
        try {
            // Check if user already has a login streak record
            $existing_record = $this->getUserLoginStreak();
            
            if ($existing_record) {
                // Update existing record
                return $this->updateExistingStreak($existing_record);
            } else {
                // Create new record
                return $this->createNewStreak();
            }
        } catch (Exception $e) {
            error_log("Login Streak System Error: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to update login streak: " . $e->getMessage();
            }
            return false;
        }
    }
    
    /* Get user's current login streak record */
    private function getUserLoginStreak() {
        try {
            $query = "SELECT * FROM login_streak WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            $stmt->close();
            
            return $record;
        } catch (Exception $e) {
            throw new Exception("Failed to get user login streak: " . $e->getMessage());
        }
    }
    
    /* Create a new login streak record for user */
    private function createNewStreak() {
        try {
            $today = date('Y-m-d');
            
            $query = "INSERT INTO login_streak (user_id, last_login_date, current_streak_days) 
                      VALUES (?, ?, 1)";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("is", $this->user_id, $today);
            
            if ($stmt->execute()) {
                $stmt->close();
                return [
                    'success' => true,
                    'current_streak_days' => 1,
                    'message' => 'New login streak started'
                ];
            } else {
                $stmt->close();
                throw new Exception("Failed to create new streak: " . $this->conn->error);
            }
        } catch (Exception $e) {
            throw new Exception("Failed to create new streak: " . $e->getMessage());
        }
    }
    
    /* Update existing login streak record */
    private function updateExistingStreak($record) {
        try {
            $today = date('Y-m-d');
            $last_login_date = $record['last_login_date'] ?? null;
            
            if (!$last_login_date) {
                throw new Exception("Invalid last login date in record");
            }
            
            // Check if already logged in today
            if ($today == $last_login_date) {
                // Already logged in today, streak remains the same
                return [
                    'success' => true,
                    'current_streak_days' => $record['current_streak_days'] ?? 0,
                    'message' => 'Already logged in today'
                ];
            }
            
            // Calculate days difference
            $last_login = new DateTime($last_login_date);
            $current_date = new DateTime($today);
            $interval = $last_login->diff($current_date);
            $days_difference = (int)$interval->format('%a');
            
            $new_streak_days = $record['current_streak_days'] ?? 0;
            
            if ($days_difference == 1) {
                // Consecutive day - increment streak
                $new_streak_days = ($record['current_streak_days'] ?? 0) + 1;
            } elseif ($days_difference > 1) {
                // Break in streak - reset to 1
                $new_streak_days = 1;
            }
            // If days_difference is 0, it's already handled above
            
            // Update the record
            $query = "UPDATE login_streak 
                      SET last_login_date = ?, 
                          current_streak_days = ?,
                          updated_at = NOW()
                      WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("sii", $today, $new_streak_days, $this->user_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                return [
                    'success' => true,
                    'current_streak_days' => $new_streak_days,
                    'previous_streak_days' => $record['current_streak_days'] ?? 0,
                    'days_difference' => $days_difference,
                    'message' => $days_difference == 1 ? 'Streak increased' : 
                               ($days_difference > 1 ? 'Streak reset' : 'No change')
                ];
            } else {
                $stmt->close();
                throw new Exception("Failed to update streak: " . $this->conn->error);
            }
        } catch (Exception $e) {
            throw new Exception("Failed to update existing streak: " . $e->getMessage());
        }
    }
    
    /* Get user's current streak days */
    public function getCurrentStreakDays() {
        try {
            $record = $this->getUserLoginStreak();
            
            if ($record) {
                // Check if last login was today or yesterday
                $today = date('Y-m-d');
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $last_login_date = $record['last_login_date'] ?? null;
                
                if ($last_login_date && ($last_login_date == $today || $last_login_date == $yesterday)) {
                    return $record['current_streak_days'] ?? 0;
                } else {
                    // Streak broken - return 0
                    return 0;
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Failed to get current streak days: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to get streak days: " . $e->getMessage();
            }
            return 0;
        }
    }
    
    /* Get user's maximum streak days */
    public function getMaxStreakDays() {
        try {
            return $this->getCurrentStreakDays();
        } catch (Exception $e) {
            error_log("Failed to get max streak days: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to get max streak: " . $e->getMessage();
            }
            return 0;
        }
    }
    
    /* Reset user's streak */
    public function resetStreak() {
        try {
            $query = "DELETE FROM login_streak WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to reset streak: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to reset streak: " . $e->getMessage();
            }
            return false;
        }
    }
    
    /* Get all login streak records */
    public function getAllStreaks() {
        try {
            $query = "SELECT ls.*, u.username, u.email 
                      FROM login_streak ls
                      JOIN users u ON ls.user_id = u.user_id
                      ORDER BY ls.current_streak_days DESC, ls.last_login_date DESC";
            
            $result = $this->conn->query($query);
            
            if (!$result) {
                throw new Exception("Failed to execute query: " . $this->conn->error);
            }
            
            $streaks = [];
            while ($row = $result->fetch_assoc()) {
                $streaks[] = $row;
            }
            
            return $streaks;
        } catch (Exception $e) {
            error_log("Failed to get all streaks: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to get all streaks: " . $e->getMessage();
            }
            return [];
        }
    }
}
?>