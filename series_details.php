<?php
session_start();
include 'config.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'toonhub');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get series ID from the URL
$series_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch series details
$sql = "SELECT * FROM series WHERE id = $series_id";
$result = $conn->query($sql);
$series = $result->fetch_assoc();

if (!$series) {
    echo "Series not found.";
    exit();
}

// Fetch episodes grouped by season
$episodes_sql = "SELECT * FROM series_episodes 
                WHERE series_id = $series_id 
                ORDER BY season_number, episode_number";
$episodes_result = $conn->query($episodes_sql);

$episodes_by_season = array();
if ($episodes_result && $episodes_result->num_rows > 0) {
    while ($episode = $episodes_result->fetch_assoc()) {
        $season = $episode['season_number'];
        if (!isset($episodes_by_season[$season])) {
            $episodes_by_season[$season] = array();
        }
        $episodes_by_season[$season][] = $episode;
    }
}

$hours = floor($series['duration'] / 60);
$minutes = $series['duration'] % 60;
$formatted_duration = "{$hours}hr {$minutes}min";

// Get the episode to play if specified
$play_episode_id = isset($_GET['episode']) ? $_GET['episode'] : null;
if ($play_episode_id) {
    $play_sql = "SELECT * FROM series_episodes WHERE id = " . intval($play_episode_id);
    $play_result = $conn->query($play_sql);
    $playing_episode = $play_result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $series['title']; ?></title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: #222;
            border-radius: 10px;
            padding: 20px;
        }
        .series-info {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .poster-container {
            text-align: center;
            flex: 1 1 300px;
            margin-right: 20px;
        }
        .poster {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 10px;
        }
        .title {
            margin-top: 15px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            text-shadow: 2px 2px 4px #444;
        }
        .details {
            flex: 2 1 500px;
            margin-top: 20px;
        }
        .details p {
            line-height: 1.6;
        }
        .details strong {
            color: #0fcaf0;
        }
        .category {
            margin-top: 10px;
            color: #ffdb58;
        }
        .video-container {
            margin: 30px 0;
            text-align: center;
            width: 100%;
            background-color: #000;
            border-radius: 10px;
            overflow: hidden;
        }
        .video-container video {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        .back-button, .add-episode-button {
            display: inline-block;
            margin: 20px 10px;
            text-decoration: none;
            color: #fff;
            background-color: #0fcaf0;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .back-button:hover, .add-episode-button:hover {
            background-color: #00a4d3;
        }
        h3 {
            margin-top: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #0fcaf0;
        }
        .episodes-container {
            margin-top: 30px;
        }
        .season-container {
            margin-bottom: 30px;
        }
        .season-title {
            font-size: 20px;
            color: #0fcaf0;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #0fcaf0;
        }
        .episode-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .episode-card {
            background-color: #333;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
        }
        .episode-card:hover {
            transform: translateY(-5px);
        }
        .episode-thumbnail {
            width: 100%;
            height: 158px;
            object-fit: cover;
        }
        .episode-info {
            padding: 15px;
        }
        .episode-number {
            color: #0fcaf0;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .episode-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .episode-duration {
            font-size: 14px;
            color: #888;
        }
        .now-playing {
            border: 2px solid #0fcaf0;
        }
        .no-episodes {
            text-align: center;
            padding: 30px;
            background-color: #333;
            border-radius: 8px;
            margin: 20px 0;
        }
        .admin-controls {
            margin: 20px 0;
            padding: 15px;
            background-color: #333;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($playing_episode)): ?>
        <div class="video-container">
            <video controls autoplay>
                <source src="<?php echo htmlspecialchars($playing_episode['video_url']); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <h3>Now Playing: Season <?php echo $playing_episode['season_number']; ?> Episode <?php echo $playing_episode['episode_number']; ?> - <?php echo htmlspecialchars($playing_episode['title']); ?></h3>
        </div>
        <?php endif; ?>

        <div class="series-info">
            <div class="poster-container">
                <img src="<?php echo $series['poster']; ?>" alt="<?php echo $series['title']; ?>" class="poster">
                <div class="title"><?php echo $series['title']; ?></div>
            </div>
            <div class="details">
                <p><strong>Description:</strong> <?php echo $series['description']; ?></p>
                <br>
                <h3>Series Info:</h3>
                <p><strong>Category:</strong> <?php echo $series['maincategory']; ?> - <?php echo $series['subcategory']; ?></p>
                <p><strong>Language:</strong> <?php echo $series['language']; ?></p>
                <p><strong>Release Date:</strong> <?php echo $series['release_date']; ?></p>
                <p><strong>Duration:</strong> <?php echo $formatted_duration; ?></p>
                <p><strong>Rating:</strong> <?php echo $series['rating']; ?>/10</p>
                <p><strong>Age Rating:</strong> <?php echo $series['age_rating']; ?></p>
                <p><strong>Seasons:</strong> <?php echo $series['seasons']; ?></p>
                <p><strong>Episodes:</strong> <?php echo $series['episodes']; ?></p>
                <p><strong>Status:</strong> <?php echo $series['status']; ?></p>
            </div>
        </div>

        <?php if (isset($_SESSION['loggedin']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="admin-controls">
            <h3>Admin Controls</h3>
            <a href="add_episode.php?series_id=<?php echo $series_id; ?>" class="add-episode-button">
                <i class="fas fa-plus"></i> Add New Episode
            </a>
        </div>
        <?php endif; ?>

        <div class="episodes-container">
            <h3>Episodes</h3>
            <?php if (empty($episodes_by_season)): ?>
                <div class="no-episodes">
                    <h3><i class="fas fa-film"></i> No episodes available yet</h3>
                    <p>Episodes will be added soon. Please check back later!</p>
                </div>
            <?php else: ?>
                <?php foreach ($episodes_by_season as $season_number => $episodes): ?>
                <div class="season-container">
                    <div class="season-title">Season <?php echo $season_number; ?></div>
                    <div class="episode-list">
                        <?php foreach ($episodes as $episode): ?>
                        <a href="?id=<?php echo $series_id; ?>&episode=<?php echo $episode['id']; ?>" 
                           class="episode-card <?php echo (isset($playing_episode) && $playing_episode['id'] == $episode['id']) ? 'now-playing' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($episode['thumbnail']); ?>" 
                                 alt="Episode <?php echo $episode['episode_number']; ?>" 
                                 class="episode-thumbnail">
                            <div class="episode-info">
                                <div class="episode-number">Episode <?php echo $episode['episode_number']; ?></div>
                                <div class="episode-title"><?php echo htmlspecialchars($episode['title']); ?></div>
                                <div class="episode-duration">
                                    <?php 
                                    $ep_hours = floor($episode['duration'] / 60);
                                    $ep_minutes = $episode['duration'] % 60;
                                    echo $ep_hours > 0 ? "{$ep_hours}h {$ep_minutes}m" : "{$ep_minutes}m";
                                    ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
  
    <div class="back">
        <a class="back-button" href="home.php">Back to Series</a>
    </div>
</body>
</html>

<?php $conn->close(); ?>