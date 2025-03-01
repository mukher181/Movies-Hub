<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("location: admin_login.php");
    exit();
}

$showAlert = false;
$showModal = false; // Control whether the modal is shown
$errors = [
    'name' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirmpassword' => '',
    'image' => ''
];

$name = $username = $email = ''; // Initialize fields as empty

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $showModal = true; // Show the modal again if there are errors
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);  
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];

    // Validation for each field
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
    } elseif (!preg_match("/^[a-zA-Z0-9_]*$/", $username)) {
        $errors['username'] = "Only letters, digits, and underscores allowed in Username.";
    } elseif (!preg_match("/[a-zA-Z]/", $username) || !preg_match("/[0-9]/", $username)) {
        $errors['username'] = "Username must contain at least one letter and one digit.";
    } else {
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

    // Image validation
    $imagePath = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $uploadDir = 'uploads/';
        $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $uniqueImageName = uniqid("user_", true) . '.' . $fileExtension;
        $imagePath = $uploadDir . $uniqueImageName;

        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($imageTmpName);

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

    // Insert into database if no errors
    if (array_filter($errors) === []) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (Name, Username, Email, Password, Image) VALUES ('$name', '$username', '$email', '$hashed_password', '$imagePath');";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $showAlert = true;
            // Clear inputs and errors after success
            $name = $username = $email = '';
            $password = $confirmpassword = '';
            $imagePath = '';
            $errors = []; // Clear errors
            header("Location: total_users.php");
            exit(); 
        } else {
            $errors['general'] = "Database error: " . mysqli_error($conn);
        }
    }
}
else{
    $showModal = false; 
}

// Fetch Total Records
$recordsPerPage = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $recordsPerPage;

// Count total records
$totalRecordsQuery = "SELECT COUNT(*) AS total FROM users WHERE role != 'admin'";
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Calculate total pages
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch records for the current page
$sql = "SELECT id, name, username, email, image , is_active FROM users WHERE role != 'admin' LIMIT $recordsPerPage OFFSET $offset";
$result = $conn->query($sql);
$admin_name = $_SESSION['username'];

?>

<?php
if (isset($_GET['fetch_all'])) {
    $query = "SELECT id, name, username, email, image , is_active FROM users WHERE role != 'admin'";
    $result = $conn->query($query);
    $users = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($users);
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard/Total no. of users</title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }

        .sidebar {
    width: 200px;
    height: 100vh;
    background-color: #2f2f2f;
    position: fixed;
    padding-top: 40px;
    color: white;
    text-align: center;
    font-size: 20px;
}

.sidebar a {
    display: flex;
    align-items: center;
    color: white;
    padding: 12px 10px;
    text-decoration: none;
    font-size: 18px;
    margin-left: 10px;
}

.sidebar a i {
    margin-right: 12px;
}

.sidebar a:hover {
    background-color:  #001F3F;
}
.sidebar a.active{
    background-color: #2575fc;
}
.logout-btn {
    background-color: #e74c3c;
    color: white;
    padding: 15px;
    font-size: 16px;
    border: none;
    cursor: pointer;
    width: 90%;
    margin: auto;
    text-align: center;
    display: block;
    margin-top: 250px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
}

.logout-btn:hover {
    background-color: #c0392b;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}
        .main-content {
            margin-left: 220px;
            padding: 20px;
        }

        .add-user-btn {
            margin-bottom: 15px;
            padding: 10px 20px;
            background-color: #8903dc;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        .add-user-btn:hover {
            background-color: gray;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #4caf50;
            color: white;
        }

        .actions {
    display: flex;
    gap: 20px; /* Add space between the icons */
    
}

.actions .edit, .actions .delete, .actions .status-toggle {
    border: none;
    background-color: transparent;
    cursor: pointer;
    font-size: 25px; /* Default icon size */
    padding: 5px;
    transition: transform 0.2s ease-in-out; /* Smooth transition for scaling */
}

.actions .status-toggle.on {
    color: #27ae60;
    transform: scale(1.25); /* Increase size of the 'on' icon */
}

.actions .status-toggle.off {
    color: #e74c3c;
}

.actions .edit {
    color: #f39c12;
}

.actions .delete {
    color: #e74c3c;
}


        .user-image {
            width: 25px;
            height: 25px;
            object-fit: cover;
            border-radius: 5px;
        }



        .modal-content {
        width: 300px;
        margin: 0 auto;
        padding: 20px 40px;
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    
    .error { text-align: center; color: red; } /* Updated error color */
    .form-group { margin-bottom: 20px; color: black; position: relative; }
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
    .error-message{
        color:red;
    }
    .success{
        color:green;
    }



    .modal-content button {
        width: 100%; padding: 10px; background-color: #8903dc; color: white; margin-bottom:10px;
        border: none; border-radius: 3px; cursor: pointer; font-weight: bold; font-size: large;
    }
    button:hover { background-color: wheat; }
    .home-option { text-align: center; }
    .k { color: black; }
    .k a { color: #8903dc; }

    /* Optional: Highlight exact matches */
table tr {
    transition: background-color 0.3s ease;
}

/* Style for highlighted rows (exact matches) */
table tr[style*="background-color"] {
    font-weight: bold;
}
.edit-img{

    text-align:center;
}



    

    </style>
</head>
<body>
    <div class="sidebar">
        <i class="fa-solid fa-circle-user" style=" font-size:50px;
            border-radius: 50%;
            margin-bottom: 15px; "></i>
        <p style="margin-bottom:60px;" >Hello,<?php echo htmlspecialchars($admin_name); ?></p>
        <a href="admin_dashboard.php"><i class="fa-solid fa-house"></i>Admin</a>
        <a href="total_users.php" class="active"><i class="fa-solid fa-users"></i>Users</a>
    <a href="total_movies.php"><i class="fa-solid fa-video"></i>Movies</a>
    <a href="total_series.php"><i class="fa-solid fa-tv"></i>Series</a>
    <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
</div>

    <div class="main-content">
        
        <button class="add-user-btn" onclick="showModal()"><i class="fa-solid fa-plus"></i> new User</button>

        <!-- Add User Modal Form -->
      
            
        <div id="addUserModal" class="modal" style="display: <?= $showModal ? 'flex' : 'none' ?>;">

    <div class="modal-content">
        <h3 class="k">Add New User</h3>
        <?php if ($showAlert): ?>
            <div class="success">User successfully registered!</div>
        <?php endif; ?>
        <?php if (!empty($errors['general'])): ?>
            <div class="error"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                <input type="text" id="name" name="name" placeholder="Name" class="custom-field" value="<?= htmlspecialchars($name) ?>">
                <?php if (!empty($errors['name'])): ?><div class="error-message"><?= $errors['name'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder="Username" class="custom-field" value="<?= htmlspecialchars($username) ?>">
                <?php if (!empty($errors['username'])): ?><div class="error-message"><?= $errors['username'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Email" class="custom-field" value="<?= htmlspecialchars($email) ?>">
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
            <button type="submit">Register New User</button>
                    <button type="button"  style=" background-color:white;  color: #e74c3c;   border :1px solid  #e74c3c;" onclick="hideModal()">Cancel</button>
                </form>
            </div>
        </div>

        <div id="editUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Edit User</h2>
        <form onsubmit="event.preventDefault(); saveUserChanges();">
            <input type="hidden" id="editUserId">
             
            <div class=edit-img>
            <img id="editImage" src="" alt="Current Profile Image" class="user-image-preview" width="150px" height="150px" style=" border: 1px solid black; border-radius:50%; object-fit:cover;">
                </div>
           
            <input type="file" id="editImageFile">
            <span id="imageError" class="error-message"></span>
            
            <label for="editName">Name:</label>
            <input type="text" id="editName" required>
            <span id="nameError" class="error-message"></span>
            
            <label for="editUsername">Username:</label>
            <input type="text" id="editUsername" required>
            <span id="usernameError" class="error-message"></span>
            
            <label for="editEmail">Email:</label>
            <input type="email" id="editEmail" required>
            <span id="emailError" class="error-message"></span>
            
            <label for="editPassword">Password (optional):</label>
            <input type="password" id="editPassword" >
            
            <span id="passwordError" class="error-message"></span>
            
            
            
            <button type="submit" style="margin-top:20px;">Save Changes</button>
            <button type="button"  onclick="document.getElementById('editUserModal').style.display='none';">Cancel</button>
        </form>
    </div>
</div>


        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
    <h2>All Users</h2>
    <div style="display: flex; align-items: center; gap: 5px;">
        <input type="text" id="searchInput" placeholder="Search users..." style="
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 3px;
            width: 250px;
        " onkeyup="if (event.keyCode === 13) filterUsers()">
        


        <button style="
            background-color: #8903dc; 
            color: white; 
            padding: 10px; 
            border: none; 
            cursor: pointer; 
            border-radius: 3px;
        " onclick="filterUsers()">
            <i class="fa-solid fa-search"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 10px;">
    <select id="filterOption" onchange="filterUsersByStatus()" style="padding: 10px; border: 1px solid #ccc; border-radius: 3px;">
        <option value="all">All Users</option>
        <option value="active">All Active Users</option>
        <option value="inactive">All Inactive Users</option>
    </select>
</div>

          <!-- Download Button -->
          <button style="
            background-color: #28a745; 
            color: white; 
            padding: 10px; 
            border: none; 
            cursor: pointer; 
            border-radius: 3px;
        " onclick="downloadPDF()">
            <i class="fa-solid fa-download"></i> Download PDF
        </button>
    </div>
</div>


        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
                </thead>
                <tbody id="userTableBody">
                <?php
$index = $offset + 1;
while ($row = $result->fetch_assoc()) {
    // Determine the toggle button classes and icons based on the user's active status
    $statusClass = $row['is_active'] ? 'on' : 'off';
    $statusIcon = $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off';

    echo "<tr>
        <td>{$index}</td>
        <td>{$row['id']}</td>
        <td>{$row['name']}</td>
        <td>{$row['username']}</td>
        <td>{$row['email']}</td>
        <td><img src='{$row['image']}' class='user-image'></td>
        <td class='actions'>
             <!-- Edit Button -->
              <button class='edit' onclick='editUser({$row['id']}, \"{$row['name']}\", \"{$row['username']}\", \"{$row['email']}\", \"{$row['image']}\")'>
        <i class='fa-solid fa-pen-to-square'></i>
    </button>
             
             <!-- Delete Button with Confirmation -->
             <button class='delete' onclick='confirmDeleteUser({$row['id']})'>
                 <i class='fa-solid fa-trash'></i>
             </button>
             
             <!-- Status Toggle Button -->
             <button class='status-toggle {$statusClass}' onclick='toggleUserStatus({$row['id']}, \"{$row['username']}\", {$row['is_active']})'>
                 <i class='fa-solid {$statusIcon}'></i>
             </button>
        </td>
    </tr>";
    $index++;
}
?>

            </tbody>
            </table>

        <div id="pagination" style="margin-top: 20px; text-align: center;">
    <?php if ($totalPages > 1): ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="total_users.php?page=<?= $i ?>" 
               style="display: inline-block; padding: 10px; margin: 5px; background-color: <?= ($i == $page) ? '#8903dc' : '#f0f0f0' ?>; color: <?= ($i == $page) ? 'white' : 'black' ?>; text-decoration: none; border-radius: 5px;">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>  


    </div>

    <script>
     function showModal() {
    // Reset the form fields
    document.querySelector('#addUserModal form').reset();

    // Clear any error messages
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(error => {
        error.textContent = '';
    });

    // Optionally reset other fields or custom UI elements here
    const customFields = document.querySelectorAll('.custom-field');
    customFields.forEach(field => {
        if (field.tagName === 'INPUT' || field.tagName === 'TEXTAREA') {
            field.value = '';
        }
        if (field.tagName === 'SELECT') {
            field.selectedIndex = 0;
        }
    });

    // Show the modal
    document.getElementById('addUserModal').style.display = 'flex';
}


function hideModal() {
    // Hide the modal
    document.getElementById('addUserModal').style.display = 'none';

    // Reset the form
    const form = document.querySelector('#addUserModal form');
    form.reset();

    // Clear any error messages
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(error => {
        error.textContent = '';
    });

    // Reset input field styles (if any style was applied for errors)
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.value = ''; // Clear inputs
    });
}
        // Function to toggle the status of the user (on/off)
        function toggleStatus(button) {
            // Toggle between 'on' and 'off' classes
            if (button.classList.contains('off')) {
                button.classList.remove('off');
                button.classList.add('on');
                button.innerHTML = '<i class="fa-solid fa-toggle-on"></i>';
            } else {
                button.classList.remove('on');
                button.classList.add('off');
                button.innerHTML = '<i class="fa-solid fa-toggle-off"></i>';
            }
        }

       
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


        let allUsers = []; // Store all user records

        async function filterUsers() {
    const query = document.getElementById("searchInput").value.toLowerCase();
    const tableBody = document.querySelector("table tbody");

    // Fetch all rows (not just the current page)
    const response = await fetch("fetch_all_users.php");
    const users = await response.json();

    // Filter rows based on query
    const filteredUsers = users.filter(user => {
        return user.name.toLowerCase().includes(query) ||
               user.username.toLowerCase().includes(query) ||
               user.email.toLowerCase().includes(query);
    });

    // Clear current table body
    tableBody.innerHTML = "";
    pagination.style.display = filteredUsers.length > 0 ? "none" : "block";

    // Populate filtered rows
    if (filteredUsers.length > 0) {
        filteredUsers.forEach((user, index) => {
            const row = document.createElement("tr");

            // Check if the query is an exact match for any field
            const isExactMatch = 
                user.name.toLowerCase() === query ||
                user.username.toLowerCase() === query ||
                user.email.toLowerCase() === query;

            // Highlight row if exact match
            if (isExactMatch) {
                row.style.backgroundColor = "#f9f9a1"; // Light yellow highlight
            }

            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td><img src="${user.image}" class="user-image"></td>
                <td class="actions">
                    <button class='edit' onclick='editUser(${user.id}, "${user.name}", "${user.username}", "${user.email}", "${user.image}")'>
                        <i class='fa-solid fa-pen-to-square'></i>
                    </button>
                    <button class='delete' onclick='confirmDeleteUser(${user.id})'>
                        <i class='fa-solid fa-trash'></i>
                    </button>
                    <button class='status-toggle ${user.is_active ? "on" : "off"}' onclick='toggleUserStatus(${user.id}, "${user.username}", ${user.is_active})'>
                        <i class='fa-solid ${user.is_active ? "fa-toggle-on" : "fa-toggle-off"}'></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    } else {
        // Display "No matches found"
        const row = document.createElement("tr");
        row.innerHTML = `<td colspan="7" style="text-align: center;">No matches found</td>`;
        tableBody.appendChild(row);
    }
}

function downloadPDF() {
    const filterOption = document.getElementById("filterOption").value;

    // Send the filter and user data to the server to generate PDF
    fetch('generate_pdf.php?filter=' + filterOption)
        .then(response => response.blob())  // Expecting a PDF file
        .then(blob => {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'user_list.pdf';  // Set the filename for the PDF
            link.click();
        })
        .catch(error => console.error('Error generating PDF:', error));

    }




function editUser(userId, name, username, email, image) {
    // Populate a form or modal with user data
    const modal = document.getElementById('editUserModal'); // Assuming you have a modal for editing
    modal.querySelector('#editUserId').value = userId;
    modal.querySelector('#editName').value = name;
    modal.querySelector('#editUsername').value = username;
    modal.querySelector('#editEmail').value = email;
    modal.querySelector('#editImage').src = image;

    // Show the modal
    modal.style.display = 'block';
}

// Function to handle form submission
async function saveUserChanges() {
        const userId = document.getElementById('editUserId').value;
        const name = document.getElementById('editName').value;
        const username = document.getElementById('editUsername').value;
        const email = document.getElementById('editEmail').value;
        const password = document.getElementById('editPassword').value;
        const imageFile = document.getElementById('editImageFile').files[0];

        // Clear previous error messages
        clearErrors();

        // Prepare FormData for sending data
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('name', name);
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        try {
            const response = await fetch('edit_user.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.reload(); // Reload the page to reflect changes
            } else {
                displayErrors(result.errors); // Show error messages
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving changes.');
        }
    }

    // Function to display error messages
    function displayErrors(errors) {
        if (errors.name) {
            document.getElementById('nameError').textContent = errors.name;
        }
        if (errors.username) {
            document.getElementById('usernameError').textContent = errors.username;
        }
        if (errors.email) {
            document.getElementById('emailError').textContent = errors.email;
        }
        if (errors.password) {
            document.getElementById('passwordError').textContent = errors.password;
        }
        if (errors.image) {
            document.getElementById('imageError').textContent = errors.image;
        }
    }

    // Function to clear error messages
    function clearErrors() {
        document.getElementById('nameError').textContent = '';
        document.getElementById('usernameError').textContent = '';
        document.getElementById('emailError').textContent = '';
        document.getElementById('passwordError').textContent = '';
        document.getElementById('imageError').textContent = '';
    }

async function confirmDeleteUser(userId) {
    // Confirm deletion
    if (confirm("Are you sure you want to delete this user? This action cannot be undone.")) {
        try {
            const response = await fetch('delete_user.php', {
                method: 'POST',
                body: JSON.stringify({ id: userId }),
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await response.json();
            if (data.success) {
                alert("User deleted successfully.");
                location.reload(); // Reload the page to update the table
            } else {
                alert("Failed to delete user.");
            }
        } catch (error) {
            console.error("Error deleting user:", error);
            alert("An error occurred while deleting the user.");
        }
    }
}

async function toggleUserStatus(userId, username, isActive) {
    const newStatus = isActive ? 'inactive' : 'active';
    const action = isActive ? 'deactivate' : 'activate';

    if (confirm(`Are you sure you want to ${action} ${username}?`)) {
        try {
            const payload = { id: userId, status: newStatus };
            console.log("Request payload:", payload); // Debugging

            const response = await fetch('toggle_status.php', {
                method: 'POST',
                body: JSON.stringify(payload),
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await response.json();
            console.log("Server response:", data); // Debugging

            if (data.success) {
                alert(`User ${newStatus}d successfully.`);
                location.reload();
            } else {
                alert(data.message || "Failed to update user status. Check logs for details.");
            }
        } catch (error) {
            console.error("Error toggling status:", error);
            alert("An error occurred while updating user status.");
        }
    }
}

//For  all users, active users, inactive users



async function filterUsersByStatus() {
    const filterOption = document.getElementById("filterOption").value;
    const tableBody = document.getElementById("userTableBody");

    // Fetch all users
    const response = await fetch("fetch_all_users.php");
    const users = await response.json();

    // Filter users based on the selected option
    let filteredUsers;
    if (filterOption === "active") {
        filteredUsers = users.filter(user => user.is_active === 1);
    } else if (filterOption === "inactive") {
        filteredUsers = users.filter(user => user.is_active === 0);
    } else {
        filteredUsers = users;
    }

    // Clear the table
    tableBody.innerHTML = "";
    pagination.style.display = filteredUsers.length > 0 ? "none" : "block";

    // Populate the table with filtered users
    if (filteredUsers.length > 0) {
        filteredUsers.forEach((user, index) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                 <td>${index + 1}</td>
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td><img src="${user.image}" class="user-image"></td>
                <td class="actions">
                    <button class='edit' onclick='editUser(${user.id}, "${user.name}", "${user.username}", "${user.email}", "${user.image}")'>
                        <i class='fa-solid fa-pen-to-square'></i>
                    </button>
                    <button class='delete' onclick='confirmDeleteUser(${user.id})'>
                        <i class='fa-solid fa-trash'></i>
                    </button>
                    <button class='status-toggle ${user.is_active ? "on" : "off"}' onclick='toggleUserStatus(${user.id}, "${user.username}", ${user.is_active})'>
                        <i class='fa-solid ${user.is_active ? "fa-toggle-on" : "fa-toggle-off"}'></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    } else {
        const noDataRow = document.createElement("tr");
        noDataRow.innerHTML = `<td colspan="7" style="text-align: center;">No users found</td>`;
        tableBody.appendChild(noDataRow);
    }
}




 
    </script>
</body>
</html>
