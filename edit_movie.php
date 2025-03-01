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

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $maincategory = mysqli_real_escape_string($conn, $_POST['maincategory']);
    $subcategory = mysqli_real_escape_string($conn, $_POST['subcategory']);
    $release_date = mysqli_real_escape_string($conn, $_POST['release_date']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $age_rating = mysqli_real_escape_string($conn, $_POST['age_rating']);

    // Handle poster upload if a new one is provided
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $target_dir = "uploads/movies/posters/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["poster"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
            // Update including new poster
            $sql = "UPDATE movies SET 
                    title = '" . mysqli_real_escape_string($conn, $title) . "',
                    description = '" . mysqli_real_escape_string($conn, $description) . "',
                    maincategory = '" . mysqli_real_escape_string($conn, $maincategory) . "',
                    subcategory = '" . mysqli_real_escape_string($conn, $subcategory) . "',
                    release_date = '" . mysqli_real_escape_string($conn, $release_date) . "',
                    duration = '" . mysqli_real_escape_string($conn, $duration) . "',
                    age_rating = '" . mysqli_real_escape_string($conn, $age_rating) . "',
                    poster = '" . mysqli_real_escape_string($conn, $target_file) . "'
                    WHERE id = " . intval($id);
        }
    } else {
        // Update without changing poster
        $sql = "UPDATE movies SET 
                title = '" . mysqli_real_escape_string($conn, $title) . "',
                description = '" . mysqli_real_escape_string($conn, $description) . "',
                maincategory = '" . mysqli_real_escape_string($conn, $maincategory) . "',
                subcategory = '" . mysqli_real_escape_string($conn, $subcategory) . "',
                release_date = '" . mysqli_real_escape_string($conn, $release_date) . "',
                duration = '" . mysqli_real_escape_string($conn, $duration) . "',
                age_rating = '" . mysqli_real_escape_string($conn, $age_rating) . "'
                WHERE id = " . intval($id);
    }

    // Ensure $sql is defined before query
    if (isset($sql)) {
        if (mysqli_query($conn, $sql)) {
            header("location: total_movies.php");
            exit();
        } else {
            // Optional: Add error logging or display
            error_log("Database update failed: " . mysqli_error($conn));
            // You might want to show an error message to the user
        }
    } else {
        error_log("No SQL query generated");
        // Handle the case where no SQL query was created
    }
}

// Get existing movie data
$sql = "SELECT * FROM movies WHERE id = $id";
$result = mysqli_query($conn, $sql);
$movie = mysqli_fetch_assoc($result);

if (!$movie) {
    header("location: total_movies.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        .current-poster {
            max-width: 200px;
            margin: 10px 0;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Movie</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($movie['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="maincategory">Main Category:</label>
                <input type="text" id="maincategory" name="maincategory" value="<?php echo htmlspecialchars($movie['maincategory']); ?>" required>
            </div>

            <div class="form-group">
                <label for="subcategory">Sub Category:</label>
                <input type="text" id="subcategory" name="subcategory" value="<?php echo htmlspecialchars($movie['subcategory']); ?>" required>
            </div>

            <div class="form-group">
                <label for="release_date">Release Date:</label>
                <input type="date" id="release_date" name="release_date" value="<?php echo htmlspecialchars($movie['release_date']); ?>" required>
            </div>

            <div class="form-group">
                <label for="duration">Duration (mins):</label>
                <input type="number" id="duration" name="duration" value="<?php echo htmlspecialchars($movie['duration']); ?>" required>
            </div>

            <div class="form-group">
                <label for="age_rating">Age Rating:</label>
                <input type="text" id="age_rating" name="age_rating" value="<?php echo htmlspecialchars($movie['age_rating']); ?>" required>
            </div>

            <div class="form-group">
                <label for="poster">Current Poster:</label>
                <img src="<?php echo $movie['poster']; ?>" alt="Current Poster" class="current-poster">
                <label for="poster">Upload New Poster (optional):</label>
                <input type="file" id="poster" name="poster" accept="image/*">
            </div>

            <div class="form-group">
                <button type="submit" class="button">Update Movie</button>
                <a href="total_movies.php" class="button" style="text-decoration: none; display: inline-block; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
