<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("location: login.php");
    exit();
}

if ($_SESSION['usertype'] != 'd') {
    header("location: login.php");
    exit();
}

include("../connection.php");
date_default_timezone_set('Asia/Singapore');

$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$username = $userfetch["docname"];
$userphoto = $userfetch["photo"];
$photopath = $userphoto ? "../admin/uploads/" . $userphoto : "../Media/Icon/Blue/profile.png";

// Get counts for sidebar
$patientrow = $database->query("SELECT COUNT(DISTINCT pid) FROM appointment WHERE docid='$userid'");
$appointmentrow = $database->query("SELECT COUNT(*) FROM appointment WHERE status='booking' AND docid='$userid'");
$schedulerow = $database->query("SELECT COUNT(*) FROM appointment WHERE status='appointment' AND docid='$userid'");

// Calendar variables
$today = date('Y-m-d');
$currentMonth = date('F');
$currentYear = date('Y');
$daysInMonth = date('t');
$firstDayOfMonth = date('N', strtotime("$currentYear-" . date('m') . "-01"));
$currentDay = date('j');

// Fetch bookings query
$sqlmain = "SELECT 
            appointment.appoid, 
            procedures.procedure_name, 
            patient.pname, 
            patient.pid,
            appointment.appodate, 
            appointment.appointment_time,
            patient.profile_pic
        FROM appointment
        INNER JOIN patient ON appointment.pid = patient.pid
        INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id
        WHERE appointment.docid = '$userid' 
          AND appointment.status = 'booking'";

// Check if filter is applied
if (isset($_POST['filter'])) {
    $filterDate = $_POST['appodate'];
    
    if (!empty($filterDate)) {
        $sqlmain .= " AND appointment.appodate = '$filterDate'";
    }
}

// Execute the query
$result = $database->query($sqlmain);
$booking_count = $result->num_rows;

// Handle booking actions
if ($_GET) {
    $id = $_GET["id"];
    $action = $_GET["action"];

    // First get appointment details
    $bookingQuery = $database->query("
        SELECT a.*, p.pid, p.pname, p.pemail, pr.procedure_name 
        FROM appointment a
        JOIN patient p ON a.pid = p.pid
        JOIN procedures pr ON a.procedure_id = pr.procedure_id
        WHERE a.appoid = '$id' AND a.docid = '$userid'
    ");
    
    if ($bookingQuery && $bookingQuery->num_rows > 0) {
        $booking = $bookingQuery->fetch_assoc();
        
        if ($action == 'accept') {
            $database->query("UPDATE appointment SET status='appointment' WHERE appoid='$id'");
            
            // Create notification for patient
            $notificationTitle = "Booking Accepted";
            $notificationMessage = "Your booking for " . $booking['procedure_name'] . " on " . 
                                 date('M j, Y', strtotime($booking['appodate'])) . " at " . 
                                 date('g:i A', strtotime($booking['appointment_time'])) . 
                                 " has been accepted by Dr. " . $username;
            
            $notificationQuery = $database->prepare("
                INSERT INTO notifications (user_id, user_type, title, message, related_id, related_type, created_at, is_read)
                VALUES (?, 'p', ?, ?, ?, 'appointment', NOW(), 0)
            ");
            $notificationQuery->bind_param("issi", 
                $booking['pid'], 
                $notificationTitle, 
                $notificationMessage, 
                $id
            );
            $notificationQuery->execute();
            
            header("Location: booking.php");
            exit();
            
        } elseif ($action == 'reject') {
            // Archive appointment before deleting
            $status = 'rejected';
            $rejectedBy = 'dentist';
        
            $archiveQuery = $database->prepare("
                INSERT INTO appointment_archive (
                    appoid, pid, docid, appodate, appointment_time,
                    procedure_id, event_name, status, cancel_reason, archived_at
                )
                SELECT appoid, pid, docid, appodate, appointment_time,
                       procedure_id, event_name, ?, ?, NOW()
                FROM appointment 
                WHERE appoid = ?
            ");
            $archiveQuery->bind_param("ssi", $status, $rejectedBy, $id);
            $archiveQuery->execute();
        
            // Then delete from appointment table
            $database->query("DELETE FROM appointment WHERE appoid='$id'");
        
            // Create notification for patient
            $notificationTitle = "Booking Rejected";
            $notificationMessage = "Your booking for " . $booking['procedure_name'] . " on " . 
                                 date('M j, Y', strtotime($booking['appodate'])) . " at " . 
                                 date('g:i A', strtotime($booking['appointment_time'])) . 
                                 " has been rejected by Dr. " . $username;
        
            $notificationQuery = $database->prepare("
                INSERT INTO notifications (user_id, user_type, title, message, related_id, related_type, created_at, is_read)
                VALUES (?, 'p', ?, ?, ?, 'appointment', NOW(), 0)
            ");
            $notificationQuery->bind_param("issi", 
                $booking['pid'], 
                $notificationTitle, 
                $notificationMessage, 
                $id
            );
            $notificationQuery->execute();
        
            header("Location: booking.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/table.css">
    <title>Bookings - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .popup {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            position: relative;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #333;
            text-decoration: none;
            cursor: pointer;
            z-index: 10000;
        }
        
        /* Right sidebar styles */
        .right-sidebar {
            width: 320px;
        }
        
        .stats-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stat-box {
            height: 100%;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 12px;
        }
        
        .stat-icon {
            position: relative;
        }
        
        /* Table styles */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            margin: 0 5px;
        }
        
        .accept-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .accept-btn:hover {
            background-color: #45a049;
        }
        
        .reject-btn {
            background-color: #f44336;
            color: white;
        }
        
        .reject-btn:hover {
            background-color: #da190b;
        }
        
        .filter-container {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .filter-btn {
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .filter-btn:hover {
            background: #0b7dda;
        }
        
        .no-bookings {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .no-bookings img {
            width: 25%;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="main-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="../Media/Icon/ToothTrackr/ToothTrackr.png" alt="ToothTrackr Logo">
            </div>

            <div class="user-profile">
                <div class="profile-image">
                    <img src="<?php echo $photopath; ?>" alt="Profile" class="profile-img">
                </div>
                <h3 class="profile-name"><?php echo substr($username, 0, 25); ?></h3>
                <p style="color: #777; margin: 0; font-size: 14px; text-align: center;">
                    <?php echo substr($useremail, 0, 30); ?>
                </p>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <img src="../Media/Icon/Blue/home.png" alt="Home" class="nav-icon">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="calendar/calendar.php" class="nav-item">
                    <img src="../Media/Icon/Blue/calendar.png" alt="Calendar" class="nav-icon">
                    <span class="nav-label">Calendar</span>
                </a>
                <a href="booking.php" class="nav-item active">
                    <img src="../Media/Icon/Blue/booking.png" alt="Booking" class="nav-icon">
                    <span class="nav-label">Booking</span>
                </a>
                <a href="appointment.php" class="nav-item">
                    <img src="../Media/Icon/Blue/appointment.png" alt="Appointment" class="nav-icon">
                    <span class="nav-label">Appointment</span>
                </a>
                <a href="patient.php" class="nav-item">
                    <img src="../Media/Icon/Blue/care.png" alt="Patient" class="nav-icon">
                    <span class="nav-label">Patient</span>
                </a>
                <a href="dentist-records.php" class="nav-item">
                    <img src="../Media/Icon/Blue/edit.png" alt="Records" class="nav-icon">
                    <span class="nav-label">Records</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <img src="../Media/Icon/Blue/settings.png" alt="Settings" class="nav-icon">
                    <span class="nav-label">Settings</span>
                </a>
            </div>

            <div class="log-out">
                <a href="logout.php" class="nav-item">
                    <img src="../Media/Icon/Blue/logout.png" alt="Log Out" class="nav-icon">
                    <span class="nav-label">Log Out</span>
                </a>
            </div>
        </div>

        <div class="content-area">
            <div class="content">
                <div class="main-section">
                    <div class="announcements-header">
                        <h3 class="announcements-title">Booking Manager</h3>
                        <div class="announcement-filters">
                            <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;">
                                <?php echo date('F j, Y'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="filter-container">
                        <form action="" method="post" class="filter-form">
                            <input type="date" name="appodate" id="date" class="filter-input">
                            <input type="submit" name="filter" value="Filter" class="filter-btn">
                        </form>
                    </div>
                    
                    <div class="table-container">
                        <?php if ($result->num_rows == 0): ?>
                            <div class="no-bookings">
                                <img src="../img/notfound.svg" width="25%">
                                <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">No pending bookings found!</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Procedure</th>
                                        <th>Date & Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): 
                                        $appoid = $row["appoid"];
                                        $procedure_name = $row["procedure_name"];
                                        $pname = $row["pname"];
                                        $pid = $row["pid"];
                                        $appodate = $row["appodate"];
                                        $appointment_time = $row["appointment_time"];
                                        $profile_pic = !empty($row["profile_pic"]) ? "../" . $row["profile_pic"] : "../Media/Icon/Blue/profile.png";
                                    ?>
                                        <tr id="row-<?php echo $appoid; ?>">
                                            <td>
                                                <div style="display: flex; align-items: center;">
                                                    <img src="<?php echo $profile_pic; ?>" alt="<?php echo $pname; ?>" class="profile-img-small" style="margin-right: 10px;">
                                                    <div>
                                                        <div><?php echo $pname; ?></div>
                                                        <small>ID: P-<?php echo $pid; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $procedure_name; ?></td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($appodate)); ?><br>
                                                <small><?php echo date('g:i A', strtotime($appointment_time)); ?></small>
                                            </td>
                                            <td>
                                                <a href="#" onclick="updateBooking(<?php echo $appoid; ?>, 'accept')" class="action-btn accept-btn">Accept</a>
                                                <a href="#" onclick="updateBooking(<?php echo $appoid; ?>, 'reject')" class="action-btn reject-btn">Reject</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right sidebar section -->
                <div class="right-sidebar">
                    <div class="stats-section">
                        <div class="stats-container">
                            <!-- First row -->
                            <a href="patient.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $patientrow->fetch_row()[0] ?? 0; ?></h1>
                                        <p class="stat-label">My Patients</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/care.png" alt="Patients Icon">
                                    </div>
                                </div>
                            </a>

                            <!-- Second row -->
                            <a href="booking.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $booking_count; ?></h1>
                                        <p class="stat-label">Bookings</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/booking.png" alt="Booking Icon">
                                        <?php if ($booking_count > 0): ?>
                                            <span class="notification-badge"><?php echo $booking_count; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>

                            <?php
// Get the count once and store it
$scheduleCount = $schedulerow->fetch_row()[0] ?? 0;
?>

<a href="appointment.php" class="stat-box-link">
    <div class="stat-box">
        <div class="stat-content">
            <h1 class="stat-number"><?php echo $scheduleCount; ?></h1>
            <p class="stat-label">Appointments</p>
        </div>
        <div class="stat-icon">
            <img src="../Media/Icon/Blue/appointment.png" alt="Appointment Icon">
            <?php if ($scheduleCount > 0): ?>
                <span class="notification-badge"><?php echo $scheduleCount; ?></span>
            <?php endif; ?>
        </div>
    </div>
</a>
                        </div>
                    </div>

                    <div class="calendar-section">
                        <!-- Dynamic Calendar -->
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <h3 class="calendar-month">
                                    <?php echo strtoupper(date('F', strtotime('this month'))); ?>
                                </h3>
                            </div>
                            <div class="calendar-grid">
                                <div class="calendar-day">S</div>
                                <div class="calendar-day">M</div>
                                <div class="calendar-day">T</div>
                                <div class="calendar-day">W</div>
                                <div class="calendar-day">T</div>
                                <div class="calendar-day">F</div>
                                <div class="calendar-day">S</div>

                                <?php
                                // Calculate the previous month's spillover days
                                $previousMonthDays = $firstDayOfMonth - 1;
                                $previousMonthLastDay = date('t', strtotime('last month'));
                                $startDay = $previousMonthLastDay - $previousMonthDays + 1;

                                // Display previous month's spillover days
                                for ($i = 0; $i < $previousMonthDays; $i++) {
                                    echo '<div class="calendar-date other-month">' . $startDay . '</div>';
                                    $startDay++;
                                }

                                // Display current month's days
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $class = ($day == $currentDay) ? 'calendar-date today' : 'calendar-date';
                                    echo '<div class="' . $class . '">' . $day . '</div>';
                                }

                                // Calculate and display next month's spillover days
                                $nextMonthDays = 42 - ($previousMonthDays + $daysInMonth);
                                for ($i = 1; $i <= $nextMonthDays; $i++) {
                                    echo '<div class="calendar-date other-month">' . $i . '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="upcoming-appointments">
                        <h3>Upcoming Appointments</h3>
                        <div class="appointments-content">
                            <?php
                            $upcomingAppointments = $database->query("
                                SELECT
                                    appointment.appoid,
                                    procedures.procedure_name,
                                    appointment.appodate,
                                    appointment.appointment_time,
                                    patient.pname as patient_name
                                FROM appointment
                                INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id
                                INNER JOIN patient ON appointment.pid = patient.pid
                                WHERE
                                    appointment.docid = '$userid'
                                    AND appointment.status = 'appointment'
                                    AND appointment.appodate >= '$today'
                                ORDER BY appointment.appodate ASC
                                LIMIT 3;
                            ");

                            if ($upcomingAppointments->num_rows > 0) {
                                while ($appointment = $upcomingAppointments->fetch_assoc()) {
                                    echo '<div class="appointment-item">
                                        <h4 class="appointment-type">' . htmlspecialchars($appointment['patient_name']) . '</h4>
                                        <p class="appointment-date">' . htmlspecialchars($appointment['procedure_name']) . '</p>
                                        <p class="appointment-date">' .
                                            htmlspecialchars(date('F j, Y', strtotime($appointment['appodate']))) .
                                            ' • ' .
                                            htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))) .
                                        '</p>
                                    </div>';
                                }
                            } else {
                                echo '<div class="no-appointments">
                                    <p>No upcoming appointments scheduled</p>
                                </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="overlay">
        <div class="popup">
            <center>
                <h2>Confirm Action</h2>
                <a class="close" href="#" onclick="closeModal()">&times;</a>
                <div class="content">
                    <p id="modalMessage">Are you sure you want to proceed?</p>
                </div>
                <div style="display: flex;justify-content: center;gap:10px;margin-top:20px;">
                    <button onclick="confirmAction()" class="action-btn accept-btn">Yes</button>
                    <button onclick="closeModal()" class="action-btn reject-btn">No</button>
                </div>
            </center>
        </div>
    </div>

    <script>
        let currentAppoid = null;
        let currentAction = null;

        function updateBooking(appoid, action) {
            currentAppoid = appoid;
            currentAction = action;
            document.getElementById("modalMessage").textContent = `Are you sure you want to ${action} this booking?`;
            document.getElementById("confirmationModal").style.display = "flex";
        }

        function confirmAction() {
            window.location.href = `booking.php?action=${currentAction}&id=${currentAppoid}`;
        }

        function closeModal() {
            document.getElementById("confirmationModal").style.display = "none";
            currentAppoid = null;
            currentAction = null;
        }
    </script>
</body>
</html>