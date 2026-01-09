<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/db.php';

echo "<h2>Database Debugger</h2>";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<p>Database connected.</p>";

// List tables
echo "<h3>Tables:</h3><ul>";
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
} else {
    echo "<li>Error listing tables: " . $conn->error . "</li>";
}
echo "</ul>";

// Check quiz table columns
echo "<h3>Columns in 'quiz' table:</h3><ul>";
$result = $conn->query("SHOW COLUMNS FROM quiz");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
} else {
    echo "<li>Error showing columns for 'quiz': " . $conn->error . "</li>";
}
echo "</ul>";

// Try the failing query
echo "<h3>Testing Query: SELECT * FROM quiz WHERE status = 'published'</h3>";
$query = "SELECT * FROM quiz WHERE status = 'published'";
$result = $conn->query($query);

if ($result) {
    echo "<p>Query successful. Rows: " . $result->num_rows . "</p>";
} else {
    echo "<p style='color:red'>Query Failed: " . $conn->error . "</p>";
}
?>
