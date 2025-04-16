<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB credentials
$host = 'mysql_db';
$user = 'adnan';
$pass = '123456';
$db   = 'auth_demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch bookings
$user_id = $_SESSION['user_id'];
$query = "SELECT id, booking_date, booking_time, machine, hours, total_cost FROM bookings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[$row['booking_date']][] = $row;
}

$stmt->close();
$conn->close();

// Get current calendar month
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$first_day_of_month = strtotime("$current_year-$current_month-01");
$first_day_weekday = date('w', $first_day_of_month);
$days_in_month = date('t', $first_day_of_month);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings Calendar</title>
  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #fceabb, #f8b500);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .calendar {
      width: 95%;
      max-width: 1000px;
      background: #fff;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .calendar-header {
      text-align: center;
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 20px;
      color: #444;
    }

    .calendar-header a {
      margin: 0 20px;
      text-decoration: none;
      font-size: 1.5rem;
      color: #ff4081;
      transition: color 0.3s;
    }

    .calendar-header a:hover {
      color: #e91e63;
    }

    .calendar-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
    }

    .calendar-day-header {
      text-align: center;
      background: #4db6ac;
      color: white;
      padding: 10px 0;
      font-weight: bold;
      border-radius: 8px;
      text-transform: uppercase;
      font-size: 0.9rem;
    }

    .calendar-day {
      background: #f1f1f1;
      border-radius: 12px;
      padding: 10px;
      min-height: 100px;
      position: relative;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
    }

    .calendar-day:hover {
      background: #ffe082;
    }

    .calendar-day strong {
      font-size: 1.1rem;
      font-weight: bold;
    }

    .calendar-day.booked {
      background: #ffccbc;
      color: #212121;
      font-weight: bold;
      position: relative;
    }

    .calendar-day.booked span {
      display: block;
      background: #ff7043;
      color: white;
      font-size: 0.75rem;
      padding: 3px 6px;
      margin-top: 6px;
      border-radius: 8px;
      text-align: center;
      word-wrap: break-word;
    }

    /* Modal Styling */
    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 1000;
      padding: 20px;
    }

    .modal-content {
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      width: 100%;
      max-width: 500px;
      position: relative;
      animation: zoomIn 0.3s ease;
      overflow-y: auto;
      max-height: 90vh;
    }

    @keyframes zoomIn {
      from { transform: scale(0.8); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .close-btn {
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 24px;
      cursor: pointer;
      color: #999;
    }

    .close-btn:hover {
      color: #000;
    }

    .booking-item {
      background: #ffecb3;
      padding: 10px;
      margin: 10px 0;
      border-radius: 10px;
      font-size: 0.95rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .booking-item strong {
      color: #333;
    }

    /* 🔁 Responsive */
    @media (max-width: 768px) {
      .calendar-header {
        font-size: 1.4rem;
      }
      .calendar-header a {
        font-size: 1.2rem;
        margin: 0 10px;
      }
      .calendar-days {
        grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
      }
      .calendar-day {
        min-height: 80px;
        font-size: 0.85rem;
      }
      .calendar-day-header {
        font-size: 0.75rem;
        padding: 6px 0;
      }
    }

    @media (max-width: 480px) {
      .calendar {
        padding: 20px;
      }
      .calendar-header {
        font-size: 1.2rem;
      }
      .calendar-day {
        padding: 6px;
      }
      .modal-content {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
<div class="calendar">
  <div class="calendar-header">
    <a href="?month=<?= $current_month == 1 ? 12 : $current_month - 1; ?>&year=<?= $current_month == 1 ? $current_year - 1 : $current_year; ?>">←</a>
    <?= date('F Y', $first_day_of_month); ?>
    <a href="?month=<?= $current_month == 12 ? 1 : $current_month + 1; ?>&year=<?= $current_month == 12 ? $current_year + 1 : $current_year; ?>">→</a>
  </div>

  <div class="calendar-days">
    <?php
    $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    foreach ($days as $d) {
        echo "<div class='calendar-day-header'>$d</div>";
    }

    for ($i = 0; $i < $first_day_weekday; $i++) {
        echo "<div class='calendar-day'></div>";
    }

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = "$current_year-" . str_pad($current_month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
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

<!-- Booking Modal -->
<div id="bookingModal" class="modal">
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
          <strong>Time:</strong> ${b.booking_time}<br>
          <strong>Machine:</strong> ${b.machine}<br>
          <strong>Hours:</strong> ${b.hours}<br>
          <strong>Total Cost:</strong> $${b.total_cost}
        </div>
      `;
    });

    document.getElementById('bookingModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('bookingModal').style.display = 'none';
  }
</script>
</body>
</html>
