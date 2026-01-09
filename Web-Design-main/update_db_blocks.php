<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/db.php';

echo "<h2>Database Migration: Add Blocks to Carbon Emission</h2>";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Check if column exists
$result = $conn->query("SHOW COLUMNS FROM carbon_emission LIKE 'block_a_usage'");
if ($result && $result->num_rows > 0) {
    echo "<p>Columns already exist.</p>";
} else {
    // 2. Add columns
    $sql = "ALTER TABLE carbon_emission 
            ADD COLUMN block_a_usage decimal(10,2) DEFAULT 0 AFTER carbon_avoided_kg,
            ADD COLUMN block_b_usage decimal(10,2) DEFAULT 0 AFTER block_a_usage,
            ADD COLUMN block_c_usage decimal(10,2) DEFAULT 0 AFTER block_b_usage,
            ADD COLUMN block_d_usage decimal(10,2) DEFAULT 0 AFTER block_c_usage";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Successfully added block columns.</p>";
        
        // 3. Update existing data (distribute total)
        $update = "UPDATE carbon_emission SET 
                   block_a_usage = electricity_usage_kwh * 0.25,
                   block_b_usage = electricity_usage_kwh * 0.25,
                   block_c_usage = electricity_usage_kwh * 0.25,
                   block_d_usage = electricity_usage_kwh * 0.25
                   WHERE block_a_usage = 0";
        if ($conn->query($update) === TRUE) {
            echo "<p>Populated existing rows with dummy split data.</p>";
        } else {
            echo "<p>Error updating data: " . $conn->error . "</p>";
        }

    } else {
        echo "<p>Error adding columns: " . $conn->error . "</p>";
    }
}
?>
