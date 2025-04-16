<?php
session_start();
require_once './config.php'; // Ensure this path is correct

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];

// MySQL connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve User ID based on username
$userSql = "SELECT p.UserID FROM User u JOIN Profile p ON u.UserID=p.UserID WHERE Username = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userStmt->bind_result($userID);
$userStmt->fetch();
$userStmt->close();

// Initialize variables
$result = null;
$checkInDate = '';
$numOfNights = '';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserveRoom'])) {
    $roomType = $_POST['TypeName'];
    $checkInDate = $_POST['checkInDate'];
    $numOfNights = $_POST['numOfNights'];
    $checkOutDate = date('Y-m-d', strtotime("+$numOfNights days", strtotime($checkInDate)));
    $roomRate = $_POST['roomRate'];
    $taxRate = $_POST['taxRate'];

    // First, select a random available room of the specified type that is still available
    $selectRoomSql = "SELECT RoomNumber FROM RoomInfo WHERE TypeName = ? AND RoomNumber NOT IN (
                      SELECT RoomNum FROM Reservations WHERE NOT (CheckOutDate <= ? OR CheckInDate >= ?)
                      ) ORDER BY RAND() LIMIT 1";
    $selectStmt = $conn->prepare($selectRoomSql);
    $selectStmt->bind_param("sss", $roomType, $checkInDate, $checkOutDate);
    $selectStmt->execute();
    $selectStmt->bind_result($roomNumber);
    if ($selectStmt->fetch()) {
        $selectStmt->close();
        
        // Proceed to reserve this room
        if ($reserveStmt = $conn->prepare("CALL MakeReservation(?, ?, ?, ?, ?, ?, @reservationId)")) {
            $reserveStmt->bind_param("iisidd", $userID, $roomNumber, $checkInDate, $numOfNights, $roomRate, $taxRate);
            $reserveStmt->execute();
            $reserveStmt->close();
        
            // Retrieve the reservation ID
            $result = $conn->query("SELECT @reservationId AS reservationId");
            $row = $result->fetch_assoc();
            $reservationId = $row['reservationId'];
        
            if ($reservationId) {
                header("Location: billing.php?reservationId=" . $reservationId);
                exit;
            } else {
                echo "<script>alert('Failed to get reservation ID');</script>";
            }
        }
        
    } else {
        echo "<script>alert('No available rooms of the selected type.');</script>";
        $selectStmt->close();
    }
}


// Fetch available rooms based on user input for date and number of nights
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkAvailability'])) {
    $checkInDate = $_POST['checkInDate'];
    $numOfNights = $_POST['numOfNights'];
    $checkOutDate = date('Y-m-d', strtotime("+$numOfNights days", strtotime($checkInDate)));

    $sql = "SELECT TypeName, COUNT(*) AS AvailableCount, MAX(Room_Rate) AS Rate_Per_Night, MAX(Tax_Rate) AS Tax 
            FROM RoomInfo 
            WHERE RoomNumber NOT IN (
                SELECT RoomNum FROM Reservations
                WHERE NOT (CheckOutDate <= ? OR CheckInDate >= ?)
            ) GROUP BY TypeName";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $checkInDate, $checkOutDate);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Room | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        function validateForm() {
            var checkInDate = document.getElementById("checkInDate").value;
            var numOfNights = document.getElementById("numOfNights").value;
            if (!checkInDate || numOfNights < 1) {
                alert("Please enter a valid check-in date and number of nights must be at least 1.");
                return false;
            }
            return true;
        }
    </script>
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
    <div class="room-container">
        <h2>Check Room Availability</h2>
        <form method="post" onsubmit="return validateForm();">
            <label for="checkInDate">Check-in Date:</label>
            <input type="date" id="checkInDate" name="checkInDate" required>
            <label for="numOfNights">Number of Nights:</label>
            <input type="number" id="numOfNights" name="numOfNights" required>
            <button type="submit" name="checkAvailability">Check Availability</button>
            <button type="button" onclick="window.location.href='guestview.php';">Back</button>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Available Rooms</th>
                    <th>Rate (per night)</th>
                    <th>Tax (%)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['TypeName']) ?></td>
                        <td><?= htmlspecialchars($row['AvailableCount']) ?></td>
                        <td><?= htmlspecialchars($row['Rate_Per_Night']) ?></td>
                        <td><?= htmlspecialchars($row['Tax']) ?></td>
                        <td>
                            <form action="" method="post">
                                <input type="hidden" name="TypeName" value="<?= $row['TypeName'] ?>">
                                <input type="hidden" name="checkInDate" value="<?= $checkInDate ?>">
                                <input type="hidden" name="numOfNights" value="<?= $numOfNights ?>">
                                <input type="hidden" name="roomRate" value="<?= $row['Rate_Per_Night'] ?>">
                                <input type="hidden" name="taxRate" value="<?= $row['Tax'] ?>">
                                <button type="submit" name="reserveRoom">Reserve</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No rooms available for the selected dates. Please try different dates.</p>
        <?php endif; ?>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>