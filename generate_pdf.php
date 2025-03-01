<?php
require 'dompdf/autoload.inc.php'; // Include the DOMPDF library

use Dompdf\Dompdf;
use Dompdf\Options;

// Database connection
$mysqli = new mysqli("localhost", "root", "", "toonhub");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get filter option
$filter = $_GET['filter'] ?? 'all'; // Default is 'all'

// Query based on filter
if ($filter == 'all') {
    $query = "SELECT * FROM users where role != 'admin'";
} elseif ($filter == 'active') {
    $query = "SELECT * FROM users WHERE is_active = 1";
} elseif ($filter == 'inactive') {
    $query = "SELECT * FROM users WHERE is_active = 0";
} elseif ($filter == 'selected') {
    $user_id = $_GET['user_id'] ?? null;
    if ($user_id) {
        $query = "SELECT * FROM users WHERE id = $user_id";
    } else {
        die("No user ID provided for selected filter.");
    }
} else {
    die("Invalid filter option.");
}

// Fetch data
$result = $mysqli->query($query);
if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Count total users
$total_users = $result->num_rows;

// Generate HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>User List</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>User List</h1>
    <p>Total Users: ' . $total_users . '</p>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

// Add rows to the table
$serial_no = 1; // Initialize serial number
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $serial_no++ . '</td>
        <td>' . $row['ID'] . '</td>
        <td>' . $row['Name'] . '</td>
        <td>' . $row['Username'] . '</td>
        <td>' . $row['Email'] . '</td>
        <td>' . ($row['is_active'] ? 'Active' : 'Inactive') . '</td>
    </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Initialize DOMPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// Load HTML into DOMPDF
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Output the PDF
$dompdf->stream("user_list.pdf", ["Attachment" => 1]); // 1 for download, 0 for inline view
?>
