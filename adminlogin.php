<?php
session_start();
require_once './config.php'; // Ensure the database configuration file path is correct

// Handle the POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Escape the input data to prevent SQL Injection
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    // Prepare SQL to check if the user exists and is an admin
    $stmt = $conn->prepare("SELECT u.UserID, p.Username, p.Password, u.UserRole FROM User u
                            JOIN Profile p ON u.UserID = p.UserID
                            WHERE p.Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Assuming '2' is the role ID for admins, modify if using role name or different ID
        if (password_verify($password, $row['Password']) && $row['UserRole'] == 2) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['Username'];
            $_SESSION['userID'] = $row['UserID'];
            $_SESSION['userRole'] = $row['UserRole']; // This should be the role ID or name that indicates an admin

            // Redirect to the admin dashboard or appropriate page
            header('Location: adminView.php');
            exit;
        } else {
            // Either the password is incorrect, or the user is not an admin
            $_SESSION['error_message'] = "Invalid login credentials or not authorized as admin.";
            header('Location: adminlogin.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "No account found with that username.";
        header('Location: adminlogin.php');
        exit;
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav id="navbar">
            <div class="container">
                <h1 class="logo"><a href="index.html">NVH</a></h1>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="login-signup-container">
        <div class="form-box">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>Admin Login</h2>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <p style="color: red;"><?= $_SESSION['error_message']; ?></p>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                <input type="text" name="username" placeholder="Enter Username" required>
                <input type="password" name="password" placeholder="Enter Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
