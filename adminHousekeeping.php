<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once './config.php'; // Ensure the database configuration file path is correct

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['userRole'] != 2) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update room status to 'Available' after cleaning
function updateRoomStatus($mysqli, $roomNumber) {
    // Get the current room status to determine if it needs to be set to 'Available'
    $stmt = $mysqli->prepare("SELECT RoomStatusId FROM Room WHERE RoomNumber = ?");
    $stmt->bind_param("i", $roomNumber);
    $stmt->execute();
    $stmt->bind_result($currentStatus);
    $stmt->fetch();
    $stmt->close();

    // Update the LastCleanedDate for the room regardless of its status
    $stmt = $mysqli->prepare("UPDATE Room SET LastCleanedDate = CURDATE() WHERE RoomNumber = ?");
    $stmt->bind_param("i", $roomNumber);
    $stmt->execute();
    $stmt->close();

    // Only update the status to 'Available' if it is currently 'Needs Cleaning'
    if ($currentStatus == 3) { // 3 = Needs Cleaning
        $stmt = $mysqli->prepare("UPDATE Room SET RoomStatusId = 1 WHERE RoomNumber = ?");
        $stmt->bind_param("i", $roomNumber);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanRoom'])) {
    updateRoomStatus($conn, $_POST['roomNumber']);

    $roomNumber=$_POST['roomNumber'];
    $currentDate=date('Y-m-d');

    // Record the cleaning in the Housekeeping table
    $stmt = $conn->prepare("INSERT INTO Housekeeping (RoomNumber, CleaningDate) VALUES (?, ?)");
    $stmt->bind_param("is", $roomNumber, $currentDate);
    $stmt->execute();
    $stmt->close();

    header("Location: adminHousekeeping.php"); // Refresh the page to reflect changes
    exit;
}

// Fetch rooms that need cleaning
$stmt = $conn->prepare("SELECT RoomNumber,LastCleanedDate FROM Room WHERE RoomStatusId = 3 OR DATEDIFF(CURDATE(), LastCleanedDate) >= 1"); // 3 = Needs Cleaning
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Housekeeping | Hotel Management System</title>
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
        <h2>Rooms Needing Housekeeping</h2>
        <table>
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Last Cleaned Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['RoomNumber']) ?></td>
                    <td><?= htmlspecialchars($row['LastCleanedDate'] ? $row['LastCleanedDate'] : 'Never') ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="roomNumber" value="<?= $row['RoomNumber'] ?>">
                            <input type="hidden" name="lastCleanedDate" value="<?= $row['LastCleanedDate'] ?>">
                            <button type="submit" class="back-button" name="cleanRoom">Mark as Cleaned</button>
                        </form>
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

