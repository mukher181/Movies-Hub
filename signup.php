<?php
$showAlert = false; 
$errors = [
    'name' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirmpassword' => '',
    'image' => ''
];

$name = $username = $email = ''; // Initialize these variables to empty strings

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'config.php';
    
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];

    // Validate each field and populate specific error messages
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
        $usernameQuery = "SELECT * FROM users WHERE Username = '$username'";
        $usernameResult = mysqli_query($conn, $usernameQuery);
        if (mysqli_num_rows($usernameResult) > 0) {
            $errors['username'] = "Username already exists.";
        }
    }

    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        $emailQuery = "SELECT * FROM users WHERE Email = '$email'";
        $emailResult = mysqli_query($conn, $emailQuery);
        if (mysqli_num_rows($emailResult) > 0) {
            $errors['email'] = "Email already exists.";
        }
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 8 || strlen($password) > 15) {
        $errors['password'] = "Password must be between 8 and 15 characters.";
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $errors['password'] = "Password must include uppercase, lowercase, a digit, and a special character.";
    } elseif ($password !== $confirmpassword) {
        $errors['confirmpassword'] = "Passwords do not match.";
    }

   // Image upload validation
$imagePath = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $imageName = $_FILES['image']['name'];
    $imageTmpName = $_FILES['image']['tmp_name'];
    $uploadDir = 'uploads/';
    $imagePath = $uploadDir . basename($imageName);

    $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

    // Generate a unique name using current timestamp and original extension
    $uniqueImageName = uniqid("user_", true) . '.' . $fileExtension; // e.g., user_607e9ac4c4d26.png
    $imagePath = $uploadDir . $uniqueImageName;


    // Allowed file types
    $allowedTypes = ['image/jpeg', 'image/png'];
    $fileType = mime_content_type($imageTmpName);

    // Check file type and extension
    $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
        $errors['image'] = "Only JPG and PNG images are allowed.";
    } elseif ($_FILES['image']['size'] > 500 * 1024) {
        $errors['image'] = "Image size should not exceed 500kb.";
    } elseif (!move_uploaded_file($imageTmpName, $imagePath)) {
        $errors['image'] = "Failed to upload the image.";
    }
} else {
    $errors['image'] = "Image is required.";
}

    if (array_filter($errors) === []) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (Name, Username, Email, Password, Image) VALUES ('$name', '$username', '$email', '$hashed_password', '$imagePath');";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $showAlert = true;
            // Reset input fields
            $name = $username = $email = '';
            $password = $confirmpassword = '';
            $imagePath = '';
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Registration</title>
    <style>
        body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        background-image:url(img/background.jpg);
        margin: 0;
        padding: 40px 0px;
    } 

    .container {
        width: 300px;
        margin: 0 auto;
        padding: 20px 40px;
        background-color: black;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1, .success { text-align: center; color: white; }
    .error { text-align: center; color: red; } /* Updated error color */
    .form-group { margin-bottom: 20px; color: white; position: relative; }
    label { display: block; font-weight: bold; }
    input[type="text"], input[type="password"], input[type="email"], input[type="file"] {
        width: 93%; padding: 10px; border: 1px solid #ccc; border-radius: 3px;
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



    button {
        width: 100%; padding: 10px; background-color: #8903dc; color: white;
        border: none; border-radius: 3px; cursor: pointer; font-weight: bold; font-size: large;
    }
    button:hover { background-color: wheat; }
    .home-option { text-align: center; }
    .k { color: white; }
    .k a { color: #8903dc; }
    .home-option a { color: white; }
    .success {
    background-color: #00a86b; /* Background color */
    color: black; /* Text color */
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
}
.logoimg{

    margin-left:30px;
    width:200px;
    height:40px;
}
            .error-message { color: red; font-size: 0.9em; }
    </style>
</head>

<body>
    <?php if ($showAlert): ?>
        <div class="success">Registration successful! You can now <a href="login.php">login</a>.</div>
    <?php endif; ?>

    <img src="img/logo.png" class="logoimg" alt="">
    <div class="container">
        <h1 class="k">Register</h1>
        <form action="signup.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" id="name" name="name" placeholder="Name" value="<?= htmlspecialchars($name) ?>">
                <?php if (!empty($errors['name'])): ?><div class="error-message"><?= $errors['name'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder="Username" value="<?= htmlspecialchars($username) ?>">
                <?php if (!empty($errors['username'])): ?><div class="error-message"><?= $errors['username'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>">
                <?php if (!empty($errors['email'])): ?><div class="error-message"><?= $errors['email'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <div class="input-group">
                   <input type="password" id="password" name="password" placeholder="Password">
                   <i class="fa-regular fa-eye-slash" id="togglePassword"></i>
                </div>
                <?php if (!empty($errors['password'])): ?> <div class="error-message"><?= $errors['password'] ?></div> <?php endif; ?>
            </div>

            <div class="form-group">
                <div class="input-group">
                   <input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm Password">
                   <i class="fa-regular fa-eye-slash" id="toggleConfirmPassword"></i>
                </div>
            <?php if (!empty($errors['confirmpassword'])): ?> <div class="error-message"><?= $errors['confirmpassword'] ?></div> <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image">Photo</label>
                <input type="file" id="image" name="image">
                <?php if (!empty($errors['image'])): ?><div class="error-message"><?= $errors['image'] ?></div><?php endif; ?>
            </div>
            <button type="submit">Register</button>
        </form>
        <p class="k">Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
    <div class="home-option">
        <a href="index.php" class="button">Back</a>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirmpassword');

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


