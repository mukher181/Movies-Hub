<?php
// toggle_status.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        // Get the user ID and new status from the request body
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = filter_var($data['id'], FILTER_VALIDATE_INT);
        $status = $data['status']; // 'active' or 'inactive'

        // Validate input
        if (!$userId || !in_array($status, ['active', 'inactive'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        // Convert status to integer for database
        $statusInt = ($status === 'active') ? 1 : 0;

        // Database connection
        $pdo = new PDO('mysql:host=localhost;dbname=toonhub', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Update the user's status
        $stmt = $pdo->prepare('UPDATE users SET is_active = :status WHERE id = :id');
        $stmt->bindParam(':status', $statusInt, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
        }
    } catch (Exception $e) {
        // Return error response
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
