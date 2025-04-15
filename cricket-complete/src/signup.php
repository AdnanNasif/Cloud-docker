<?php
// Database connection
$host = 'mysql_db';
$user = 'adnan';
$pass = '123456';
$db   = 'auth_demo';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // secure password

// Prepare and insert data
$sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query error: " . $conn->error); // show why it failed
}

$stmt->bind_param("sss", $name, $email, $password);

if ($stmt->execute()) {
    echo "Signup successful!";
} else {
    echo "Signup failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
