<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Fetch user's email from database if not already set in session
if (!isset($_SESSION['email'])) {
  require_once 'db.php'; // Make sure this file sets up $conn properly

  $user_id = $_SESSION['user_id'];
  $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
  if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email);
    if ($stmt->fetch()) {
      $_SESSION['email'] = $email;
    }
    $stmt->close();
  }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hourly Booking</title>
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
      display: flex;
      justify-content: flex-end;
      align-items: center;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
    }

    .hamburger {
      font-size: 26px;
      color: white;
      cursor: pointer;
      user-select: none;
      margin-right: 35px;
    }

    .dropdown {
      display: none;
      position: absolute;
      top: 50px;
      right: 20px;
      background-color: #333;
      border-radius: 5px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }

    .dropdown a, .dropdown div {
      color: white;
      padding: 12px 20px;
      display: block;
      text-decoration: none;
    }

    .dropdown a:hover {
      background-color: #444;
    }

    .booking-container {
      background: white;
      padding: 2em;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      width: 90%;
      max-width: 500px;
      margin: 100px auto;
    }

    h2 {
      text-align: center;
      color: #333;
    }

    label {
      display: block;
      margin-top: 1em;
      font-weight: bold;
    }

    input, select {
      width: 100%;
      padding: 0.5em;
      margin-top: 0.5em;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .total {
      margin-top: 1em;
      font-size: 1.2em;
      font-weight: bold;
      text-align: center;
    }

    button {
      margin-top: 1.5em;
      padding: 0.75em;
      width: 100%;
      background-color: #b22222;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 1em;
      cursor: pointer;
    }

    button:hover {
      background-color: #8b1a1a;
    }
  </style>
</head>
<body>

  <div class="top-bar">
    <div class="hamburger" onclick="toggleDropdown()">☰</div>
    <div class="dropdown" id="menuDropdown">
      <div>Logged in as: <?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Unknown'; ?></div>
      <a href="my_bookings.php">My Bookings</a>
      <a href="change_password.php">Change Password</a>
      <a href="#" onclick="logoutUser()">Logout</a>
    </div>
  </div>

  <div class="booking-container">
    <h2>Book Your Slot</h2>
    <form id="bookingForm" method="POST" action="save_booking.php">
      <label for="date">Select Date:</label>
      <input type="date" id="date" name="date" required />

      <label for="time">Select Time:</label>
      <input type="time" id="time" name="time" required />

      <label for="hours">Number of Hours:</label>
      <input type="number" id="hours" name="hours" min="1" required />

      <label for="machine">Bowling Machine:</label>
      <select id="machine" name="machine">
        <option value="no">No</option>
        <option value="yes">Yes</option>
      </select>

      <input type="hidden" id="total" name="total">

      <div class="total" id="totalPrice">Total Price: $0</div>

      <button type="submit">Confirm Booking</button>
    </form>
  </div>

  <script>
    function toggleDropdown() {
      const menu = document.getElementById('menuDropdown');
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    window.onclick = function(event) {
      const menu = document.getElementById('menuDropdown');
      const icon = document.querySelector('.hamburger');
      if (!menu.contains(event.target) && !icon.contains(event.target)) {
        menu.style.display = 'none';
      }
    }

    function logoutUser() {
      window.location.href = "logout.php";
    }

    const form = document.getElementById('bookingForm');
    const hoursInput = document.getElementById('hours');
    const machineSelect = document.getElementById('machine');
    const totalPriceDisplay = document.getElementById('totalPrice');
    const totalInput = document.getElementById('total');

    function calculatePrice() {
      const hours = parseInt(hoursInput.value) || 0;
      const withMachine = machineSelect.value === 'yes';
      const pricePerHour = withMachine ? 45 : 35;
      const total = hours * pricePerHour;
      totalPriceDisplay.textContent = `Total Price: $${total}`;
      totalInput.value = total;
    }

    hoursInput.addEventListener('input', calculatePrice);
    machineSelect.addEventListener('change', calculatePrice);

    form.addEventListener('submit', function(event) {
      calculatePrice();
    });
  </script>
</body>
</html>
