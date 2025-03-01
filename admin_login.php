<?php
session_start();
$errors = [
    'username' => '',
    'password' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['username'])) {
        $errors['username'] = "Username is required.";
    }
    if (empty($_POST['password'])) {
        $errors['password'] = "Password is required.";
    }

    // Proceed with login logic only if there are no errors
    if (empty(array_filter($errors))) {
        include 'config.php';
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Check if the username exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // Username exists, now verify password and role
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['Password'])) {
                if ($user['role'] === 'admin') {
                    // Admin Login Success
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'admin';
                    header("location: admin_dashboard.php");
                    exit();
                } else {
                    $errors['username'] = "Access denied. Admins only.";
                }
            } else {
                $errors['password'] = "Incorrect password.";
            }
        } else {
            $errors['username'] = "Username not found.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            background-image: url(img/background.jpg);
            margin: 0;
            padding: 40px 0;
            position: relative;
            min-height: 100vh;
        }

        .container {
            width: 300px;
            margin: 0 auto;
            padding: 30px;
            background-color: black;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: white;
        }

        .form-group {
            margin-bottom: 25px;
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

        input[type="text"],
        input[type="password"] {
            width: 93%;
            padding: 10px;
            border: 2px solid #140101;
            border-radius: 3px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #8903dc;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: large;
            font-weight: bold;
        }

        button:hover {
            background-color: wheat;
            color: black;
        }

        .forgot-password {
            text-align: right;
        }

        .forgot-password a {
            color: white;
            text-decoration: none;
        }

        p {
            text-align: center;
            color: white;
        }

        .home-option {
            text-align: center;
            margin-top: 20px;
        }

        .home-option a {
            background-color: green;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: small;
        }

        .home-option a:hover {
            background-color: wheat;
            color: black;
        }

        .logoimg {
            margin-left: 30px;
            width: 200px;
            height: 40px;
        }

        .selection-box {
    position: fixed;
    bottom: 40px;
    left: 30px;
    width: 120px;
    background-color: #f0f0f0; /* Light grey background */
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}

/* Style for User and Admin options */
.user-option,
.admin-option {
    display: block;
    padding: 15px 0;
    text-align: center;
    text-decoration: none;
    color: #8903dc; /* Purple text color */
    font-weight: bold;
    font-size: 14px;
    background-color: #f0f0f0; /* Light grey background */
    border: none;
    border-radius: 0;
}

/* Hover effect for options */
.user-option:hover,
.admin-option:hover {
    background-color: gray; /* Purple background on hover */
    color: #8903dc; /* White text on hover */
}

/* Separator line styling */
.separator {
    height: 1px;
    background-color: #8903dc; /* Purple line */
    width: 100%;
}
.active {
    background-color: #8903dc; /* Purple background for the active option */
    color: white; /* White text for the active option */
}
.user-option i,
.admin-option i {
    margin-right: 8px; /* Adjust the value as needed for spacing */
    font-size: 1.2em; /* Adjust icon size if necessary */
}



    </style>
</head>
<body>
    <img src="img/logo.png" class="logoimg" alt="">
    <div class="container">
        <h2>Admin Login</h2>
        <form action="admin_login.php" method="post">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <?php if (!empty($errors['username'])): ?>
                    <div class="error"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <div class="input-group">
                   <input type="password" id="password" name="password" placeholder="Password">
                   <i class="fa-regular fa-eye-slash" id="togglePassword"></i>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <div class="error"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <!-- Home Option -->
    <div class="home-option">
        <a href="index.php">Back to Home</a>
    </div>

    
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
      
        // Toggle Password Visibility
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');
        });

        
    </script>


</body>
</html>