<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once './config.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['userID'];

$sql = "SELECT * FROM ViewUserReservations WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reservation History | Nova View Hotel</title>
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

    <div class="billing-container">
        <h2>Your Reservation History</h2>
        <table>
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Username</th>
                    <th>Room Type</th>
                    <th>Room Rate</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Status</th>
                    <th>Subtotal</th>
                    <th>Taxes</th>
                    <th>Total Cost</th>
                    <th>Billing Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ReservationID']) ?></td>
                    <td><?= htmlspecialchars($row['Username']) ?></td>
                    <td><?= htmlspecialchars($row['RoomType']) ?></td>
                    <td><?= htmlspecialchars($row['Room_Rate']) ?></td>
                    <td><?= htmlspecialchars($row['CheckInDate']) ?></td>
                    <td><?= htmlspecialchars($row['CheckOutDate']) ?></td>
                    <td><?= htmlspecialchars($row['ReservationStatus']) ?></td>
                    <td><?= htmlspecialchars($row['SubTotal']) ?></td>
                    <td><?= htmlspecialchars($row['Taxes']) ?></td>
                    <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                    <td><?= htmlspecialchars($row['BillingDate']) ?></td>
                    <td>
                        <?php if ($row['ReservationStatus'] == 'Confirmed'): ?>
                            <a href="cancel.php?reservationId=<?= $row['ReservationID'] ?>" class="back-button">Cancel Reservation</a>
                        <?php elseif ($row['ReservationStatus'] == 'Checked Out'): ?>
                            <a href="feedback.php?reservationId=<?= $row['ReservationID'] ?>" class="back-button">Give Feedback</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="guestview.php" class="back-button">Back to Profile</a>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
