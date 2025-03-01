<?php
session_start();

if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "toonhub";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the username from the session
$username = $_SESSION['username'];

// Fetch user data including role from the database
$sql = "SELECT image, role FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Save the image path and role in the session
    $_SESSION['profile_image'] = '' . $row['image'];
    $_SESSION['role'] = $row['role'];
} else {
    echo "User not found.";
}

// Get selected category from the sidebar
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch categories and subcategories for the filter menu
$category_result = $conn->query("SELECT DISTINCT maincategory FROM movies UNION SELECT DISTINCT maincategory FROM series");
$subcategory_result = $conn->query("SELECT DISTINCT subcategory FROM movies UNION SELECT DISTINCT subcategory FROM series");

// Fetch movies based on the selected category, ordered by newest
$movie_sql = $category_filter
    ? "SELECT id, title, poster, 'Movie' AS type FROM movies WHERE maincategory = '$category_filter' OR subcategory = '$category_filter' ORDER BY id DESC"
    : ($search_query
        ? "SELECT id, title, poster, 'Movie' AS type FROM movies WHERE title LIKE '%" . $conn->real_escape_string($search_query) . "%' ORDER BY id DESC"
        : "SELECT id, title, poster, 'Movie' AS type FROM movies ORDER BY id DESC");
$movie_result = $conn->query($movie_sql);

// Fetch series based on the selected category, ordered by newest
$series_sql = $category_filter
    ? "SELECT id, title, poster, 'Series' AS type FROM series WHERE maincategory = '$category_filter' OR subcategory = '$category_filter' ORDER BY id DESC"
    : ($search_query
        ? "SELECT id, title, poster, 'Series' AS type FROM series WHERE title LIKE '%" . $conn->real_escape_string($search_query) . "%' ORDER BY id DESC"
        : "SELECT id, title, poster, 'Series' AS type FROM series ORDER BY id DESC");
$series_result = $conn->query($series_sql);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Moovies Hub <?php echo $_SESSION['username']; ?></title>
    <link rel="icon" href="img/title logo.png" type="image/x-icon" />
    <style>
        /* Same CSS styles as before */
        * {
            margin: 5px;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #111;
            color: #fff;
        }

        .navbarspa {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            background-color: #111;
            position: fixed;
            margin-top: 0;
            top: 0;
            left: 0;
            width: 100%;
            height: 90px;
            z-index: 1000;
        }

        .logo {
            font-size: 24px;
            color: #8903dc;
            font-weight: bold;
            position: absolute;
            left: 49%;
            transform: translateX(-50%);
        }

        .menu-icon-left {
            font-size: 24px;
            cursor: pointer;
            position: absolute;
            left: 30px;
        }

        .menu-icon-right {
            right: 30px;
            font-size: 24px;
            cursor: pointer;
            position: absolute;
            right: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-icon-right img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar-left {
            position: fixed;
            top: 80px;
            width: 250px;
            height: 100%;
            background-color: #000;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
        }

        .sidebar-left {
            left: 0;
            transform: translateX(-100%);
        }

        .sidebar-left.active {
            transform: translateX(0);
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
        }

        .sidebar-right {
            right: 0;
            transform: translateX(100%);
        }

        .sidebar-right.active {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            padding: 0 20px;
            margin-bottom: 20px;
        }

        .logo-icon {
            font-size: 30px;
            color: #8903dc;
            margin-right: 10px;
        }

        .sidebar-logo {
            font-size: 20px;
            color: #fff;
            font-weight: bold;
        }


        /* Category options styling */
.category-options {
    display: flex;
    flex-direction: column; /* Stack categories vertically */
    padding: 0 20px;
}

.category-options a {
    color: #fff;
    text-decoration: none;
    font-size: 18px; /* Increased font size */
    padding: 12px 0; /* Add more padding for spacing */
    border-bottom: 1px solid #333;
    transition: background-color 0.3s ease;
}

.category-options a:hover {
    background-color: #8903dc; /* Highlight background color */
    color: #fff; /* Keep text color white when hovered */
}

/* Active category link styling */
.category-options a.active {
    color: #8903dc;
    font-weight: bold;
    background-color: #333; /* Make active category stand out */
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
            font-size: 18px;
            font-weight: bold;
        }

        .edit-profile, .sign-out {
            width: 80%;
            padding: 10px;
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
    padding: 10px 0;
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
        .movie-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .movie {
            text-align: center;
        }

        .movie img {
            width: 200px; /* Set a fixed width */
            height: 300px; /* Set a fixed height */
            object-fit: cover; /* Ensure the image covers the area without distortion */
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .movie img:hover {
            transform: scale(1.1);
        }

        .movie h2 {
            font-size: 16px;
            margin: 10px 0;
        }

        .section-title{
            font-size: 35px;
        }
        /* Dropdown Button */
.dropdown {
    position: relative;
}

.dropdown-btn {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    font-size: 18px;
    color: #fff;
    text-decoration: none;
    background-color: transparent;
    border: none;
    cursor: pointer;
    width: 100%;
    text-align: left;
    transition: background-color 0.3s;
}

.dropdown-btn:hover, .dropdown-btn.active {
    background-color: #8903dc;
    color: #fff;
}

/* Make the dropdown content display when the parent (dropdown-btn) or child (dropdown-content) is hovered */
.dropdown:hover .dropdown-content {
    display: flex;
}

/* Remove the margin-top for the dropdown content to prevent a space */
.dropdown-content {
    display: none;
    flex-direction: column;
    background-color: #000;
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    margin-top: 0; /* Ensure no space between button and dropdown */
}



    .subcategory-dropdown {
        position: relative;
    }

    .subcategory-dropdown-btn {
        padding: 12px 20px;
        font-size: 18px;
        color: #fff;
        background-color: transparent;
        text-decoration: none;
        display: block;
        border-bottom: 1px solid #333;
        cursor: pointer;
    }

    .subcategory-dropdown-btn:hover {
        background-color: #8903dc;
        color: #fff;
    }

    /* For subcategory dropdowns, ensure they remain open when hovered */
.subcategory-dropdown:hover .subcategory-dropdown-content {
    display: flex;
}

.subcategory-dropdown-content {
    display: none;
    position: absolute;
    top: 0;
    left: 100%;
    background-color: #8903dc;
    width: 200px;
    flex-direction: column;
         z-index: 10;
}

    .subcategory-dropdown-content a {
        padding: 10px 20px;
        font-size: 16px;
        color: #fff;
        background-color: transparent;
        text-decoration: none;
        border-bottom: 1px solid #333;
    }

    .subcategory-dropdown-content a:hover {
        background-color: #8903dc;
    }

    .subcategory-dropdown-content a.active {
        color: #8903dc;
        font-weight: bold;
    }

    </style>
</head>
<body>
    
    <header>
        <div class="navbarspa">
            <div class="menu-icon-left" onclick="toggleSidebarLeft()">☰ Menu</div>
            <div class="logo"><img src="img/logo.png" width="225px" height="45px" class="logoimg" /></div>
            <div class="menu-icon-right" onclick="toggleSidebarRight()">
                <img src="<?php echo $_SESSION['profile_image']; ?>" alt="Profile Image" >
                <span><?php echo $_SESSION['username']; ?> </span>
               
            </div>
        </div>
    </header>
    
    <br><br><br><br><br>
    <form action="home.php" method="GET" style="margin-left: auto; margin-top: 10px;float :right;">
                    <input type="text" name="search" placeholder="Search movies or series..." style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                    <button type="submit" style="padding: 10px; border-radius: 5px; background-color: #28a745; color: white; border: none; cursor: pointer;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

    <div class="content-container">
    <!-- Movies Section -->
    <div class="section-title">Movies</div>
    <br><br>
    <hr>
    <div class="movie-container">
        <?php while ($row = $movie_result->fetch_assoc()): ?>
            <div class="movie">
                <a href="movie_details.php?id=<?php echo $row['id']; ?>">
                    <img src="<?php echo $row['poster']; ?>" alt="<?php echo $row['title']; ?>">
                </a>
                <h2><?php echo $row['title']; ?></h2>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Series Section -->
    <div class="section-title">Series</div>
    <br><br>
    <hr>
    <div class="movie-container">
        <?php while ($row = $series_result->fetch_assoc()): ?>
            <div class="movie">
                <a href="series_details.php?id=<?php echo $row['id']; ?>">
                    <img src="<?php echo $row['poster']; ?>" alt="<?php echo $row['title']; ?>">
                </a>
                <h2><?php echo $row['title']; ?></h2>
            </div>
        <?php endwhile; ?>
    </div>
</div>


    

   <!-- Left Sidebar -->
<aside class="sidebar-left" id="sidebarLeft">
    <nav class="category-options">
        <a href="home.php" class="<?= ($category_filter == '') ? 'active' : '' ?>">Home</a>

        <!-- Main Category Dropdowns -->
        <div class="dropdown">
            <a href="#" class="dropdown-btn <?= ($category_filter != '') ? 'active' : '' ?>">
                Movies/Series
                <span class="dropdown-arrow">▼</span>
            </a>
            <div class="dropdown-content">
                <?php
                // Loop through main categories and display each one with a subcategory dropdown
                while ($cat_row = $category_result->fetch_assoc()) {
                    $main_category = $cat_row['maincategory'];
                ?>
                    <div class="subcategory-dropdown">
                        <!-- Main category link -->
                        <a href="#" class="subcategory-dropdown-btn <?= ($category_filter == $main_category) ? 'active' : '' ?>">
                            <?php echo $main_category; ?>
                        </a>

                        <!-- Subcategory dropdown for each main category -->
                        <div class="subcategory-dropdown-content">
                            <?php
                            // Fetch subcategories for the current main category
                            $subcategory_sql = "SELECT DISTINCT subcategory FROM movies WHERE maincategory = '$main_category' UNION SELECT DISTINCT subcategory FROM series WHERE maincategory = '$main_category'";
                            $subcategory_result = $conn->query($subcategory_sql);

                            // Display each subcategory under its main category
                            while ($subcat_row = $subcategory_result->fetch_assoc()) {
                                $subcategory = $subcat_row['subcategory'];
                            ?>
                                <a href="?category=<?php echo $subcategory; ?>" class="<?= ($category_filter == $subcategory) ? 'active' : '' ?>">
                                    <?php echo $subcategory; ?>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </nav>
</aside>



    <!-- Right Sidebar -->
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
            <a href="#" class="<?= ($current_page == 'about.php') ? 'active' : '' ?>"><i class="fas fa-info-circle"></i> About</a>
            </nav>
        </aside>
    
        <script>
            // Toggle left sidebar
            function toggleSidebarLeft() {
                const sidebar = document.getElementById('sidebarLeft');
                sidebar.classList.toggle('active');
            }
    
            // Toggle right sidebar
            function toggleSidebarRight() {
                const sidebar = document.getElementById('sidebarRight');
                sidebar.classList.toggle('active');
            }

            // Add event listener for the Movies/Series dropdown to show/hide the subcategories
    document.querySelector('.dropdown-btn').addEventListener('click', function (e) {
        e.preventDefault();
        const dropdownContent = document.querySelector('.dropdown-content');
        dropdownContent.classList.toggle('active');
    });

    // Add event listener for subcategory dropdowns
    const subcategoryDropdownBtns = document.querySelectorAll('.subcategory-dropdown-btn');
    subcategoryDropdownBtns.forEach(function (button) {
        button.addEventListener('click', function (e) {
            const dropdownContent = this.nextElementSibling;
            dropdownContent.classList.toggle('active');
        });
    });

        </script>
    </body>
    </html>
