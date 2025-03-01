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
    header("location: total_movies.php");
    exit();
}

$id = $_GET['id'];

// Get movie info to delete poster file
$sql = "SELECT poster FROM movies WHERE id = $id";
$result = mysqli_query($conn, $sql);
$movie = mysqli_fetch_assoc($result);

// Delete the movie from database
$sql = "DELETE FROM movies WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    // Delete the poster file if it exists
    if ($movie && !empty($movie['poster']) && file_exists($movie['poster'])) {
        unlink($movie['poster']);
    }
    header("location: total_movies.php");
} else {
    echo "Error deleting movie: " . mysqli_error($conn);
}
?>
