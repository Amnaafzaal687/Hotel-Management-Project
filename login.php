<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once './config.php';  // Ensure this path is correct

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

    // Prepare SQL to prevent SQL Injection and check role ID
    // Assuming the role ID for guests is stored in the User table and is 1 for guests
    $stmt = $conn->prepare("SELECT p.UserID, p.Username, p.Password, u.UserRole 
                            FROM Profile p 
                            JOIN User u ON p.UserID = u.UserID 
                            WHERE p.Username = ? AND u.UserRole = 1");  // Check only for guests
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the user exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verify the password against the hashed password in the database
        if (password_verify($password, $row['Password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['Username'];
            $_SESSION['userID'] = $row['UserID'];  // Storing user ID in session
            $_SESSION['userRole'] = $row['UserRole']; // Storing user role in session

            // Redirect to the guest view page
            header('Location: guestview.php');
            exit;
        } else {
            // Password is not correct
            $_SESSION['error_message'] = "Invalid password";
            header('Location: loginsignup.php');
            exit;
        }
    } else {
        // No user found or not a guest
        $_SESSION['error_message'] = "No account found with that username or not authorized as guest";
        header('Location: loginsignup.php');
        exit;
    }

    // Close connection
    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    echo "Please submit the form.";
}
?>
