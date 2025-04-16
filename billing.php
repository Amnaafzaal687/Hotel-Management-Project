<?php
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

$reservationId = $_GET['reservationId'] ?? null; // Ensure this variable is not null

if (!$reservationId) {
    die('Reservation ID is required.');
}

$sql = "SELECT * FROM ViewRoomBillingInfo WHERE ReservationID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();
$billingInfo = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Summary | Nova View Hotel</title>
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
        <h2>Billing Summary</h2>
        <?php if ($billingInfo): ?>
            <table>
                <tr>
                    <th>Room Type</th>
                    <td><?= htmlspecialchars($billingInfo['TypeName']) ?></td>
                </tr>
                <tr>
                    <th>Room Rate</th>
                    <td><?= htmlspecialchars($billingInfo['Room_Rate']) ?></td>
                </tr>
                <tr>
                    <th>Check-In Date</th>
                    <td><?= htmlspecialchars($billingInfo['CheckInDate']) ?></td>
                </tr>
                <tr>
                    <th>Check-Out Date</th>
                    <td><?= htmlspecialchars($billingInfo['CheckOutDate']) ?></td>
                </tr>
                <tr>
                    <th>Subtotal</th>
                    <td><?= htmlspecialchars($billingInfo['SubTotal']) ?></td>
                </tr>
                <tr>
                    <th>Taxes</th>
                    <td><?= htmlspecialchars($billingInfo['Taxes']) ?></td>
                </tr>
                <tr>
                    <th>Total Cost</th>
                    <td><?= htmlspecialchars($billingInfo['TotalCost']) ?></td>
                </tr>
                <tr>
                    <th>Billing Date</th>
                    <td><?= htmlspecialchars($billingInfo['BillingDate']) ?></td>
                </tr>
            </table>
            <a href="realTimeRooms.php" class="back-button">Back to Rooms</a>
        <?php else: ?>
            <p>No billing information available.</p>
        <?php endif; ?>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
