<?php
session_start();
if (!isset($_SESSION['user_id'])) {
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

$user_id = $_SESSION['user_id'];
// Validate month and year inputs
$month = isset($_GET['month']) && is_numeric($_GET['month']) && $_GET['month'] >= 1 && $_GET['month'] <= 12 ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Ensure month is two digits for consistency
$month = str_pad($month, 2, "0", STR_PAD_LEFT);

$first_day_of_month = strtotime("$year-$month-01");
$last_day_of_month = strtotime("last day of this month", $first_day_of_month);

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? AND booking_date BETWEEN ? AND ? ORDER BY booking_date, booking_time");
$start_date = "$year-$month-01";
$end_date = "$year-$month-" . date('t', $first_day_of_month);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
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
    <title>My Bookings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f7f7;
            color: #333;
        }
        /* Header */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #2c3e50;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .topbar h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .hamburger {
            font-size: 28px;
            cursor: pointer;
        }
        .dropdown-menu {
            position: absolute;
            right: 20px;
            top: 60px;
            background-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            display: none;
            min-width: 180px;
            overflow: hidden;
        }
        .dropdown-menu p, .dropdown-menu a {
            padding: 10px;
            color: #333;
            font-weight: 400;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        .dropdown-menu a:hover {
            background-color: #e8ecef;
        }
        /* Calendar */
        .calendar-container {
            max-width: 900px;
            margin: 30px auto;
            background: linear-gradient(135deg, #ffffff, #f8fafc); /* Subtle gradient */
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .calendar-header span {
            font-size: 20px;
            font-weight: 600;
            color: #34495e;
        }
        .calendar-header button {
            background-color: #3498db;
            color: white;
            font-size: 22px;
            border: none;
            padding: 10px;
            width: 40px;
            height: 40px;
            cursor: pointer;
            border-radius: 50%;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .calendar-header button:hover {
            background-color: #2980b9;
            transform: scale(1.1);
        }
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 20px;
            font-size: 15px;
            text-align: center;
        }
        .calendar-day-header {
            font-weight: 600;
            font-size: 16px;
            color: #34495e;
            padding-bottom: 10px;
        }
        .calendar-day {
            position: relative;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 80px; /* Ensure enough space for booking times */
        }
        .calendar-day:hover {
            background-color: #f1f3f5;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .calendar-day.booked {
            background: linear-gradient(135deg, #1abc9c, #16a085); /* Modern gradient */
            color: white;
            border: 1px solid #16a085;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .calendar-day.booked:hover {
            background: linear-gradient(135deg, #16a085, #1abc9c);
            transform: translateY(-2px);
        }
        .calendar-day .booking-time {
            position: relative;
            display: inline-block;
            margin-top: 5px;
            font-size: 11px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            border-radius: 4px;
            padding: 3px 8px;
            line-height: 1.5;
        }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            border-radius: 12px;
            width: 80%;
            max-width: 500px;
            padding: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .modal-content h3 {
            margin-bottom: 15px;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            cursor: pointer;
        }
        .close-btn:hover {
            color: #e74c3c;
        }
        .booking-item {
            margin: 10px 0;
            padding: 12px;
            background-color: #f39c12;
            border-radius: 8px;
            color: white;
        }
        /* Responsive Design */
        @media (max-width: 600px) {
            .calendar-container {
                padding: 20px;
                margin: 20px;
            }
            .calendar-days {
                gap: 10px;
                font-size: 14px;
            }
            .calendar-day {
                padding: 10px;
                min-height: 60px;
            }
            .calendar-day .booking-time {
                font-size: 10px;
                padding: 2px 6px;
            }
            .calendar-header span {
                font-size: 18px;
            }
            .calendar-header button {
                width: 35px;
                height: 35px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    <h1>My Bookings</h1>
    <div class="hamburger" onclick="toggleDropdown()">☰
        <div class="dropdown-menu" id="dropdownMenu">
            <p>Logged in as <strong><?= isset($_SESSION['email']) && !empty($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'User'; ?></strong></p>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="calendar-container">
    <div class="calendar-header">
        <button onclick="changeMonth(-1)">‹</button>
        <span><?= date('F Y', strtotime("$year-$month-01")); ?></span>
        <button onclick="changeMonth(1)">›</button>
    </div>
    <div class="calendar-days">
        <?php
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($days as $d) {
            echo "<div class='calendar-day-header'>$d</div>";
        }

        $first_day_weekday = date('w', $first_day_of_month);
        $days_in_month = date('t', $first_day_of_month);

        for ($i = 0; $i < $first_day_weekday; $i++) {
            echo "<div class='calendar-day'></div>";
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = date('Y-m-') . str_pad($day, 2, "0", STR_PAD_LEFT);
            $is_booked = isset($bookings[$date]);
            $day_html = "<strong>$day</strong>";

            if ($is_booked) {
                foreach ($bookings[$date] as $b) {
                    $day_html .= "<div class='booking-time'>{$b['booking_time']}</div>";
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

    function toggleDropdown() {
        const menu = document.getElementById('dropdownMenu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    function changeMonth(direction) {
        let currentMonth = parseInt(<?= json_encode($month); ?>); // Convert to integer
        let currentYear = parseInt(<?= json_encode($year); ?>);   // Convert to integer
        currentMonth += direction;

        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        } else if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }

        // Pad month to ensure two-digit format
        let paddedMonth = currentMonth.toString().padStart(2, '0');
        window.location.href = `?month=${paddedMonth}&year=${currentYear}`;
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