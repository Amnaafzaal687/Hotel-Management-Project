<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once './config.php'; // Ensure this path is correct

$username = $password = $confirm_password = $email = $firstName = $lastName = $phone = $address = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Assigning values from POST
    $username = trim($_POST["username"]);
    $firstName = trim($_POST["firstName"]);
    $lastName = trim($_POST["lastName"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm-password"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    // Validate username
    if (empty($username)) {
        $_SESSION['error_message'] = "Please enter a username.";
        header('Location: signuphtml.php');
        exit;
    } else {
        $sql = "SELECT u.UserID FROM User u JOIN Profile p ON p.UserID=u.UserID WHERE p.Username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $_SESSION['error_message'] = "This username is already taken.";
                header('Location: signuphtml.php');
                exit;
            }
            $stmt->close();
        }
    }

    // Validate password
    if (empty($password) || strlen($password) < 6) {
        $_SESSION['error_message'] = "Password must have at least 6 characters.";
        header('Location: signuphtml.php');
        exit;
    }

    // Confirm password validation
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Password did not match.";
        header('Location: signuphtml.php');
        exit;
    }

    // Validate email
    if (empty($email)) {
        $_SESSION['error_message'] = "Please enter your email.";
        header('Location: signuphtml.php');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check input errors before inserting in database
    if (empty($_SESSION['error_message'])) {
        $sql = "CALL RegisterUser( ?, ?, ?, ?, ?, ?, ?, @UserID)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssss", $username, $firstName, $lastName, $email, $phone, $address, $hashed_password);
            if ($stmt->execute()) {
                // Fetch the output parameter (UserID)
                $result = $conn->query("SELECT @UserID");
                $row = $result->fetch_assoc();
                $last_id = $row['@UserID'];

                if (!$last_id) {
                    $_SESSION['error_message'] = "Error creating user profile.";
                    header('Location: signuphtml.php');
                    exit;
                }

                // Redirect after successful registration
                header("location: loginsignup.php");
                exit;
            } else {
                $_SESSION['error_message'] = "Error registering user.";
                header('Location: signuphtml.php');
                exit;
            }
        }
    }

    // Close connection
    $conn->close();
}
?>