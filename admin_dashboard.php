<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("location: admin_login.php");
    exit();
}
include 'config.php';

$admin_name = $_SESSION['username'];



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            font-size:20px;
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
    margin-top: 230px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
}

.logout-btn:hover {
    background-color: #c0392b;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}
.card-container {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    margin: 20px;
    padding: 30px 250px;
}

.card {
    width: 200px;
    height: 120px;
    background: linear-gradient(135deg, #6a11cb, #2575fc); /* Gradient background */
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); /* Softer shadow */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    color: white;
    position: relative;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25); /* Enhanced hover effect */
}

.card i {
    font-size: 30px;
    margin-bottom: 15px;
    color: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
}

.card p {
    font-size: 15px;
    font-weight: bold;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.card::before {
    content: '';
    position: absolute;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1); /* Decorative semi-transparent circle */
    border-radius: 50%;
    top: -20px;
    right: -20px;
    transform: rotate(45deg);
}
h1{
    padding:0px 250px;
}


    </style>
</head>
<body>
    <div class="sidebar">
        <i class="fa-solid fa-circle-user" style=" font-size:50px;
            border-radius: 50%;
            margin-bottom: 15px; "></i>
        <p style="margin-bottom:60px;" >Hello,<?php echo htmlspecialchars($admin_name); ?></p>
        <a href="admin_dashboard.php" class="active"><i class="fa-solid fa-house"></i>Admin</a>
        <a href="total_users.php"><i class="fa-solid fa-users"></i>Users</a>
    <a href="total_movies.php"><i class="fa-solid fa-video"></i>Movies</a>
    <a href="total_series.php"><i class="fa-solid fa-tv"></i>Series</a>
    <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
</div>

<h1>Admin Dashboard</h1>
    <div class="card-container">
    <a href="total_users.php" style="text-decoration: none;">
        <div class="card">
            <i class="fa-solid fa-users"></i>
            <p>Total Registered Users</p>
        </div>
    </a>
    <a href="total_movies.php" style="text-decoration: none;">
        <div class="card">
            <i class="fa-solid fa-video"></i>
            <p>Total Movies</p>
        </div>
    </a>
    <a href="total_series.php" style="text-decoration: none;">
        <div class="card">
            <i class="fa-solid fa-tv"></i>
            <p>Total Series</p>
        </div>
    </a>

</div>

 
</body>
</html>
