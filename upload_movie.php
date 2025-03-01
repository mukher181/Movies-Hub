<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'toonhub');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_messages = []; // Array to hold all error messages
$input_values = []; // Array to retain input values

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize inputs
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $maincategory = htmlspecialchars(trim($_POST['maincategory']));
    $subcategory = htmlspecialchars(trim($_POST['subcategory']));
    $language = htmlspecialchars(trim($_POST['language']));
    $release_date = $_POST['release_date'];
    $hours = (int)$_POST['hours'];
    $minutes = (int)$_POST['minutes'];
    $duration = ($hours * 60) + $minutes;
    
    $age_rating = htmlspecialchars(trim($_POST['age_rating']));

    // Retain input values
    $input_values = compact('title', 'description', 'maincategory', 'subcategory', 'language', 'release_date', 'hours', 'minutes', 'age_rating');

    // Validation checks
    if (empty($title)) $error_messages['title'] = "Title is required.";
    if (empty($description)) $error_messages['description'] = "Description is required.";
    if (empty($maincategory)) $error_messages['maincategory'] = "Main category is required.";
    if (empty($subcategory)) $error_messages['subcategory'] = "Sub category is required.";
    if (empty($language)) $error_messages['language'] = "Language is required.";
    if (empty($release_date)) $error_messages['release_date'] = "Release date is required.";
    if ($duration <= 0) $error_messages['duration'] = "Duration must be greater than 0.";

    if (empty($age_rating)) $error_messages['age_rating'] = "Age Limit is required.";

    // Check if the movie title already exists
    if (empty($error_messages['title'])) {
        $stmt = $conn->prepare("SELECT * FROM movies WHERE title = ?");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error_messages['title'] = "Movie with this title already exists.";
        }
    }

    // File upload security
    $poster_target_dir = "uploads/posters/";
    $video_target_dir = "uploads/videos/";
    if (!file_exists($poster_target_dir)) mkdir($poster_target_dir, 0777, true);
    if (!file_exists($video_target_dir)) mkdir($video_target_dir, 0777, true);

    // Validate poster file
    if (empty($_FILES['poster']['name'])) {
        $error_messages['poster'] = "Poster is required.";
    } else {
        $poster_file_type = mime_content_type($_FILES['poster']['tmp_name']);
        $allowed_image_types = ['image/jpeg', 'image/png'];
        if (!in_array($poster_file_type, $allowed_image_types)) {
            $error_messages['poster'] = "Only JPG and PNG formats are allowed for posters.";
        } else {
            $poster = $poster_target_dir . uniqid('poster_', true) . "." . pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
        }
    }

    // Validate video file
    if (empty($_FILES['video']['name'])) {
        $error_messages['video'] = "Video is required.";
    } else {
        $video_file_type = mime_content_type($_FILES['video']['tmp_name']);
        $allowed_video_types = ['video/mp4', 'video/mkv'];
        if (!in_array($video_file_type, $allowed_video_types)) {
            $error_messages['video'] = "Only MP4 and MKV formats are allowed for videos.";
        } else {
            $video = $video_target_dir . uniqid('video_', true) . "." . pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        }
    }

    // Proceed if no errors
    if (empty($error_messages)) {
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $poster) && move_uploaded_file($_FILES['video']['tmp_name'], $video)) {
            $stmt = $conn->prepare("INSERT INTO movies (title, description, maincategory, subcategory, language, release_date, duration, rating, age_rating, poster, video_path) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdssss", $title, $description, $maincategory, $subcategory, $language, $release_date, $duration, $rating, $age_rating, $poster, $video);
            if ($stmt->execute()) {
                echo "Movie uploaded successfully!";
                sleep(2);
                header("Location: total_movies.php");
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            $error_messages['upload'] = "Failed to upload poster or video.";
        }
    }
}

// SQL query to add language column if it doesn't exist
$alterQuery = "ALTER TABLE movies ADD COLUMN IF NOT EXISTS language VARCHAR(50);";
$conn->query($alterQuery);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard/Total no. of Upload Movies</title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --background-color: #f9f9f9;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        form {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 600;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        textarea,
        select,
        input[list] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="file"] {
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: 4px;
            width: 100%;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .duration-inputs {
            display: flex;
            gap: 10px;
        }

        .duration-inputs input {
            width: calc(50% - 5px);
        }

        button[type="submit"] {
            background-color: var(--secondary-color);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        button[type="submit"]:hover {
            background-color: #2980b9;
        }

        .error-message {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            form {
                padding: 20px;
            }
            
            .duration-inputs {
                flex-direction: column;
            }
            
            .duration-inputs input {
                width: 100%;
            }
        }
        .upload-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            justify-content: center;
            align-items: center;
        }

        .upload-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<a href="admin_dashboard.php" class="upload-button">Home</a>
    <form action="upload_movie.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Movie Title</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($input_values['title'] ?? '') ?>">
            <span class="error-message"><?= $error_messages['title'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description"><?= htmlspecialchars($input_values['description'] ?? '') ?></textarea>
            <span class="error-message"><?= $error_messages['description'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="maincategory">Main Category</label>
            <input list="maincategories" name="maincategory" id="maincategory" placeholder="Select or type a main category" value="<?= htmlspecialchars($input_values['maincategory'] ?? '') ?>">
            <datalist id="maincategories">
                <option value="Hollywood">
                <option value="Bollywood">
                <option value="South Indian">
                <option value="Pakistani">
                <option value="Korean">
            </datalist>
            <span class="error-message"><?= $error_messages['maincategory'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="subcategory">Subcategory</label>
            <select name="subcategory" id="subcategory">
                <option value="">Select</option>
                <option value="Action" <?= ($input_values['subcategory'] ?? '') == 'Action' ? 'selected' : '' ?>>Action</option>
                <option value="Comedy" <?= ($input_values['subcategory'] ?? '') == 'Comedy' ? 'selected' : '' ?>>Comedy</option>
                <option value="Adventure" <?= ($input_values['subcategory'] ?? '') == 'Adventure' ? 'selected' : '' ?>>Adventure</option>
                <option value="Drama" <?= ($input_values['subcategory'] ?? '') == 'Drama' ? 'selected' : '' ?>>Drama</option>
                <option value="Horror" <?= ($input_values['subcategory'] ?? '') == 'Horror' ? 'selected' : '' ?>>Horror</option>
                <option value="Romance" <?= ($input_values['subcategory'] ?? '') == 'Romance' ? 'selected' : '' ?>>Romance</option>
            </select>
            <span class="error-message"><?= $error_messages['subcategory'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="language">Language</label>
            <input list="languages" name="language" id="language" placeholder="Select or type a language" value="<?= htmlspecialchars($input_values['language'] ?? '') ?>">
            <datalist id="languages">
                <option value="English">
                <option value="Hindi">
                <option value="Urdu">
                <option value="Korean">
            </datalist>
            <span class="error-message"><?= $error_messages['language'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="release_date">Release Date</label>
            <input type="date" name="release_date" id="release_date" value="<?= htmlspecialchars($input_values['release_date'] ?? '') ?>">
            <span class="error-message"><?= $error_messages['release_date'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="duration">Duration</label>
            <div class="duration-inputs">
                <input type="number" name="hours" id="hours" placeholder="Hours" min="0" value="<?= htmlspecialchars($input_values['hours'] ?? '') ?>">
                <input type="number" name="minutes" id="minutes" placeholder="Minutes" min="0" max="59" value="<?= htmlspecialchars($input_values['minutes'] ?? '') ?>">
            </div>
            <span class="error-message"><?= $error_messages['duration'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="age_rating">Age Limit</label>
            <input type="text" name="age_rating" id="age_rating" value="<?= htmlspecialchars($input_values['age_rating'] ?? '') ?>">
            <span class="error-message"><?= $error_messages['age_rating'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="poster">Poster</label>
            <input type="file" name="poster" id="poster">
            <span class="error-message"><?= $error_messages['poster'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="video">Video</label>
            <input type="file" name="video" id="video">
            <span class="error-message"><?= $error_messages['video'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <button type="submit">Upload Movie</button>
            <span class="error-message"><?= $error_messages['upload'] ?? '' ?></span>
        </div>
    </form>
</body>
</html>