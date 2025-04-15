<?php
$host = 'mysql_db';
$user = 'adnan';
$pass = '123456';
$db   = 'auth_demo';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// No closing PHP tag, no echo, no whitespace

