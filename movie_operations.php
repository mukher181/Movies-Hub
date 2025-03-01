<?php
require 'config.php';

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';

// Response array
$response = ['success' => false];

if ($type === 'fetch') {
    // Fetch all movies
    $query = "SELECT * FROM movies";
    $result = mysqli_query($conn, $query);

    $movies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $movies[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($movies);
    exit;

} elseif ($type === 'delete') {
    // Delete a movie
    $id = $data['id'] ?? 0;

    $query = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $response['success'] = true;
    }

    $stmt->close();

} elseif ($type === 'toggle') {
    // Toggle movie status
    $id = $data['id'] ?? 0;
    $status = $data['status'] === 'active' ? 1 : 0;

    $query = "UPDATE movies SET is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $status, $id);

    if ($stmt->execute()) {
        $response['success'] = true;
    }

    $stmt->close();
}
// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';

if ($type === 'fetch') {
    // Fetch all movies
    $query = "SELECT * FROM movies";
    $result = mysqli_query($conn, $query);

    $movies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $movies[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($movies);
    exit;
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);


?>
