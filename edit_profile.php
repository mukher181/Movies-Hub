<?php
session_start();
include 'config.php';

$showAlert = false;
$errors = [
    'name' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
    'image' => ''
];
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();}

// Fetch current user details from the database
$user_id = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = '$user_id'";
$result = mysqli_query($conn, $query);
if ($result) {
    $user = mysqli_fetch_assoc($result);
    if ($user) {
        $name = $user['Name'];
        $username = $user['Username'];
        $email = $user['Email'];
        $currentImage = $user['Image'];
    } else {
        // Redirect or show an error if no user is found
        echo "User not found.";
        exit();
    }
} else {
    echo "Database query failed: " . mysqli_error($conn);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate fields
    if (empty($name)) {
        $errors['name'] = "Name is required.";
    } elseif (strlen($name) < 3 || strlen($name) > 50) {
        $errors['name'] = "Name must be between 3 and 50 characters.";
    } elseif (!preg_match("/^[a-zA-Z .]*$/", $name)) {
        $errors['name'] = "Only letters and white space allowed in Name.";
    }

    if (empty($username)) {
        $errors['username'] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors['username'] = "Username must be between 3 and 20 characters.";
    }elseif (!preg_match("/^[a-zA-Z0-9_]*$/", $username)) {
        $errors['username'] = "Only letters, digits, and underscores allowed in Username.";
    }elseif (!preg_match("/[a-zA-Z]/", $username) || !preg_match("/[0-9]/", $username)) {
    $errors['username'] = "Username must contain at least one letter and one digit.";
    }else {
        // Check if the username already exists (excluding the current user)
        $usernameQuery = "SELECT * FROM users WHERE username = '$username' AND username != '$user_id'";
        $usernameResult = mysqli_query($conn, $usernameQuery);
        if (mysqli_num_rows($usernameResult) > 0) {
            $errors['username'] = "Username is already taken.";
        }
    }


    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        // Check if the email already exists (excluding the current user)
        $emailQuery = "SELECT * FROM users WHERE email = '$email' AND username != '$user_id'";
        $emailResult = mysqli_query($conn, $emailQuery);
        if (mysqli_num_rows($emailResult) > 0) {
            $errors['email'] = "Email is already taken.";
        }
    }
    

    // Validate password
    if (!empty($new_password)) {
        if (strlen($new_password) < 8 || strlen($new_password) > 15) {
            $errors['password'] = "Password must be between 8 and 15 characters.";
        }elseif (!preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
            $errors['password'] = "Password must include uppercase, lowercase, a digit, and a special character.";
        }elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "Passwords do not match.";
        }
    }

    // Image upload validation
    $imagePath = $currentImage; // Default to current image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $imageName = $_FILES['profile_image']['name'];
        $imageTmpName = $_FILES['profile_image']['tmp_name'];
        $uploadDir = 'uploads/';
        
        $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $uniqueImageName = uniqid("user_", true) . '.' . $fileExtension;
        $imagePath = $uploadDir . $uniqueImageName;

        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($imageTmpName);

        if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
            $errors['image'] = "Only JPG and PNG images are allowed.";
        } elseif ($_FILES['profile_image']['size'] > 500 * 1024) {
            $errors['image'] = "Image size should not exceed 500kb.";
        } elseif (!move_uploaded_file($imageTmpName, $imagePath)) {
            $errors['image'] = "Failed to upload the image.";
        }
    }

    // Update database if no errors
    if (array_filter($errors) === []) {
        $updateQuery = "UPDATE users SET name='$name', username='$username', email='$email', image='$imagePath'";

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery .= ", password='$hashed_password'";
        }
        
        $updateQuery .= " WHERE username='$user_id'";
        
        if (mysqli_query($conn, $updateQuery)) {
            // Update session variables to reflect the changes
            $_SESSION['username'] = $username; // If the username is updated
            $_SESSION['profile_image'] = $imagePath; // Update profile image in session
    
            $showAlert = true;
        } else {
            $errors['general'] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            
            height: 100vh;
            
            background-color: #111;
        }

        /* Main container styling */
        .main-content {
            flex: 1;
            background-color: #111;
            padding: 20px;
            overflow-y: auto;
        }

       

        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: black;
        }

        .container h2 {
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }

        .profile-pic {
            text-align: center;
            margin-bottom: 15px;
        }

        .profile-pic img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .input-group {
        position: relative;
    }

    .input-group input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding-right: 35px; /* Leave space for the icon */
        box-sizing: border-box; /* Ensure padding doesn't affect field size */
    }

    .input-group i {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #111;
    }


        .form-group label {
            display: block;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }

        .input-container {
            position: relative;
        }

        .edit-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 14px;
            color: #999;
            transition: color 0.2s ease;
        }

        .edit-icon:hover {
            color: #666;
        }

        input {
            width: 100%;
            padding: 8px 30px 8px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .buttons button {
            width: 48%;
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .buttons .save-btn {
            background-color: #8903dc;
            color: white;
        }

        .buttons .cancel-btn {
            background-color: #ffffff;
            color: #8903dc;
            border: 1px solid #8903dc;
        }


        .menu-icon-right {
            right: 30px;
        font-size: 24px;
        cursor: pointer;
        position: absolute;
        right: 30px;
        display: flex;
        align-items: center; /* Aligns image and username in the center */
        gap: 10px; /* Adds space between the image and username */
    }

        

       
        .sidebar-right {
            position: fixed;
            top: 80px;
            width: 250px;
            height: 100%;
            background-color: #000;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            right:0;
        }


      

       

       

        .sidebar-logo {
            font-size: 20px;
            color: #fff;
            font-weight: bold;
        }

        .menu-options{
            display: flex;
            flex-direction: column;
            padding: 0 20px;
        }

         

        .menu-options a:hover {
            color: #8903dc;
        }

        .profile {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .profile h3 {
            color:white;
            font-size: 18px;
            font-weight: bold;
        }

        .edit-profile, .sign-out {
            width: 80%;
            padding: 15px 10px;
            margin: 5px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .edit-profile {
            background-color: #8903dc;
            color: #fff;
        }
        .edit-profile i{
            margin-right:10px;
        }

        .sign-out {
            background-color: transparent;
            color: #8903dc;
            border: 1px solid #8903dc;
        }
        .menu-icon-right img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: 0; /* Remove top margin to align it with the username */
    }

    
    .menu-options a {
    color: #fff;
    text-decoration: none;
    font-size: 16px;
    padding: 15px 0;
    border-bottom: 1px solid #333;
    display: flex;
    
}

.menu-options a i {
    margin-right:10px;
    margin-top:0;
    color: #8903dc;
}

.option-arrow {
    color: #8903dc;
    font-weight: bold;
}

a.active {
    color: #8903dc; /* Highlight color */
    font-weight: bold;
   
}

.menu-options a.active,
.category-options a.active {
    color:#8903dc;/* for active link */
   
}



    </style>
</head>
<body>

<aside class="sidebar-right" id="sidebarRight">
        
        <div class="profile">
            
            <img src="<?php echo $_SESSION['profile_image']; ?>" alt="Profile Image" onclick="openModal();">
        
            <h3 id="username"><?php echo $_SESSION['username']; ?></h3>
            <a href="edit_profile.php" >
                <button class="edit-profile" ><i class="fa-solid fa-user-pen"></i>Edit Profile</button>
            </a>
            <a href="logout.php"><button class="sign-out">Logout</button></a>
        </div>
        <nav class="menu-options">
            <a href="home.php" class="<?= ($current_page == 'home.php') ? 'active' : '' ?>"><i class="fa-solid fa-house-user"></i> Home </a>
            <a href="#" class="<?= ($current_page == 'rate_us.php') ? 'active' : '' ?>"><i class="fas fa-star"></i> Rate Us </a>        
            <a href="#" class="<?= ($current_page == 'contact_us.php') ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Contact Us </a>
            <a href="#" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings </a>
            <a href="#" class="<?= ($current_page == 'about.php') ? 'active' : '' ?>"><i class="fas fa-info-circle"></i> About </a>
        </nav>
    </aside>

<!-- Main Content -->
<div class="main-content">

<div class="container">
    <h2>Edit Profile</h2>
    
    <?php if ($showAlert): ?>
        <p style="color: green; text-align: center;">Profile updated successfully!</p>
    <?php endif; ?>
    
    <form action="" method="POST" enctype="multipart/form-data">
        <!-- Profile Image -->
        <div class="profile-pic">
            <img src="<?= htmlspecialchars($currentImage) ?>" alt="Profile Image">
            <input type="file" name="profile_image">
            <p style="color: red;"><?= $errors['image'] ?></p>
        </div>

        <!-- Name -->
        <div class="form-group">
    <label for="name">Name</label>
    <div class="input-container">
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" required>
        
    </div>
    <p style="color: red;"><?= $errors['name'] ?></p>
</div>

<!-- Username -->
<div class="form-group">
    <label for="username">Username</label>
    <div class="input-container">
        <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required>
        
    </div>
    <p style="color: red;"><?= $errors['username'] ?></p>
</div>

<!-- Email -->
<div class="form-group">
    <label for="email">Email</label>
    <div class="input-container">
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>
        
    </div>
    <p style="color: red;"><?= $errors['email'] ?></p>
</div>


        <!-- New Password (optional) -->
        <div class="form-group">
            <label for="new_password">New Password (optional)</label>
            <div class="input-group">
                   <input type="password" id="new_password" name="new_password" placeholder="New Password">
                   <i class="fa-regular fa-eye-slash" id="togglePassword"></i>
                </div>
            <p style="color: red;"><?= $errors['password'] ?></p>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-group">
                   <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password">
                   <i class="fa-regular fa-eye-slash" id="toggleConfirmPassword"></i>
                </div>
            <p style="color: red;"><?= $errors['confirm_password'] ?></p>
        </div>

        <!-- Buttons -->
        <div class="buttons">
            <button type="button" class="cancel-btn" onclick="window.location.href='home.php'">Go back</button>
            <button type="submit" class="save-btn">Update</button>
        </div>
    </form>
</div>

    </div>

   

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('new_password');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');

        // Toggle Password Visibility
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');
        });

        // Toggle Confirm Password Visibility
        toggleConfirmPassword.addEventListener('click', () => {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            toggleConfirmPassword.classList.toggle('fa-eye');
            toggleConfirmPassword.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>
