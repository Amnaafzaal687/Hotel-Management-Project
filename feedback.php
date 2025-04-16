<?php
session_start();
require_once './config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';
$reservationId = $_GET['reservationId'] ?? null;

// Establish a database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if feedback already exists
if ($reservationId) {
    $stmt = $conn->prepare("SELECT FeedbackID FROM Feedback WHERE ReservationID = ?");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errorMessage = "Feedback already submitted for this reservation.";
    }
    $stmt->close();
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $feedbackText = $_POST['feedback'];
    $rating = $_POST['rating'];

    if (empty($errorMessage)) {
        $stmt = $conn->prepare("INSERT INTO Feedback (ReservationID, FeedbackText, Rating) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $reservationId, $feedbackText, $rating);
        if ($stmt->execute()) {
            $successMessage = "Thank you for your feedback!";
        } else {
            $errorMessage = "Error submitting feedback: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css">
    <style>

        .billing-container textarea, .billing-container input[type="number"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box; /* Includes padding and border in the element's total width and height */
            margin-bottom: 10px;
        }

    </style>
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

    <div class="billing-container">
        <h2>Feedback</h2>
        <?php if ($errorMessage): ?>
            <p style="color: red;"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <p style="color: green;"><?= htmlspecialchars($successMessage) ?></p>
        <?php else: ?>
            <form action="" method="post">
                <label for="feedback">Feedback:</label>
                <textarea id="feedback" name="feedback" required></textarea>
                <label for="rating">Rating (1-5):</label>
                <input type="number" id="rating" name="rating" min="1" max="5" required>
                <button type="submit" name="submit_feedback">Submit Feedback</button>
            </form>
        <?php endif; ?>
        <a href="reservationHistory.php" class="back-button">Back to Reservations</a>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
