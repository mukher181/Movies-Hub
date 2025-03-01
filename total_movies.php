<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("location: admin_login.php");
    exit();
}

// Pagination settings
$recordsPerPage = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Get total number of records
$totalRecordsQuery = "SELECT COUNT(*) as total FROM movies";
$totalRecordsResult = mysqli_query($conn, $totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Calculate total pages
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get movies with pagination
$query = "SELECT * FROM movies ORDER BY id DESC LIMIT $offset, $recordsPerPage";
$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Movies - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .main-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            white-space: nowrap;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .pagination a {
            padding: 8px 16px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #0056b3;
        }

        .movie-thumbnail {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
        }

        .description-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upload-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .upload-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Total Movies</h1>
            <div class="action-buttons">
            <a href="admin_dashboard.php" class="upload-button">Home</a>
                <a href="upload_movie.php" class="upload-button">
                    <i class="fas fa-plus"></i> Upload New Movie
                </a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Main Category</th>
                    <th>Sub Category</th>
                    
                    <th>Release Date</th>
                    <th>Duration (mins)</th>
                    <th>Age Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <img src="<?php echo $row['poster']; ?>" alt="Movie Poster" class="movie-thumbnail">
                    </td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="description-cell" title="<?php echo htmlspecialchars($row['description']); ?>">
                        <?php echo htmlspecialchars($row['description']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['maincategory']); ?></td>
                    <td><?php echo htmlspecialchars($row['subcategory']); ?></td>
                    
                    <td><?php echo htmlspecialchars($row['release_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['duration']); ?></td>
                    <td><?php echo htmlspecialchars($row['age_rating']); ?></td>
                    <td>
                        <a href="edit_movie.php?id=<?php echo $row['id']; ?>" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_movie.php?id=<?php echo $row['id']; ?>" title="Delete" 
                           onclick="return confirm('Are you sure you want to delete this movie?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo ($page-1); ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" <?php echo ($page == $i) ? 'style="background-color: #0056b3;"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo ($page+1); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
