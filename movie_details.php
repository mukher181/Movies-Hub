<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'toonhub');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get movie details based on the ID passed in the URL
$movie_id = isset($_GET['id']) ? $_GET['id'] : '';
$sql = "SELECT * FROM movies WHERE id = '$movie_id'";
$result = $conn->query($sql);
$movie = $result->fetch_assoc();

if (!$movie) {
    echo "Movie not found!";
    exit;
}

// Convert duration in minutes to hours and minutes
$hours = floor($movie['duration'] / 60);
$minutes = $movie['duration'] % 60;
$formatted_duration = "{$hours}hr {$minutes}min";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie['title']; ?></title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            background-color: #111;
            border: 1px solid #444;
            border-radius: 10px;
            overflow: hidden;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
        }
        .header {
            text-align: center;
            flex: 1 1 300px;
            padding: 10px;
        }
        .header img {
            width: 250px;
            border-radius: 10px;
        }
        .header h1 {
            font-size: 24px;
            margin-top: 10px;
            text-shadow: 2px 2px 4px #444;
        }
        .details {
            flex: 2 1 500px;
            margin-top: 20px;
            padding-left: 20px;
        }
        .details p {
            line-height: 1.6;
        }
        .details strong {
            color: #0fcaf0;
        }
        .details .category {
            margin-top: 10px;
            color: #ffdb58;
        }
        .video-container {
            margin-top: 30px;
            text-align: center;
            width: 100%;
        }
        .video-container video {
            width: 800px; /* Set the desired width */
            height: 450px; /* Set the desired height */
            border: 2px solid #555;
            border-radius: 10px;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #fff;
            background-color: #0fcaf0;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .back-button:hover {
            background-color: #00a4d3;
        }
        h3 {
            margin-top: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #0fcaf0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>">
            <h1><?php echo $movie['title']; ?></h1>
        </div>
        <div class="details">
            <p><strong>Description:</strong> <?php echo $movie['description']; ?></p>

            <br>
            <!-- Series Info Heading -->
            <h3>Movies Info:</h3>
            
            <p><strong>Category:</strong> <?php echo $movie['maincategory']; ?> - <?php echo $movie['subcategory']; ?></p>
            <p><strong>Language:</strong> <?php echo $movie['language']; ?></p>
            <p><strong>Release Date:</strong> <?php echo $movie['release_date']; ?></p>
            <p><strong>Duration:</strong> <?php echo $formatted_duration; ?></p>
            <p><strong>Rating:</strong> <?php echo $movie['rating']; ?>/10</p>
            <p><strong>Age Rating:</strong> <?php echo $movie['age_rating']; ?></p>
            
        </div>
    </div>
    <div class="video-container">
        <h3>Watch the Movie:</h3>
        <video controls>
            <source src="<?php echo $movie['video_path']; ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <div class="back">
        <a class="back-button" href="home.php">Back to Movies</a>
    </div>
</body>
</html>

<?php $conn->close(); ?>