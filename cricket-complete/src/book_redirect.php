<?php
session_start();

// If user is logged in, redirect to booking page
if (isset($_SESSION['user_id'])) {
  header("Location: booking.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In or Sign Up</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .top-bar {
      background-color: black;
      color: white;
      padding: 10px 20px;
      text-align: center;
      font-size: 1.2em;
    }

    .auth-container {
      background: white;
      padding: 2em;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      width: 90%;
      max-width: 500px;
      margin: 80px auto;
    }

    h2 {
      text-align: center;
      color: #b22222;
      margin-bottom: 1em;
    }

    form {
      margin-bottom: 2em;
    }

    label {
      display: block;
      margin-top: 1em;
      font-weight: bold;
    }

    input {
      width: 100%;
      padding: 0.5em;
      margin-top: 0.5em;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    input[type="submit"] {
      margin-top: 1.5em;
      padding: 0.75em;
      background-color: #b22222;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 1em;
      cursor: pointer;
    }

    input[type="submit"]:hover {
      background-color: #8b1a1a;
    }
  </style>
</head>
<body>
  <div class="top-bar">Buffalo Indoor Cricket</div>

  <div class="auth-container">
    <h2>Please Sign In or Sign Up to Continue</h2>

    <form action="login.php" method="post">
      <h3>Login</h3>
      <label>Email:</label>
      <input type="email" name="email" required />
      <label>Password:</label>
      <input type="password" name="password" required />
      <input type="submit" value="Login" />
    </form>

    <form action="signup.php" method="post">
      <h3>Sign Up</h3>
      <label>Full Name:</label>
      <input type="text" name="name" required />
      <label>Email:</label>
      <input type="email" name="email" required />
      <label>Password:</label>
      <input type="password" name="password" required />
      <input type="submit" value="Sign Up" />
    </form>
  </div>
</body>
</html>
