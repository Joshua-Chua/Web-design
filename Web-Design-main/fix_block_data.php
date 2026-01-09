<?php
require 'config/db.php';

echo "<h2>Fixing Missing Block Data</h2>";

// Select records where Block A is 0 or NULL, but Total Usage > 0
$query = "SELECT * FROM carbon_emission WHERE (block_a_usage = 0 OR block_a_usage IS NULL) AND electricity_usage_kwh > 0";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<p>Found " . mysqli_num_rows($result) . " records to fix.</p>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['emission_id'];
        $total = $row['electricity_usage_kwh'];
        
        // Distribute Total into Blocks (Approximate ratio)
        // A: 30%, B: 20%, C: 25%, D: 25% (Remainder)
        $ba = $total * 0.30;
        $bb = $total * 0.20;
        $bc = $total * 0.25;
        $bd = $total - ($ba + $bb + $bc);
        
        $update = "UPDATE carbon_emission SET 
                   block_a_usage = '$ba', 
                   block_b_usage = '$bb', 
                   block_c_usage = '$bc', 
                   block_d_usage = '$bd' 
                   WHERE emission_id = '$id'";
                   
        if (mysqli_query($conn, $update)) {
            echo "Updated ID $id: Total $total -> A:$ba, B:$bb, C:$bc, D:$bd <br>";
        } else {
            echo "Error updating ID $id: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "Error querying DB: " . mysqli_error($conn);
}

echo "<p>Done.</p>";
?>
