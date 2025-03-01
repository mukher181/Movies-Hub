<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'toonhub');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_messages = []; // Array to store error messages
$input_values = []; // Array to store input values

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input fields
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $maincategory = trim($_POST['maincategory']);
    $subcategory = $_POST['subcategory'];
    $language = $_POST['language'];
    $release_date = $_POST['release_date'];
    $hours = (int)$_POST['hours'];
    $minutes = (int)$_POST['minutes'];
    $duration = ($hours * 60) + $minutes;
    
    $age_rating = trim($_POST['age_rating']);
    $seasons = (int)($_POST['seasons'] ?? 0);
    $episodes = (int)($_POST['episodes'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    // Retain input values
    $input_values = compact('title', 'description', 'maincategory', 'subcategory', 'language', 'release_date', 'hours', 'minutes', 'age_rating', 'seasons', 'episodes', 'status');

    // Validate inputs
    if (empty($title)) $error_messages['title'] = "Title is required.";
    if (empty($description)) $error_messages['description'] = "Description is required.";
    if (empty($maincategory)) $error_messages['maincategory'] = "Main category is required.";
    if (empty($subcategory)) $error_messages['subcategory'] = "Sub category is required.";
    if (empty($language)) $error_messages['language'] = "Language is required.";
    if (empty($release_date)) $error_messages['release_date'] = "Release date is required.";
    if ($duration <= 0) $error_messages['duration'] = "Duration must be greater than 0.";
    
    if (empty($age_rating)) $error_messages['age_rating'] = "Age Limit is required.";
    if ($seasons < 1) $error_messages['seasons'] = "Number of seasons must be at least 1.";
    if ($episodes < 1) $error_messages['episodes'] = "Episodes per season must be at least 1.";
    if (empty($status)) {
        $error_messages['status'] = "Status is required.";
    } elseif (!in_array($status, ['Ongoing', 'Completed'])) {
        $error_messages['status'] = "Invalid status value.";
    }

    // Check if the series title already exists
    $sql_check_title = $conn->prepare("SELECT * FROM series WHERE title = ?");
    $sql_check_title->bind_param("s", $title);
    $sql_check_title->execute();
    $result = $sql_check_title->get_result();
    if ($result->num_rows > 0) {
        $error_messages['title'] = "Series with this title already exists!";
    }

    // File upload validations
    $allowed_poster_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $allowed_video_types = ['video/mp4', 'video/mkv', 'video/webm'];

    // Poster upload
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $poster_info = $_FILES['poster'];
        $poster_mime = mime_content_type($poster_info['tmp_name']);
        if (!in_array($poster_mime, $allowed_poster_types)) {
            $error_messages['poster'] = "Invalid poster format. Only JPG, PNG files are allowed.";
        } else {
            $poster_target_dir = "uploads/posters/";
            if (!file_exists($poster_target_dir)) mkdir($poster_target_dir, 0777, true);
            $poster = $poster_target_dir . uniqid() . "_" . basename($poster_info['name']);
            move_uploaded_file($poster_info['tmp_name'], $poster);
        }
    } else {
        $error_messages['poster'] = "Poster is required.";
    }

    // Video upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $video_info = $_FILES['video'];
        $video_mime = mime_content_type($video_info['tmp_name']);
        if (!in_array($video_mime, $allowed_video_types)) {
            $error_messages['video'] = "Invalid video format. Only MP4, MKV, WebM files are allowed.";
        } else {
            $video_target_dir = "uploads/videos/";
            if (!file_exists($video_target_dir)) mkdir($video_target_dir, 0777, true);
            $video = $video_target_dir . uniqid() . "_" . basename($video_info['name']);
            move_uploaded_file($video_info['tmp_name'], $video);
        }
    } else {
        $error_messages['video'] = "Video is required.";
    }

    // If no errors, insert data into the database
    if (empty($error_messages)) {
        $sql = $conn->prepare("INSERT INTO series (title, description, maincategory, subcategory, language, release_date, duration, age_rating, poster, video_path, seasons, episodes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $sql->bind_param("ssssssdssiiis", $title, $description, $maincategory, $subcategory, $language, $release_date, $duration, $age_rating, $poster, $video, $seasons, $episodes, $status);
    
        if ($sql->execute()) {
            echo "Series uploaded successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Series - ToonHub Admin</title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --border-color: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
        }

        .back-button {
            text-decoration: none;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea,
        select,
        input[list] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        textarea:focus,
        select:focus,
        input[list]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .duration-inputs {
            display: flex;
            gap: 10px;
        }

        .duration-inputs input {
            width: calc(50% - 5px);
        }

        .file-input-group {
            margin-bottom: 20px;
        }

        .file-input-group label {
            display: block;
            margin-bottom: 8px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type="file"] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .file-input-button:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 14px;
            margin-top: 5px;
        }

        .success-message {
            background-color: var(--success-color);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .submit-button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-button:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .container {
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
    <div class="container">
        <div class="header">
            <h1>Upload New Series</h1>
            <a href="admin_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($error_messages)): ?>
            <div class="error-message">
                Please correct the errors below.
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Series Title</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($input_values['title'] ?? '') ?>" placeholder="Enter series title">
                <?php if (isset($error_messages['title'])): ?>
                    <div class="error-message"><?= $error_messages['title'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Enter series description"><?= htmlspecialchars($input_values['description'] ?? '') ?></textarea>
                <?php if (isset($error_messages['description'])): ?>
                    <div class="error-message"><?= $error_messages['description'] ?></div>
                <?php endif; ?>
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
                <?php if (isset($error_messages['maincategory'])): ?>
                    <div class="error-message"><?= $error_messages['maincategory'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="subcategory">Subcategory</label>
                <select name="subcategory" id="subcategory">
                    <option value="">Select subcategory</option>
                    <option value="Action" <?= ($input_values['subcategory'] ?? '') == 'Action' ? 'selected' : '' ?>>Action</option>
                    <option value="Comedy" <?= ($input_values['subcategory'] ?? '') == 'Comedy' ? 'selected' : '' ?>>Comedy</option>
                    <option value="Adventure" <?= ($input_values['subcategory'] ?? '') == 'Adventure' ? 'selected' : '' ?>>Adventure</option>
                    <option value="Drama" <?= ($input_values['subcategory'] ?? '') == 'Drama' ? 'selected' : '' ?>>Drama</option>
                    <option value="Horror" <?= ($input_values['subcategory'] ?? '') == 'Horror' ? 'selected' : '' ?>>Horror</option>
                    <option value="Romance" <?= ($input_values['subcategory'] ?? '') == 'Romance' ? 'selected' : '' ?>>Romance</option>
                </select>
                <?php if (isset($error_messages['subcategory'])): ?>
                    <div class="error-message"><?= $error_messages['subcategory'] ?></div>
                <?php endif; ?>
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
                <?php if (isset($error_messages['language'])): ?>
                    <div class="error-message"><?= $error_messages['language'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="release_date">Release Date</label>
                <input type="date" name="release_date" id="release_date" value="<?= htmlspecialchars($input_values['release_date'] ?? '') ?>">
                <?php if (isset($error_messages['release_date'])): ?>
                    <div class="error-message"><?= $error_messages['release_date'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Duration</label>
                <div class="duration-inputs">
                    <input type="number" name="hours" id="hours" placeholder="Hours" value="<?= htmlspecialchars($input_values['hours'] ?? '') ?>" min="0">
                    <input type="number" name="minutes" id="minutes" placeholder="Minutes" value="<?= htmlspecialchars($input_values['minutes'] ?? '') ?>" min="0" max="59">
                </div>
                <?php if (isset($error_messages['duration'])): ?>
                    <div class="error-message"><?= $error_messages['duration'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="age_rating">Age Rating</label>
                <input type="text" name="age_rating" id="age_rating" placeholder="Enter age rating" value="<?= htmlspecialchars($input_values['age_rating'] ?? '') ?>">
                <?php if (isset($error_messages['age_rating'])): ?>
                    <div class="error-message"><?= $error_messages['age_rating'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="seasons">Number of Seasons</label>
                <input type="number" name="seasons" id="seasons" placeholder="Enter number of seasons" value="<?= htmlspecialchars($input_values['seasons'] ?? '') ?>" min="1">
                <?php if (isset($error_messages['seasons'])): ?>
                    <div class="error-message"><?= $error_messages['seasons'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="episodes">Episodes per Season</label>
                <input type="number" name="episodes" id="episodes" placeholder="Enter episodes per season" value="<?= htmlspecialchars($input_values['episodes'] ?? '') ?>" min="1">
                <?php if (isset($error_messages['episodes'])): ?>
                    <div class="error-message"><?= $error_messages['episodes'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">Select status</option>
                    <option value="Ongoing" <?= ($input_values['status'] ?? '') == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="Completed" <?= ($input_values['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
                <?php if (isset($error_messages['status'])): ?>
                    <div class="error-message"><?= $error_messages['status'] ?></div>
                <?php endif; ?>
            </div>

            <div class="file-input-group">
                <label for="poster">Series Poster</label>
                <div class="file-input-wrapper">
                    <div class="file-input-button">
                        <i class="fas fa-upload"></i> Choose Poster
                    </div>
                    <input type="file" name="poster" id="poster" accept="image/*">
                </div>
                <?php if (isset($error_messages['poster'])): ?>
                    <div class="error-message"><?= $error_messages['poster'] ?></div>
                <?php endif; ?>
            </div>

            <div class="file-input-group">
                <label for="video">Video (Trailer or First Episode)</label>
                <div class="file-input-wrapper">
                    <div class="file-input-button">
                        <i class="fas fa-upload"></i> Choose Video
                    </div>
                    <input type="file" name="video" id="video" accept="video/*">
                </div>
                <?php if (isset($error_messages['video'])): ?>
                    <div class="error-message"><?= $error_messages['video'] ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-button">
                <i class="fas fa-cloud-upload-alt"></i> Upload Series
            </button>
        </form>
    </div>

    <script>
        // Update file input labels when files are selected
        document.getElementById('poster').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file chosen';
            e.target.parentElement.querySelector('.file-input-button').textContent = fileName;
        });

        document.getElementById('video').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file chosen';
            e.target.parentElement.querySelector('.file-input-button').textContent = fileName;
        });
    </script>
</body>
</html>