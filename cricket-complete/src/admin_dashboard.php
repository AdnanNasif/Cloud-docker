<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: login.php");
  exit();
}

$host = 'mysql_db';
$user = 'adnan';
$pass = '123456';
$db   = 'auth_demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$monthStr = str_pad($currentMonth, 2, "0", STR_PAD_LEFT);
$startDate = "$currentYear-$monthStr-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Handle deletion if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $delete_id = (int)$_POST['delete_id'];
  $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  $stmt->execute();
  $stmt->close();
  header("Location: admin_dashboard.php?month=$currentMonth&year=$currentYear");
  exit();
}

$stmt = $conn->prepare("SELECT b.*, u.name, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE booking_date BETWEEN ? AND ? ORDER BY booking_date, booking_time");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
  $date = $row['booking_date'];
  if (!isset($bookings[$date])) {
    $bookings[$date] = [];
  }
  $bookings[$date][] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: #f4f6f8;
      color: #333;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      background: #2c3e50;
      color: white;
    }
    .topbar h1 {
      margin: 0;
      font-size: 24px;
    }
    .hamburger {
      cursor: pointer;
      position: relative;
    }
    .dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      color: #333;
      border: 1px solid #ccc;
      border-radius: 6px;
      display: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 10px;
    }
    .dropdown-menu a {
      color: #3498db;
      text-decoration: none;
    }
    .calendar-container {
      padding: 20px;
      max-width: 1000px;
      margin: auto;
    }
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 20px;
    }
    .calendar-header a {
      color: #3498db;
      text-decoration: none;
      margin: 0 10px;
      font-weight: 400;
    }
    .calendar-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
    }
    .calendar-day-header {
      text-align: center;
      font-weight: bold;
    }
    .calendar-day {
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      cursor: pointer;
    }
    .calendar-day.booked {
      background: #e1f5fe;
      border: 1px solid #81d4fa;
    }
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 10px;
      max-width: 600px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
    }
    .close-btn {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 20px;
      cursor: pointer;
    }
    .booking-item {
      padding: 10px;
      border-bottom: 1px solid #eee;
    }
    button {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 5px;
    }
    button:hover {
      background: #c0392b;
    }
  </style>
</head>
<body>
<div class="topbar">
  <h1>Admin Dashboard - All Bookings</h1>
  <div class="hamburger" onclick="toggleDropdown()">☰
    <div class="dropdown-menu" id="dropdownMenu">
      <p>Logged in as <strong><?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></p>
      <a href="logout.php">Logout</a>
    </div>
  </div>
</div>

<div class="calendar-container">
  <div class="calendar-header">
    <a href="?month=<?= ($currentMonth == 1 ? 12 : $currentMonth - 1); ?>&year=<?= ($currentMonth == 1 ? $currentYear - 1 : $currentYear); ?>">« Prev</a>
    <?= date('F Y', strtotime("$currentYear-$monthStr-01")); ?>
    <a href="?month=<?= ($currentMonth == 12 ? 1 : $currentMonth + 1); ?>&year=<?= ($currentMonth == 12 ? $currentYear + 1 : $currentYear); ?>">Next »</a>
  </div>
  <div class="calendar-days">
    <?php
    $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    foreach ($days as $d) {
        echo "<div class='calendar-day-header'>$d</div>";
    }

    $first_day_of_month = strtotime("$currentYear-$monthStr-01");
    $first_day_weekday = date('w', $first_day_of_month);
    $days_in_month = date('t', $first_day_of_month);

    for ($i = 0; $i < $first_day_weekday; $i++) {
        echo "<div class='calendar-day'></div>";
    }

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = "$currentYear-$monthStr-" . str_pad($day, 2, "0", STR_PAD_LEFT);
        $is_booked = isset($bookings[$date]);
        $day_html = "<strong>$day</strong>";

        if ($is_booked) {
            foreach ($bookings[$date] as $b) {
                $day_html .= "<span>{$b['booking_time']}</span>";
            }
        }

        echo "<div class='calendar-day" . ($is_booked ? " booked" : "") . "' onclick='showDetails(\"$date\")'>$day_html</div>";
    }
    ?>
  </div>
</div>

<div class="modal" id="bookingModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">×</span>
    <h3>Bookings on <span id="modalDate"></span></h3>
    <div id="modalContent"></div>
  </div>
</div>

<script>
  const bookings = <?= json_encode($bookings); ?>;

  function showDetails(date) {
    if (!bookings[date]) return;

    document.getElementById('modalDate').innerText = date;
    const container = document.getElementById('modalContent');
    container.innerHTML = '';

    bookings[date].forEach(b => {
      container.innerHTML += `
        <div class="booking-item">
          <form method="POST" style="margin-bottom: 10px;">
            <input type="hidden" name="delete_id" value="${b.id}" />
            <strong>Time:</strong> ${b.booking_time}<br>
            <strong>Machine:</strong> ${b.machine}<br>
            <strong>Hours:</strong> ${b.hours}<br>
            <strong>Total Cost:</strong> $${b.total_cost}<br>
            <strong>User:</strong> ${b.name} (${b.email})<br>
            <button type="submit" onclick="return confirm('Are you sure you want to delete this booking?')">Delete Booking</button>
          </form>
        </div>
      `;
    });

    document.getElementById('bookingModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('bookingModal').style.display = 'none';
  }

  function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
  }

  window.onclick = function(event) {
    const dropdown = document.getElementById('dropdownMenu');
    if (!event.target.closest('.hamburger')) {
      dropdown.style.display = 'none';
    }
  }
</script>
</body>
</html>