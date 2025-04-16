<?php
session_start();
require_once './config.php'; // Adjust the path as necessary

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username']; // Username from session
$userRole = $_SESSION['userRole']; // User role from session
$data_err = $password_err = "";

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch existing data
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $sql = "SELECT FirstName, LastName, Email, Phone, Address FROM User u JOIN Profile p ON u.UserID=p.UserID WHERE p.Username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($firstName, $lastName, $email, $phone, $address);
        $stmt->fetch();
        $stmt->close();
    }
}


// Update profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $firstName = trim($_POST["firstName"]);
    $lastName = trim($_POST["lastName"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $userId = $_SESSION['userID']; // Get the UserID from session

    echo $userId;

    $sql = "UPDATE User SET FirstName = ?, LastName = ?, Email = ?, Phone = ?, Address = ? WHERE UserID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $address, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect based on user role
    if ($userRole == 2) { // Assuming '2' is the Admin role ID
        header('Location: adminView.php');
    } else {
        header('Location: guestview.php');
    }
    exit;
}


$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Nova View Hotel</title>
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
        <div class="signup-form-box">
            <h2><?php echo htmlspecialchars($username); ?></h2>

            <!-- Profile Update Form -->
            <form action="editprofile.php" method="post">
                <label>First Name:</label>
                <input type="text" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                <label>Last Name:</label>
                <input type="text" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <label>Phone:</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                <div class="actions">
                    <button type="submit" name="update">Save Changes</button>
                    <!-- Dynamic redirect based on user role -->
                    <a href="<?php echo ($userRole == 2) ? 'adminView.php' : 'guestview.php'; ?>" class="btn">Cancel</a>
                </div>
            </form>
        
        </div>

        <div class="actions">
            <a href="changepassword.php" class="btn">Change Password</a> <!-- Link to change password page -->
        </div>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>

