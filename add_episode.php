<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("location: admin_login.php");
    exit();
}

$series_id = isset($_GET['series_id']) ? $_GET['series_id'] : 0;

// Get series info
$series_sql = "SELECT title, seasons FROM series WHERE id = $series_id";
$series_result = $conn->query($series_sql);
$series = $series_result->fetch_assoc();

if (!$series) {
    header("location: total_series.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $season_number = mysqli_real_escape_string($conn, $_POST['season_number']);
    $episode_number = mysqli_real_escape_string($conn, $_POST['episode_number']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $release_date = mysqli_real_escape_string($conn, $_POST['release_date']);

    // Handle video upload
    $video_url = '';
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $target_dir = "uploads/series/videos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["video"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
            $video_url = $target_file;
        }
    }

    // Handle thumbnail upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_dir = "uploads/series/thumbnails/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["thumbnail"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $thumbnail = $target_file;
        }
    }

    // Insert episode
    $sql = "INSERT INTO series_episodes (series_id, season_number, episode_number, title, description, 
            video_url, thumbnail, duration, release_date) 
            VALUES ($series_id, $season_number, $episode_number, '$title', '$description', 
            '$video_url', '$thumbnail', $duration, '$release_date')";

    if (mysqli_query($conn, $sql)) {
        header("location: series_details.php?id=" . $series_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Episode - <?php echo htmlspecialchars($series['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #111;
            color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #222;
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
            color: #0fcaf0;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            box-sizing: border-box;
        }
        input[type="file"] {
            background-color: #333;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .button {
            background-color: #0fcaf0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0ba8c9;
        }
        .back-button {
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            color: #fff;
            background-color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Episode to <?php echo htmlspecialchars($series['title']); ?></h1>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="season_number">Season Number:</label>
                <input type="number" id="season_number" name="season_number" min="1" max="<?php echo $series['seasons']; ?>" required>
            </div>

            <div class="form-group">
                <label for="episode_number">Episode Number:</label>
                <input type="number" id="episode_number" name="episode_number" min="1" required>
            </div>

            <div class="form-group">
                <label for="title">Episode Title:</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="video">Video File:</label>
                <input type="file" id="video" name="video" accept="video/*" required>
            </div>

            <div class="form-group">
                <label for="thumbnail">Episode Thumbnail:</label>
                <input type="file" id="thumbnail" name="thumbnail" accept="image/*" required>
            </div>

            <div class="form-group">
                <label for="duration">Duration (minutes):</label>
                <input type="number" id="duration" name="duration" min="1" required>
            </div>

            <div class="form-group">
                <label for="release_date">Release Date:</label>
                <input type="date" id="release_date" name="release_date" required>
            </div>

            <div class="form-group">
                <button type="submit" class="button">Add Episode</button>
                <a href="series_details.php?id=<?php echo $series_id; ?>" class="button back-button">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php $conn->close(); ?>
