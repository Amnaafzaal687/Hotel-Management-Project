<?php
session_start();
require_once './config.php'; // Ensure the database configuration file path is correct

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['userRole'] != 2) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function updateReservationAndRoomStatus($mysqli, $reservationId, $statusId, $roomStatusId) {
    // Update the reservation status
    $stmt = $mysqli->prepare("UPDATE Reservations SET ReservationStatusID = ? WHERE ReservationID = ?");
    $stmt->bind_param("ii", $statusId, $reservationId);
    $stmt->execute();
    $stmt->close();

    // Get room number from reservation
    $stmt = $mysqli->prepare("SELECT RoomNum FROM Reservations WHERE ReservationID = ?");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $stmt->bind_result($roomNum);
    $stmt->fetch();
    $stmt->close();

    // Update the room status
    $stmt = $mysqli->prepare("UPDATE Room SET RoomStatusId = ? WHERE RoomNumber = ?");
    $stmt->bind_param("ii", $roomStatusId, $roomNum);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        // Assuming '2' for Checked In and '2' for Occupied
        updateReservationAndRoomStatus($conn, $_POST['reservationId'], 2, 2);
    } elseif (isset($_POST['check_out'])) {
        // Assuming '3' for Checked Out and '3' for Needs Cleaning
        updateReservationAndRoomStatus($conn, $_POST['reservationId'], 3, 3);
    }
    header("Location: adminCheckInOut.php"); // Refresh the page to reflect changes
    exit;
}

$dateToday = date('Y-m-d');
$stmt = $conn->prepare("SELECT r.ReservationID, u.FirstName, u.LastName, rt.TypeName, r.CheckInDate, r.CheckOutDate, rs.StatusName 
                        FROM Reservations r
                        JOIN User u ON r.UserID = u.UserID
                        JOIN Room ro ON ro.RoomNumber=r.RoomNum
                        JOIN RoomType rt ON ro.RoomTypeId = rt.TypeID
                        JOIN ReservationStatus rs ON r.ReservationStatusID = rs.StatusID
                        WHERE ( r.CheckInDate = ? OR r.CheckOutDate = ?) AND rs.StatusName <> 'Cancelled'");
$stmt->bind_param("ss", $dateToday, $dateToday);
$stmt->execute();
$reservations = $stmt->get_result();
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Check-In/Out | Hotel Management System</title>
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
        <h2>Today's Arrivals and Departures</h2>
        <table>
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest Name</th>
                    <th>Room Type</th>
                    <th>Check-In Date</th>
                    <th>Check-Out Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $reservations->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ReservationID']) ?></td>
                    <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                    <td><?= htmlspecialchars($row['TypeName']) ?></td>
                    <td><?= htmlspecialchars($row['CheckInDate']) ?></td>
                    <td><?= htmlspecialchars($row['CheckOutDate']) ?></td>
                    <td><?= htmlspecialchars($row['StatusName']) ?></td>
                    <td>
                        <?php if ($dateToday == $row['CheckInDate'] && $row['StatusName'] == 'Confirmed'): ?>
                            <form method="post">
                                <input type="hidden" name="reservationId" value="<?= $row['ReservationID'] ?>">
                                <button type="submit" class="back-button" name="check_in">Check In</button>
                            </form>
                        <?php elseif ($dateToday == $row['CheckOutDate'] && $row['StatusName'] == 'Checked In'): ?>
                            <form method="post">
                                <input type="hidden" name="reservationId" value="<?= $row['ReservationID'] ?>">
                                <button type="submit" class="back-button" name="check_out">Check Out</button>
                            </form>
                        <?php else: ?>
                            No action available
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="adminView.php" class="back-button">Back to Admin Profile</a>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
    
</body>
</html>
