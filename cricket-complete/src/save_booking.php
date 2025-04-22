<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Database connection
$host = 'mysql_db';
$user = 'adnan';
$pass = '123456';
$db   = 'auth_demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT username, name, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_query->bind_result($username, $name, $email);
$user_query->fetch();
$user_query->close();

// Ensure username is not NULL
$username = $username ?? 'unknown_user'; // Fallback if username is NULL
$name = $name ?? 'Unknown'; // Fallback for nullable name
$email = $email ?? ''; // Should not be NULL due to users table constraints

// Validate form data
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 0;
$machine = $_POST['machine'] ?? '';
$total = isset($_POST['total']) ? (float)$_POST['total'] : 0;

// Basic validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || 
    !preg_match('/^\d{2}:\d{2}$/', $time) || 
    $hours < 1 || 
    !in_array($machine, ['yes', 'no']) || 
    $total <= 0 || 
    empty($email)) {
    die("Invalid input data. Please check your booking details or user profile.");
}

// Insert booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, username, email, booking_date, booking_time, hours, machine, total_cost, name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssisis", $user_id, $username, $email, $date, $time, $hours, $machine, $total, $name);

if ($stmt->execute()) {
    // Send emails
    $mail = new PHPMailer(true);
    $email_error = '';
    try {
        // SMTP config
        $mail->isSMTP();
        $mail->Host = 'mail.pacecloud.com';
        $mail->Port = 465; // Updated to port 465
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL for port 465
        $mail->SMTPAuth = true;
        $mail->Username = 'adnan.nasif@pacecloud.com';
        $mail->Password = ''; // Replace with actual password
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer[$level]: $str");
        }; // Log to error_log

        // Common email content
        $machine_display = $machine === 'yes' ? 'Yes' : 'No';
        $subject = "Booking Confirmation";
        $body = "
            Hi $name,<br><br>
            Your booking has been confirmed with the following details:<br>
            <strong>Date:</strong> $date<br>
            <strong>Time:</strong> $time<br>
            <strong>Hours:</strong> $hours<br>
            <strong>Bowling Machine:</strong> $machine_display<br>
            <strong>Total:</strong> \$$total<br><br>
            Thank you for booking!
        ";

        // Send to user
        $mail->setFrom('adnan.nasif@pacecloud.com', 'Booking System');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();

        // Send to admin
        $mail->clearAddresses();
        $mail->addAddress('admin@pacecloud.com', 'Admin'); // Replace with real admin email
        $mail->Subject = "New Booking Received";
        $mail->Body = "
            A new booking has been made:<br><br>
            <strong>Name:</strong> $name<br>
            <strong>Email:</strong> $email<br>
            <strong>Date:</strong> $date<br>
            <strong>Time:</strong> $time<br>
            <strong>Hours:</strong> $hours<br>
            <strong>Bowling Machine:</strong> $machine_display<br>
            <strong>Total:</strong> \$$total
        ";
        $mail->send();

    } catch (Exception $e) {
        $email_error = "Mailer Error: " . $mail->ErrorInfo;
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }

    // Redirect to My Bookings with error message if email failed
    if ($email_error) {
        header("Location: my_bookings.php?error=" . urlencode($email_error));
    } else {
        header("Location: my_bookings.php");
    }
    exit();
} else {
    echo "Database Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>