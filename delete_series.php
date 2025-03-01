<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("location: admin_login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("location: total_series.php");
    exit();
}

$id = $_GET['id'];

// Get series info to delete poster file
$sql = "SELECT poster FROM series WHERE id = $id";
$result = mysqli_query($conn, $sql);
$series = mysqli_fetch_assoc($result);

// Delete the series from database
$sql = "DELETE FROM series WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    // Delete the poster file if it exists
    if ($series && !empty($series['poster']) && file_exists($series['poster'])) {
        unlink($series['poster']);
    }
    header("location: total_series.php");
} else {
    echo "Error deleting series: " . mysqli_error($conn);
}
?>
