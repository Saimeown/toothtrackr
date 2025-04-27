<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
        exit();
    }
} else {
    header("location: ../login.php");
    exit();
}

include("../connection.php");

// Get totals for right sidebar
$doctorrow = $database->query("SELECT * FROM doctor WHERE status='active'");
$patientrow = $database->query("SELECT * FROM patient WHERE status='active'");
$appointmentrow = $database->query("SELECT * FROM appointment WHERE status='appointment'");
$bookingrow = $database->query("SELECT * FROM appointment WHERE status='booking'");
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
    <title>Appointments - Toothtrackr</title>
    <link rel="icon" href="../Media/white-icon/white-ToothTrackr_Logo.png" type="image/png">
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .sub-table {
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
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
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
       
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .stat-box {
            height: 100%;
        }
        .right-sidebar {
            width: 400px;
        }
        
        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            text-align: center;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .modal-content h2 {
            color: #333;
            font-size: 1.4rem;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-body {
            padding: 10px 0;
            line-height: 1.6;
            text-align: left;
        }

        .modal-body p {
            margin: 12px 0;
            color: #555;
        }

        .modal-body b {
            color: #333;
            font-weight: 600;
            display: inline-block;
            min-width: 100px;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #45a049;
        }

        .btn-secondary {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-secondary:hover {
            background-color: #da190b;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
        }
        
        .delete-btn:hover {
            background-color: #da190b;
        }
        
        /* Search and filter styles */
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-container {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter-container-items {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn-filter {
            background-color: #2a7be4;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-filter:hover {
            background-color: #1a6bd4;
        }
        
        /* Table styles */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background-color: #f5f5f5;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .table tr:hover {
            background-color: #f9f9f9;
        }
        
        .cell-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        /* No results style */
        .no-results {
            text-align: center;
            padding: 40px;
            color: #777;
        }
        
        /* Calendar section styles */
        .calendar-section {
            margin-top: 20px;
        }
        
        .calendar-container {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .calendar-month {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        
        .calendar-day {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            color: #777;
            padding: 5px;
        }
        
        .calendar-date {
            text-align: center;
            padding: 8px 5px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .calendar-date.today {
            background-color: #2a7be4;
            color: white;
        }
        
        .calendar-date.other-month {
            color: #ccc;
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
                    <img src="../Media/Icon/SDMC Logo.png" alt="Profile" class="profile-img">
                </div>
                <h3 class="profile-name">Songco Dental and Medical Clinic</h3>
                <p style="color: #777; margin: 0; font-size: 14px; text-align: center;">
                    Administrator
                </p>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <img src="../Media/Icon/Blue/home.png" alt="Home" class="nav-icon">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="dentist.php" class="nav-item">
                    <img src="../Media/Icon/Blue/dentist.png" alt="Dentist" class="nav-icon">
                    <span class="nav-label">Dentist</span>
                </a>
                <a href="patient.php" class="nav-item">
                    <img src="../Media/Icon/Blue/care.png" alt="Patient" class="nav-icon">
                    <span class="nav-label">Patient</span>
                </a>
                <a href="records.php" class="nav-item">
                    <img src="../Media/Icon/Blue/edit.png" alt="Records" class="nav-icon">
                    <span class="nav-label">Patient Records</span>
                </a>
                <a href="calendar/calendar.php" class="nav-item">
                    <img src="../Media/Icon/Blue/calendar.png" alt="Calendar" class="nav-icon">
                    <span class="nav-label">Calendar</span>
                </a>
                <a href="booking.php" class="nav-item">
                    <img src="../Media/Icon/Blue/booking.png" alt="Booking" class="nav-icon">
                    <span class="nav-label">Booking</span>
                </a>
                <a href="appointment.php" class="nav-item active">
                    <img src="../Media/Icon/Blue/appointment.png" alt="Appointment" class="nav-icon">
                    <span class="nav-label">Appointment</span>
                </a>
                <a href="history.php" class="nav-item">
                    <img src="../Media/Icon/Blue/folder.png" alt="Archive" class="nav-icon">
                    <span class="nav-label">Archive</span>
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
                    <!-- header -->
                    <div class="announcements-header">
                        <h3 class="announcements-title">Manage Appointments</h3>
                        <a href="history.php" class="btn-primary-soft" style="padding: 10px 15px; text-decoration: none; border-radius: 10px; font-size: 12px; width: 150px; margin-right: 85px;">
                            Past Appointments
                        </a>
                    </div>

                    <!-- Date filter form -->
                    <div class="filter-container">
                        <form action="" method="post" style="display: flex; gap: 10px; align-items: center;">
                            <div style="flex-grow: 1;">
                                <input type="date" name="sheduledate" id="date" class="input-text filter-container-items" 
                                    style="margin: 0; width: 100%;" value="<?php echo isset($_POST['sheduledate']) ? $_POST['sheduledate'] : ''; ?>">
                            </div>
                            <div style="flex-grow: 1;">
                                <select name="docid" id="" class="input-text filter-container-items" style="width:100%; height: 37px; margin: 0;">
                                    <option value="" disabled selected hidden>Choose Dentist Name</option>
                                    <?php
                                    $list11 = $database->query("select * from doctor order by docname asc;");
                                    for ($y = 0; $y < $list11->num_rows; $y++) {
                                        $row00 = $list11->fetch_assoc();
                                        $sn = $row00["docname"];
                                        $id00 = $row00["docid"];
                                        $selected = (isset($_POST['docid']) && $_POST['docid'] == $id00) ? 'selected' : '';
                                        echo "<option value='$id00' $selected>$sn</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <input type="submit" name="filter" value="Filter" class="btn-primary-soft btn button-icon btn-filter">
                            </div>
                            <?php if (isset($_POST['filter'])): ?>
                                <div>
                                    <a href="appointment.php" class="btn-secondary" style="padding: 10px 15px; display: inline-block;">Clear</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php
                    if ($_POST) {
                        $sqlpt1 = "";
                        if (!empty($_POST["sheduledate"])) {
                            $sheduledate = $_POST["sheduledate"];
                            $sqlpt1 = " appointment.appodate='$sheduledate' ";
                        }

                        $sqlpt2 = "";
                        if (!empty($_POST["docid"])) {
                            $docid = $_POST["docid"];
                            $sqlpt2 = " appointment.docid=$docid ";
                        }

                        $sqlmain = "SELECT appointment.appoid, appointment.pid, appointment.appodate, appointment.event_name, 
                                   appointment.procedure_id, procedures.procedure_name, appointment.appointment_time, 
                                   appointment.docid, patient.pname, doctor.docname 
                            FROM appointment 
                            INNER JOIN patient ON appointment.pid = patient.pid 
                            INNER JOIN doctor ON appointment.docid = doctor.docid 
                            INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id 
                            WHERE appointment.status = 'appointment'";

                        $sqllist = array($sqlpt1, $sqlpt2);
                        $sqlkeywords = array(" where ", " and ");
                        $key2 = 0;
                        foreach ($sqllist as $key) {
                            if (!empty($key)) {
                                $sqlmain .= $sqlkeywords[$key2] . $key;
                                $key2++;
                            }
                        }
                    } else {
                        $sqlmain = "SELECT appointment.appoid, appointment.pid, appointment.appodate, appointment.event_name, 
                                   appointment.procedure_id, procedures.procedure_name, appointment.appointment_time, 
                                   appointment.docid, patient.pname, doctor.docname 
                            FROM appointment 
                            INNER JOIN patient ON appointment.pid = patient.pid 
                            INNER JOIN doctor ON appointment.docid = doctor.docid 
                            INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id 
                            WHERE appointment.status = 'appointment'";
                    }

                    $result = $database->query($sqlmain);
                    ?>

                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Dentist</th>
                                        <th>Event Name</th>
                                        <th>Procedure</th>
                                        <th>Appointment Date</th>
                                        <th>Appointment Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><div class="cell-text"><?php echo $row['pname']; ?></div></td>
                                            <td><div class="cell-text"><?php echo $row['docname']; ?></div></td>
                                            <td><div class="cell-text"><?php echo $row['event_name']; ?></div></td>
                                            <td><div class="cell-text"><?php echo $row['procedure_name']; ?></div></td>
                                            <td><div class="cell-text"><?php echo $row['appodate']; ?></div></td>
                                            <td><div class="cell-text"><?php echo $row['appointment_time']; ?></div></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="#" onclick="confirmCancel(<?php echo $row['appoid']; ?>, '<?php echo addslashes($row['pname']); ?>', '<?php echo addslashes($row['event_name']); ?>')" 
                                                       class="action-btn delete-btn">Cancel</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <img src="../img/notfound.svg" width="25%">
                            <p>We couldn't find any appointments matching your criteria.</p>
                            <a href="appointment.php" class="btn-primary-soft" style="padding: 10px 15px; text-decoration: none; display: inline-block; margin-top: 15px;">
                                Show All Appointments
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add right sidebar section -->
                <div class="right-sidebar">
                    <div class="stats-section">
                        <div class="stats-container">
                            <!-- First row -->
                            <a href="dentist.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $doctorrow->num_rows; ?></h1>
                                        <p class="stat-label">Dentists</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/dentist.png" alt="Dentist Icon">
                                    </div>
                                </div>
                            </a>

                            <!-- Second row -->
                            <a href="patient.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $patientrow->num_rows; ?></h1>
                                        <p class="stat-label">Patients</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/care.png" alt="Patient Icon">
                                    </div>
                                </div>
                            </a>

                            <a href="booking.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $bookingrow->num_rows; ?></h1>
                                        <p class="stat-label">Bookings</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/booking.png" alt="Booking Icon">
                                        <?php if ($bookingrow->num_rows > 0): ?>
                                            <span class="notification-badge"><?php echo $bookingrow->num_rows; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>

                            <a href="appointment.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $appointmentrow->num_rows; ?></h1>
                                        <p class="stat-label">Appointments</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/appointment.png" alt="Appointment Icon">
                                        <?php if ($appointmentrow->num_rows > 0): ?>
                                            <span class="notification-badge"><?php echo $appointmentrow->num_rows; ?></span>
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
                                    <?php
                                    // Get current month name dynamically
                                    echo strtoupper(date('F', strtotime('this month')));
                                    ?>
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
                                // Calculate the first day of the month and number of days
                                $firstDayOfMonth = date('N', strtotime("first day of this month"));
                                $daysInMonth = date('t');
                                $currentDay = date('j');

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
                                $nextMonthDays = 42 - ($previousMonthDays + $daysInMonth); // 42 = 6 rows * 7 days
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
                                    patient.pname as patient_name,
                                    doctor.docname as doctor_name
                                FROM appointment
                                INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id
                                INNER JOIN patient ON appointment.pid = patient.pid
                                INNER JOIN doctor ON appointment.docid = doctor.docid
                                WHERE
                                    appointment.status = 'appointment'
                                    AND appointment.appodate >= '$today'
                                ORDER BY appointment.appodate ASC
                                LIMIT 3;
                            ");

                            if ($upcomingAppointments->num_rows > 0) {
                                while ($appointment = $upcomingAppointments->fetch_assoc()) {
                                    echo '<div class="appointment-item">
                                        <h4 class="appointment-type">' . htmlspecialchars($appointment['patient_name']) . '</h4>
                                        <p class="appointment-dentist">With Dr. ' . htmlspecialchars($appointment['doctor_name']) . '</p>
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

    <!-- Enhanced Cancellation Modal -->
    <div id="cancelModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Cancel Appointment</h2>
            <div class="modal-body">
                <form id="cancelForm">
                    <input type="hidden" name="appoid" id="cancelAppoid">
                    <input type="hidden" name="source" value="admin">
                    <p>Are you sure you want to cancel this appointment?</p>
                    <div class="form-group">
                        <label for="cancelReason">Reason for cancellation:</label>
                        <select class="form-control" name="cancel_reason" id="cancelReason" required>
                            <option value="">-- Select a reason --</option>
                            <option value="Dentist Unavailable">Dentist Unavailable</option>
                            <option value="Clinic Closed">Clinic Closed</option>
                            <option value="Emergency Situation">Emergency Situation</option>
                            <option value="Patient Request">Patient Request</option>
                            <option value="Other">Other (please specify)</option>
                        </select>
                    </div>
                    <div class="form-group" id="otherReasonGroup" style="display:none;">
                        <label for="otherReason">Please specify:</label>
                        <input type="text" class="form-control" name="other_reason" id="otherReason">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn-secondary">Cancel</button>
                <button onclick="submitCancelForm()" class="btn-primary">Confirm Cancellation</button>
            </div>
        </div>
    </div>

    <script>
        let currentAppoid = null;
        let currentPatientName = null;
        let currentEventName = null;

        function confirmCancel(appoid, patientName, eventName) {
            currentAppoid = appoid;
            currentPatientName = patientName;
            currentEventName = eventName;
            
            // Reset form
            document.getElementById("cancelForm").reset();
            document.getElementById("otherReasonGroup").style.display = "none";
            
            // Set the appointment ID
            document.getElementById("cancelAppoid").value = appoid;
            
            // Show modal
            document.getElementById("cancelModal").style.display = "flex";
        }

        // Handle reason dropdown change
        document.getElementById("cancelReason").addEventListener("change", function() {
            const otherContainer = document.getElementById("otherReasonGroup");
            if (this.value === "Other") {
                otherContainer.style.display = "block";
            } else {
                otherContainer.style.display = "none";
            }
        });

        function submitCancelForm() {
            const form = document.getElementById("cancelForm");
            const reasonSelect = document.getElementById("cancelReason");
            
            if (!reasonSelect.value) {
                alert("Please select a cancellation reason");
                return;
            }
            
            // Submit form via AJAX
            const formData = new FormData(form);
            
            fetch("delete-appointment.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.message);
                    window.location.reload(); // Refresh the page
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while processing your request.");
            });
        }

        function closeModal() {
            document.getElementById("cancelModal").style.display = "none";
            currentAppoid = null;
            currentPatientName = null;
            currentEventName = null;
        }
    </script>
</body>
</html>