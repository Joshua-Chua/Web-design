<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/db.php';

echo "<h2>Populating Dummy Data (Jan 2025 - Jan 2026)</h2>";

$months_list = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
];

$targets = [];

// Add 2025 (All months)
foreach ($months_list as $m) {
    $targets[] = ['month' => $m, 'year' => 2025];
}

// Add 2026 (January only)
$targets[] = ['month' => 'January', 'year' => 2026];

foreach ($targets as $target) {
    $month = $target['month'];
    $year = $target['year'];

    // Check if exists
    $stmt = $conn->prepare("SELECT emission_id FROM carbon_emission WHERE month = ? AND year = ?");
    $stmt->bind_param("si", $month, $year);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        // Insert dummy data
        // Trend up slightly? Or random.
        // Let's add varying usage
        $usage = rand(120000, 250000) / 100; // 1200.00 to 2500.00
        $carbon = $usage * 0.3; 
        
        // Random split
        $ba = $usage * 0.30 + rand(-50, 50); // Block A uses more
        $bb = $usage * 0.20 + rand(-50, 50);
        $bc = $usage * 0.25 + rand(-50, 50);
        $bd = $usage - ($ba + $bb + $bc); 

        $stmt_ins = $conn->prepare("INSERT INTO carbon_emission 
            (month, year, electricity_usage_kwh, carbon_avoided_kg, block_a_usage, block_b_usage, block_c_usage, block_d_usage)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_ins->bind_param("sidddddd", $month, $year, $usage, $carbon, $ba, $bb, $bc, $bd);
        if ($stmt_ins->execute()) {
            echo "<p>Inserted $month $year</p>";
        } else {
            echo "<p>Error inserting $month $year: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>$month $year already exists.</p>";
    }
}
?>
