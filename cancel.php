<?php
session_start();
require_once './config.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Ensure there is a reservation ID to process
if (!isset($_GET['reservationId'])) {
    header('Location: reservationHistory.php?error=No reservation specified');
    exit;
}

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservationId = $_GET['reservationId'];
$userId = $_SESSION['userID'];  // Make sure session variable name matches your setup

// Fetch the reservation details
$stmt = $conn->prepare("SELECT CheckInDate, BillingID FROM Reservations WHERE ReservationID = ? AND UserID = ?");
$stmt->bind_param("ii", $reservationId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    $conn->close();
    header('Location: reservationHistory.php?error=Reservation not found or access denied');
    exit;
}

$row = $result->fetch_assoc();
$checkInDate = new DateTime($row['CheckInDate']);
$billingId = $row['BillingID'];
$now = new DateTime();
$hours = $now->diff($checkInDate)->h + ($now->diff($checkInDate)->days * 24);

// Determine the refund percentage
$refundPercentage = 0;
if ($hours >= 48) {
    $refundPercentage = 100;
} elseif ($hours >= 24) {
    $refundPercentage = 50;
} else {
    $refundPercentage = 0;
}

// Fetch the original billing amount
$stmt = $conn->prepare("SELECT TotalCost FROM Billing WHERE BillingID = ?");
$stmt->bind_param("i", $billingId);
$stmt->execute();
$result = $stmt->get_result();
$billingRow = $result->fetch_assoc();
$originalAmount = $billingRow['TotalCost'];
$refundAmount = ($refundPercentage / 100.0) * $originalAmount;

// Create a new billing record for the refund
$stmt = $conn->prepare("INSERT INTO Billing (BillingStatusId, SubTotal, Taxes, TotalCost, Date, RefundBillingID) VALUES (?, ?, ?, ?, CURDATE(), NULL)");
$negativeTotal = -$refundAmount;
$zero = 0; // Assuming no taxes on the refund
$billingStatusId = 1; // Assuming '1' for normal billing status
$stmt->bind_param("iddi", $billingStatusId, $negativeTotal, $zero, $negativeTotal);
$stmt->execute();
$refundBillingId = $stmt->insert_id;

// Update the original billing record with the refund billing ID
$stmt = $conn->prepare("UPDATE Billing SET RefundBillingID = ? WHERE BillingID = ?");
$stmt->bind_param("ii", $refundBillingId, $billingId);
$stmt->execute();

// Update the reservation status to cancelled
$cancelledStatusId = 3; // Assuming '3' is the ID for 'Cancelled'
$stmt = $conn->prepare("UPDATE Reservations SET ReservationStatusID = ? WHERE ReservationID = ?");
$stmt->bind_param("ii", $cancelledStatusId, $reservationId);
$stmt->execute();

$stmt->close();
$conn->close();

header('Location: reservationHistory.php?success=Reservation cancelled and refund processed successfully');
exit;
?>
