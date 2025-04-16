<?php
session_start();
require_once './config.php'; // Ensure this path is correct

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username']; // Retrieve the username from session

// Create a new database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$sql = "SELECT FirstName, LastName, Email, Phone, Address FROM User u JOIN Profile p ON u.UserID=p.UserID WHERE p.Username = ?";
$firstName = $lastName = $email = $phone = $address = "";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $email, $phone, $address);
    if (!$stmt->fetch()) {
        echo "No records found."; // Handle case where no user data is found
    }
    $stmt->close();
} else {
    echo "SQL error: " . $conn->error; // Better error handling for SQL preparation
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css">
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
        <div class="profile-box">
            <h2><?php echo htmlspecialchars($username); ?></h2>
            <div class="user-info">
                <div class="user-detail"><strong>First Name:</strong> <?php echo htmlspecialchars($firstName); ?></div>
                <div class="user-detail"><strong>Last Name:</strong> <?php echo htmlspecialchars($lastName); ?></div>
                <div class="user-detail"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></div>
                <div class="user-detail"><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></div>
                <div class="user-detail"><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></div>
            </div>
        </div>
        <div class="button-bar">
            <a class="btn" href="editprofile.php">Edit Profile</a>
            <a class="btn" href="reservationHistory.php">View Reservation History</a>
            <a class="btn" href="realTimeRooms.php">Check Real-Time Rooms</a>
        </div>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>






