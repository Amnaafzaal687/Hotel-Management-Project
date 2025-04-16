<?php
session_start();
require_once './config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['userRole'] != 2) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';

// Fetch room types for selection
$typeStmt = $conn->prepare("SELECT TypeID, TypeName FROM RoomType");
$typeStmt->execute();
$typeResult = $typeStmt->get_result();
$roomTypes = [];
while ($type = $typeResult->fetch_assoc()) {
    $roomTypes[$type['TypeID']] = $type['TypeName'];
}
$typeStmt->close();

// Handling POST requests for room operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $roomNumber = $_POST['roomNumber'];
    $typeId = $_POST['typeId'];
    $statusId = $_POST['statusId'];

    // Validate statusId
    if (!in_array($statusId, [1, 2, 3])) {
        $error_message = 'Invalid room status ID.';
    } else {
        try {
            if (isset($_POST['add'])) {
                // Check if room number already exists
                $checkStmt = $conn->prepare("SELECT RoomNumber FROM Room WHERE RoomNumber = ?");
                $checkStmt->bind_param("i", $roomNumber);
                $checkStmt->execute();
                if ($checkStmt->fetch()) {
                    $error_message = 'Room number already exists.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO Room (RoomNumber, RoomTypeId, RoomStatusId) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $roomNumber, $typeId, $statusId);
                    $stmt->execute();
                }
                $checkStmt->close();
            } elseif (isset($_POST['update'])) {
                $stmt = $conn->prepare("UPDATE Room SET RoomTypeId = ?, RoomStatusId = ? WHERE RoomNumber = ?");
                $stmt->bind_param("iii", $typeId, $statusId, $roomNumber);
                $stmt->execute();
            } elseif (isset($_POST['delete'])) {
                $stmt = $conn->prepare("DELETE FROM Room WHERE RoomNumber = ?");
                $stmt->bind_param("i", $roomNumber);
                $stmt->execute();
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all rooms for display
$roomsStmt = $conn->prepare("SELECT RoomNumber, RoomTypeId, RoomStatusId FROM Room");
$roomsStmt->execute();
$roomsResult = $roomsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | Admin Panel</title>
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
        <?php if ($error_message): ?>
            <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <h2>Current Rooms</h2>
        <table>
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Room Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($room = $roomsResult->fetch_assoc()): ?>
                <tr>
                    <form method="post">
                        <td><input type="number" name="roomNumber" value="<?= $room['RoomNumber'] ?>" readonly></td>
                        <td>
                            <select name="typeId">
                                <?php foreach ($roomTypes as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= $id == $room['RoomTypeId'] ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="statusId" value="<?= $room['RoomStatusId'] ?>"></td>
                        <td>
                            <button type="submit" name="update">Update</button>
                            <button type="submit" name="delete">Delete</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Add New Room</h2>
        <form method="post">
            <label>Room Number:</label>
            <input type="number" name="roomNumber" required>

            <label>Room Type:</label>
            <select name="typeId">
                <?php foreach ($roomTypes as $id => $name): ?>
                    <option value="<?= $id ?>"><?= $name ?></option>
                <?php endforeach; ?>
            </select>

            <label>Status:</label>
            <input type="number" name="statusId" required>

            <button type="submit" name="add">Add Room</button>
        </form>

        <a href="adminView.php" class="back-button">Back to Admin Profile</a>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
