<?php
session_start();
require_once './config.php'; // Ensure this path is correct

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $userid=$_SESSION['userID'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match.";
    } else {
        $sql = "SELECT Password FROM Profile WHERE UserID = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            if ($stmt->fetch() && password_verify($current_password, $hashed_password)) {
                $stmt->close();

                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE Profile SET Password = ? WHERE UserID = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ss", $new_hashed_password, $userid);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Password updated successfully.";
                        header("Location: editprofile.php"); // Redirect after successful update
                        exit;
                    } else {
                        $_SESSION['error_message'] = "Error updating password.";
                    }
                    $stmt->close();
                }
            } else {
                $_SESSION['error_message'] = "Current password is incorrect.";
                $stmt->close();
            }
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Ensure this path is correct -->
</head>
<body>
    <header>
        <nav id="navbar">
            <div class="container">
                <h1 class="logo"><a href="index.html">NVH</a></h1>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="profile-container">
        <div class="signup-form-box">
            <form action="changepassword.php" method="post">
                <label>Current Password:</label>
                <input type="password" name="current_password" required>
                <label>New Password:</label>
                <input type="password" name="new_password" required>
                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password" required>
                <div class="actions">
                    <button type="submit" name="change_password">Update Password</button>
                </div>
            </form>
            <?php
                if (!empty($_SESSION['error_message'])) {
                    echo '<div style="color: red;">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
            ?>
        </div>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
