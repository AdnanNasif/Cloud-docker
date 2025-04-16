<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'mysql_db';
$user = 'adnan';
$pass = '123456';
$db   = 'auth_demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data from session
$user_id = $_SESSION['user_id'];

// Get username and email from users table
$user_query = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_query->bind_result($name, $email);
$user_query->fetch();
$user_query->close();

// Get booking form data
$date = $_POST['date'];
$time = $_POST['time'];
$hours = $_POST['hours'];
$machine = $_POST['machine'];
$total = $_POST['total'];

// Insert booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, username, email, booking_date, booking_time, hours, machine, total_cost, name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssisis", $user_id, $name, $email, $date, $time, $hours, $machine, $total, $name);

if ($stmt->execute()) {
    // After successful booking, redirect to my_bookings.php
    header("Location: my_bookings.php");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
