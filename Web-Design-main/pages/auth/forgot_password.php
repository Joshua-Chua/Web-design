<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
</head>
<body>

<div class = "login-wrapper">
    <div class = "logo">
        <img src = "../../assets/images/apu-logo.png" alt = "APU logo">
    </div>

    <div class = "login-container">

        <?php
        if (isset($_GET['error'])) {
            if ($_GET['error'] === 'invalidID') {
                echo "<p class = 'error-msg'>Invalid Identical Number</p>";
            } elseif ($_GET['error'] === 'nouser') {
                echo "<p class = 'error-msg'>User not found</p>";
            }
        }
        ?>

        <form method = "POST" action = "forgot_process.php">

            <div class = "form-group">
                <label>Username</label>
                <input type = "text" name = "username" placeholder = "Username" required>
            </div>

            <div class = "form-group">
                <label>Identical Number</label>
                <input type = "text" name = "identical_number" placeholder = "Identilcal Number" required>
            </div>

            <div class = "back-link">
                <a href = "login.php">Back to Login</a>
            </div>

            <button type = "submit">Verify</button>

        </form>
    </div>
</div>

</body>
</html>