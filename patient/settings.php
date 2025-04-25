<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);  // Report all errors except notices and warnings
ini_set('display_errors', 0);  // Disable displaying errors

// Optionally, log errors to a file (you can keep track of them without displaying to the user)
ini_set('log_errors', 1);
ini_set('error_log', 'path/to/error.log');
session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
        header("location: login.php");
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: login.php");
}

// Import database connection
include("../connection.php");

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Verify current password
    $sql = "SELECT * FROM patient WHERE pemail='$useremail'";
    $result = $database->query($sql);
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user["ppassword"])) {
        if ($new_password === $confirm_password) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE patient SET ppassword='$hashed_password' WHERE pemail='$useremail'";
            $database->query($update_sql);
            
            $_SESSION["password_change_success"] = "Password changed successfully!";
            header("Location: settings.php");
            exit();
        } else {
            $_SESSION["password_change_error"] = "New passwords do not match!";
        }
    } else {
        $_SESSION["password_change_error"] = "Current password is incorrect!";
    }
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $userid = $_POST["user_id"];
    $fname = $_POST["fname"];
    $lname = $_POST["lname"];
    $email = $_POST["email"];
    $tele = $_POST["Tele"];
    $address = $_POST["address"];
    
    $fullname = $fname . " " . $lname;
    
    // Update query
    $update_query = "UPDATE patient SET pname=?, pemail=?, ptel=?, paddress=? WHERE pid=?";
    $stmt = $database->prepare($update_query);
    $stmt->bind_param("ssssi", $fullname, $email, $tele, $address, $userid);
    $stmt->execute();
    
    // Update session email if changed
    if ($email != $useremail) {
        $_SESSION["user"] = $email;
    }
    
    $_SESSION["profile_update_success"] = "Profile updated successfully!";
    header("Location: settings.php");
    exit();
}

// Handle medical history update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_medical_history"])) {
    $email = $_POST["email"];
    $good_health = $_POST["good_health"];
    $under_treatment = $_POST["under_treatment"];
    $condition_treated = $_POST["condition_treated"];
    $serious_illness = $_POST["serious_illness"];
    $hospitalized = $_POST["hospitalized"];
    $medication = $_POST["medication"];
    $medication_specify = $_POST["medication_specify"];
    $tobacco = $_POST["tobacco"];
    $drugs = $_POST["drugs"];
    $allergies = isset($_POST["allergies"]) ? implode(",", $_POST["allergies"]) : "";
    $blood_pressure = $_POST["blood_pressure"];
    $bleeding_time = $_POST["bleeding_time"];
    $health_conditions = isset($_POST["health_conditions"]) ? implode(",", $_POST["health_conditions"]) : "";
    
    // Check if medical history exists
    $check_sql = "SELECT * FROM medical_history WHERE email='$email'";
    $check_result = $database->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE medical_history SET 
            good_health='$good_health', 
            under_treatment='$under_treatment', 
            condition_treated='$condition_treated', 
            serious_illness='$serious_illness', 
            hospitalized='$hospitalized', 
            medication='$medication', 
            medication_specify='$medication_specify', 
            tobacco='$tobacco', 
            drugs='$drugs', 
            allergies='$allergies', 
            blood_pressure='$blood_pressure', 
            bleeding_time='$bleeding_time', 
            health_conditions='$health_conditions' 
            WHERE email='$email'";
    } else {
        // Insert new record
        $update_sql = "INSERT INTO medical_history (
            email, good_health, under_treatment, condition_treated, 
            serious_illness, hospitalized, medication, medication_specify, 
            tobacco, drugs, allergies, blood_pressure, bleeding_time, health_conditions
        ) VALUES (
            '$email', '$good_health', '$under_treatment', '$condition_treated', 
            '$serious_illness', '$hospitalized', '$medication', '$medication_specify', 
            '$tobacco', '$drugs', '$allergies', '$blood_pressure', '$bleeding_time', '$health_conditions'
        )";
    }
    
    $database->query($update_sql);
    $_SESSION["medical_history_success"] = "Medical history updated successfully!";
    header("Location: settings.php");
    exit();
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
    <title>Settings - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .dashbord-tables {
            animation: transitionIn-Y-over 0.5s;
        }

        .filter-container {
            animation: transitionIn-X 0.5s;
        }

        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
        }
        
        /* Settings Cards */
        .settings-card {
            background-color: #f2f7fb;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .settings-card:hover {
            background-color: #e1eaf4;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .settings-icon {
            width: 40px;
            height: 40px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e1eaf4;
            border-radius: 50%;
        }
        
        .settings-icon img {
            width: 20px;
            height: 20px;
        }
        
        .settings-info {
            flex: 1;
        }
        
        .settings-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: #333;
        }
        
        .settings-description {
            font-size: 13px;
            color: #666;
            margin: 3px 0 0;
        }
        
        .settings-arrow {
            color: #aaa;
            font-size: 20px;
        }
        
        .danger-text {
            color: #e74c3c;
        }
        
        /* Popup styles */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .popup {
            max-width: 600px;
            width: 90%;
            border-radius: 12px;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 30px;
            overflow: hidden;
            animation: fadeIn 0.3s;
        }
        
        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .popup-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .close {
            font-size: 24px;
            color: #999;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #e74c3c;
        }
        
        .popup-content {
            padding: 10px 0;
        }
        
        .popup-form .label-td {
            padding: 8px 0;
        }
        
        .popup-form .form-label {
            font-weight: 500;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }
        
        .popup-form .input-text {
            width: 100%;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 15px;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        
        .popup-form .input-text:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 5px rgba(46, 204, 113, 0.3);
        }
        
        .radio-group, .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 5px 0 15px;
        }
        
        .radio-option, .checkbox-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-row {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-primary {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #27ae60;
        }
        
        .btn-outline {
            background-color: white;
            color: #555;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background-color: #f5f5f5;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            margin-bottom: 20px;
        }
        
        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <?php
    date_default_timezone_set('Asia/Singapore');

    if (isset($_SESSION["user"])) {
        if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'p') {
            header("location: login.php");
        } else {
            $useremail = $_SESSION["user"];
        }
    } else {
        header("location: login.php");
    }

    //import database
    include("../connection.php");
    $userrow = $database->query("select * from patient where pemail='$useremail'");
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["pid"];
    $username = $userfetch["pname"];

    // Get totals for right sidebar
    $doctorrow = $database->query("select * from doctor where status='active';");
    $appointmentrow = $database->query("select * from appointment where status='booking' AND pid='$userid';");
    $schedulerow = $database->query("select * from appointment where status='appointment' AND pid='$userid';");

    // Pagination
    $results_per_page = 10;

    // Determine which page we're on
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    } else {
        $page = 1;
    }

    // Calculate the starting limit for SQL
    $start_from = ($page - 1) * $results_per_page;

    // Search functionality
    $search = "";

    // Main query with current database structure
    $sqlmain = "SELECT 
                appointment.appoid, 
                procedures.procedure_name, 
                doctor.docname, 
                appointment.appodate, 
                appointment.appointment_time,
                appointment.status,
                doctor.photo
            FROM appointment
            INNER JOIN doctor ON appointment.docid = doctor.docid
            INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id
            WHERE appointment.pid = $userid AND appointment.status IN ('appointment', 'completed')";

    if ($_POST) {
        if (!empty($_POST["appodate"])) {
            $appodate = $_POST["appodate"];
            $sqlmain .= " AND appointment.appodate='$appodate'";
        }
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $sqlmain .= " AND (doctor.docname LIKE '%$search%' OR procedures.procedure_name LIKE '%$search%')";
    }

    $sqlmain .= " ORDER BY appointment.appodate ASC LIMIT $start_from, $results_per_page";
    $result = $database->query($sqlmain);

    // Count query for pagination
    $count_query = str_replace("LIMIT $start_from, $results_per_page", "", $sqlmain);
    $count_query = "SELECT COUNT(*) as total FROM (" . $count_query . ") as count_table";
    $count_result = $database->query($count_query);
    $count_row = $count_result->fetch_assoc();
    $total_pages = ceil($count_row['total'] / $results_per_page);

    // Calendar variables
    $today = date('Y-m-d');
    $currentMonth = date('F');
    $currentYear = date('Y');
    $daysInMonth = date('t');
    $firstDayOfMonth = date('N', strtotime("$currentYear-" . date('m') . "-01"));
    $currentDay = date('j');
    
    // Status message handling
    $statusMessage = '';
    $messageClass = '';

    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'cancel_success') {
            $statusMessage = "Appointment canceled successfully.";
            $messageClass = "success-message";
        } elseif ($_GET['status'] == 'cancel_error') {
            $statusMessage = "Failed to cancel the appointment. Please try again.";
            $messageClass = "error-message";
        }
    }
    ?>
    <div class="nav-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="../Media/Icon/ToothTrackr/ToothTrackr.png" alt="ToothTrackr Logo">
            </div>

            <div class="user-profile">
                <div class="profile-image">
                    <?php
                    $profile_pic = isset($userfetch['profile_pic']) ? $userfetch['profile_pic'] : '../Media/Icon/Blue/profile.png';
                    ?>
                    <img src="../<?php echo $profile_pic; ?>" alt="Profile" class="profile-img">
                </div>
                <h3 class="profile-name"><?php echo substr($username, 0, 25) ?></h3>
                <p style="color: #777; margin: 0; font-size: 14px; text-align: center;">
                    <?php echo substr($useremail, 0, 30) ?>
                </p>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <img src="../Media/Icon/Blue/home.png" alt="Home" class="nav-icon">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <img src="../Media/Icon/Blue/profile.png" alt="Profile" class="nav-icon">
                    <span class="nav-label">Profile</span>
                </a>
                <a href="dentist.php" class="nav-item">
                    <img src="../Media/Icon/Blue/dentist.png" alt="Dentist" class="nav-icon">
                    <span class="nav-label">Dentist</span>
                </a>
                <a href="calendar/calendar.php" class="nav-item">
                    <img src="../Media/Icon/Blue/calendar.png" alt="Calendar" class="nav-icon">
                    <span class="nav-label">Calendar</span>
                </a>
                <a href="my_booking.php" class="nav-item">
                    <img src="../Media/Icon/Blue/booking.png" alt="Bookings" class="nav-icon">
                    <span class="nav-label">My Booking</span>
                </a>
                <a href="my_appointment.php" class="nav-item">
                    <img src="../Media/Icon/Blue/appointment.png" alt="Appointments" class="nav-icon">
                    <span class="nav-label">My Appointment</span>
                </a>
                <a href="settings.php" class="nav-item active">
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
                    <!-- search bar -->
                    <div class="search-container">
                        <form action="" method="GET" style="display: flex; width: 100%; position: relative;">
                            <input type="search" name="search" id="searchInput" class="search-input" 
                                placeholder="Search settings"
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <?php if (isset($_GET['search']) && $_GET['search'] != ""): ?>
                                <button type="button" class="clear-btn" onclick="clearSearch()">×</button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Display success/error messages -->
                    <?php if (isset($_SESSION["profile_update_success"])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION["profile_update_success"]; unset($_SESSION["profile_update_success"]); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION["password_change_success"])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION["password_change_success"]; unset($_SESSION["password_change_success"]); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION["password_change_error"])): ?>
                        <div class="alert alert-error">
                            <?php echo $_SESSION["password_change_error"]; unset($_SESSION["password_change_error"]); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION["medical_history_success"])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION["medical_history_success"]; unset($_SESSION["medical_history_success"]); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Settings Cards -->
                    <div id="settings-container">
                        <!-- Personal Details Card -->
                        <a href="?action=edit_profile&id=<?php echo $userid ?>" class="settings-card">
                            <div class="settings-icon">
                                <img src="../Media/Icon/Blue/profile.png" alt="Personal Details">
                            </div>
                            <div class="settings-info">
                                <h3 class="settings-title">Personal Details</h3>
                                <p class="settings-description">Edit your personal details</p>
                            </div>
                            <div class="settings-arrow">
                                <span>›</span>
                            </div>
                        </a>
                        
                        <!-- Password Card -->
                        <a href="?action=change_password&id=<?php echo $userid ?>" class="settings-card">
                            <div class="settings-icon">
                                <img src="../Media/Icon/Blue/lock.png" alt="Password">
                            </div>
                            <div class="settings-info">
                                <h3 class="settings-title">Password</h3>
                                <p class="settings-description">Change your password</p>
                            </div>
                            <div class="settings-arrow">
                                <span>›</span>
                            </div>
                        </a>
                        
                        <!-- Medical History Card -->
                        <a href="?action=edit_medical_history&id=<?php echo $userid ?>" class="settings-card">
                            <div class="settings-icon">
                                <img src="../Media/Icon/Blue/edit.png" alt="Medical History">
                            </div>
                            <div class="settings-info">
                                <h3 class="settings-title">Medical History</h3>
                                <p class="settings-description">Edit your medical history details</p>
                            </div>
                            <div class="settings-arrow">
                                <span>›</span>
                            </div>
                        </a>
                        
                        <!-- Deactivate Account Card -->
                        <a href="?action=deactivate_account&id=<?php echo $userid ?>" class="settings-card">
                            <div class="settings-icon">
                                <img src="../Media/Icon/Blue/x.png" alt="Deactivate">
                            </div>
                            <div class="settings-info">
                                <h3 class="settings-title danger-text">Deactivate Account</h3>
                                <p class="settings-description">This will deactivate your account</p>
                            </div>
                            <div class="settings-arrow">
                                <span>›</span>
                            </div>
                        </a>
                    </div>
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
                            <a href="my_booking.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $appointmentrow->num_rows ?></h1>
                                        <p class="stat-label">My Bookings</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/booking.png" alt="Booking Icon">
                                    </div>
                                </div>
                            </a>

                            <a href="my_appointment.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php
                                        $appointmentCount = $schedulerow->num_rows;
                                        echo $appointmentCount;
                                        ?></h1>
                                        <p class="stat-label">My Appointments</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../Media/Icon/Blue/appointment.png" alt="Appointment Icon">
                                        <?php if ($appointmentCount > 0): ?>
                                            <span class="notification-badge"><?php echo $appointmentCount; ?></span>
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
                                    appointment.appointment_time
                                FROM appointment
                                INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id
                                WHERE
                                    appointment.pid = '$userid'
                                    AND appointment.status = 'appointment'
                                    AND appointment.appodate >= '$today'
                                ORDER BY appointment.appodate ASC
                                LIMIT 3;
                            ");

                            if ($upcomingAppointments->num_rows > 0) {
                                while ($appointment = $upcomingAppointments->fetch_assoc()) {
                                    echo '<div class="appointment-item">
                                        <h4 class="appointment-type">' . htmlspecialchars($appointment['procedure_name']) . '</h4>
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
                                    <a href="calendar/calendar.php" class="schedule-btn">Schedule an appointment</a>
                                </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    if ($_GET) {
        $id = $_GET["id"];
        $action = $_GET["action"];
        
        if ($action == 'deactivate_account') {
            $sqlmain = "select * from patient where pid='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            $name = $row["pname"];
            
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <div class="popup-header">
                        <h2 class="popup-title">Deactivate Account</h2>
                        <a class="close" href="settings.php">&times;</a>
                    </div>
                    <div class="popup-content">
                        <p>Are you sure you want to deactivate your account?</p>
                        <p>This will remove <strong>' . substr($name, 0, 40) . '</strong> from the system.</p>
                        
                        <div class="btn-row">
                            <a href="settings.php" class="btn-outline">Cancel</a>
                            <a href="delete-account.php?id=' . $id . '" class="btn-danger">Deactivate</a>
                        </div>
                    </div>
                </div>
            </div>';
        } elseif ($action == 'edit_medical_history') {
            // Fetching user details
            $sqlmain = "SELECT * FROM patient WHERE pid='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            $email = $row["pemail"];
        
            // Fetching medical history details
            $sqlMedical = "SELECT * FROM medical_history WHERE email='$email'";
            $resultMedical = $database->query($sqlMedical);
            $rowMedical = $resultMedical->fetch_assoc();
        
            // Set default values if no medical history exists
            $good_health = $rowMedical['good_health'] ?? 'Yes';
            $under_treatment = $rowMedical['under_treatment'] ?? 'No';
            $condition_treated = $rowMedical['condition_treated'] ?? '';
            $serious_illness = $rowMedical['serious_illness'] ?? 'No';
            $hospitalized = $rowMedical['hospitalized'] ?? 'No';
            $medication = $rowMedical['medication'] ?? 'No';
            $medication_specify = $rowMedical['medication_specify'] ?? '';
            $tobacco = $rowMedical['tobacco'] ?? 'No';
            $drugs = $rowMedical['drugs'] ?? 'No';
            $allergies = isset($rowMedical['allergies']) ? explode(',', $rowMedical['allergies']) : [];
            $blood_pressure = $rowMedical['blood_pressure'] ?? 'No';
            $bleeding_time = $rowMedical['bleeding_time'] ?? 'No';
            $health_conditions = isset($rowMedical['health_conditions']) ? explode(',', $rowMedical['health_conditions']) : [];
        
            echo '
            <div id="popup1" class="overlay">
                <div class="popup" style="max-height: 80vh; display: flex; flex-direction: column;">
                    <div class="popup-header" style="flex-shrink: 0;">
                        <h2 class="popup-title">Edit Medical History</h2>
                        <a class="close" href="settings.php">&times;</a>
                    </div>
                    <div class="popup-content" style="overflow-y: auto; flex-grow: 1; padding: 0 20px;">
                        <form action="settings.php" method="POST" class="popup-form">
                            <input type="hidden" name="update_medical_history" value="1">
                            <input type="hidden" name="email" value="' . $email . '">
                            
                            <div class="form-section">
                                <div class="form-section-title">General Health</div>
                                <div class="label-td">
                                    <label for="good_health" class="form-label">Are you in good health?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="good_health" value="Yes" ' . ($good_health == 'Yes' ? 'checked' : '') . ' required>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="good_health" value="No" ' . ($good_health == 'No' ? 'checked' : '') . ' required>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="under_treatment" class="form-label">Are you under any medical treatment?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="under_treatment" value="Yes" ' . ($under_treatment == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="under_treatment" value="No" ' . ($under_treatment == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="condition_treated" class="form-label">If yes, specify condition treated:</label>
                                    <input type="text" name="condition_treated" class="input-text" placeholder="Condition treated" value="' . htmlspecialchars($condition_treated) . '">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Medical History</div>
                                <div class="label-td">
                                    <label for="serious_illness" class="form-label">Have you had any serious illness in the past?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="serious_illness" value="Yes" ' . ($serious_illness == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="serious_illness" value="No" ' . ($serious_illness == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="hospitalized" class="form-label">Have you ever been hospitalized?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="hospitalized" value="Yes" ' . ($hospitalized == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="hospitalized" value="No" ' . ($hospitalized == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Current Medications</div>
                                <div class="label-td">
                                    <label for="medication" class="form-label">Are you currently on any medication?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="medication" value="Yes" ' . ($medication == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="medication" value="No" ' . ($medication == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="medication_specify" class="form-label">If yes, please specify:</label>
                                    <input type="text" name="medication_specify" class="input-text" placeholder="Medication details" value="' . htmlspecialchars($medication_specify) . '">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Habits</div>
                                <div class="label-td">
                                    <label for="tobacco" class="form-label">Do you use tobacco?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="tobacco" value="Yes" ' . ($tobacco == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="tobacco" value="No" ' . ($tobacco == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="drugs" class="form-label">Do you use recreational drugs?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="drugs" value="Yes" ' . ($drugs == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="drugs" value="No" ' . ($drugs == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Allergies & Conditions</div>
                                <div class="label-td">
                                    <label for="allergies" class="form-label">Do you have any allergies?</label>
                                    <div class="checkbox-group">
                                        <div class="checkbox-option">
                                            <input type="checkbox" name="allergies[]" value="Pollen" ' . (in_array('Pollen', $allergies) ? 'checked' : '') . '>
                                            <label>Pollen</label>
                                        </div>
                                        <div class="checkbox-option">
                                            <input type="checkbox" name="allergies[]" value="Penicillin" ' . (in_array('Penicillin', $allergies) ? 'checked' : '') . '>
                                            <label>Penicillin</label>
                                        </div>
                                        <div class="checkbox-option">
                                            <input type="checkbox" name="allergies[]" value="Latex" ' . (in_array('Latex', $allergies) ? 'checked' : '') . '>
                                            <label>Latex</label>
                                        </div>
                                        <div class="checkbox-option">
                                            <input type="checkbox" name="allergies[]" value="Other" ' . (in_array('Other', $allergies) ? 'checked' : '') . '>
                                            <label>Other</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="blood_pressure" class="form-label">Do you have high blood pressure?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="blood_pressure" value="Yes" ' . ($blood_pressure == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="blood_pressure" value="No" ' . ($blood_pressure == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="bleeding_time" class="form-label">Do you have a prolonged bleeding time?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" name="bleeding_time" value="Yes" ' . ($bleeding_time == 'Yes' ? 'checked' : '') . '>
                                            <label>Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" name="bleeding_time" value="No" ' . ($bleeding_time == 'No' ? 'checked' : '') . '>
                                            <label>No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="label-td">
                                    <label for="health_conditions" class="form-label">Any other health conditions?</label>
                                    <input type="text" name="health_conditions" class="input-text" placeholder="Other health conditions" value="' . htmlspecialchars(implode(', ', $health_conditions)) . '">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="btn-row" style="flex-shrink: 0; padding: 20px; border-top: 1px solid #eee;">
                        <a href="settings.php" class="btn-outline">Cancel</a>
                        <button type="submit" form="medical-history-form" class="btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>';
        } elseif ($action == 'change_password') {
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <div class="popup-header">
                        <h2 class="popup-title">Change Password</h2>
                        <a class="close" href="settings.php">&times;</a>
                    </div>
                    <div class="popup-content">
                        <form action="settings.php" method="POST" class="popup-form">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="label-td">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="input-text" placeholder="Enter current password" required>
                            </div>
                            
                            <div class="label-td">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" name="new_password" class="input-text" placeholder="Enter new password" required>
                            </div>
                            
                            <div class="label-td">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="input-text" placeholder="Confirm new password" required>
                            </div>
                            
                            <div class="btn-row">
                                <a href="settings.php" class="btn-outline">Cancel</a>
                                <button type="submit" class="btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';
        } elseif ($action == 'edit_profile') {
            // Fetching user details
            $sqlmain = "SELECT * FROM patient WHERE pid='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            $name = $row["pname"];
            $email = $row["pemail"];
            $address = $row["paddress"];
            $tele = $row['ptel'];

            // Split the name into first and last name
            $name_parts = explode(' ', $name, 2);
            $fname = $name_parts[0] ?? '';
            $lname = $name_parts[1] ?? '';

            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <div class="popup-header">
                        <h2 class="popup-title">Edit Personal Details</h2>
                        <a class="close" href="settings.php">&times;</a>
                    </div>
                    <div class="popup-content">
                        <form action="settings.php" method="POST" class="popup-form">
                            <input type="hidden" name="update_profile" value="1">
                            <input type="hidden" name="user_id" value="' . $id . '">
                            
                            <div class="label-td">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" class="input-text" placeholder="Email Address" value="' . htmlspecialchars($email) . '" required>
                            </div>
                            
                            <div class="label-td">
                                <label for="fname" class="form-label">First Name</label>
                                <input type="text" name="fname" class="input-text" placeholder="First Name" value="' . htmlspecialchars($fname) . '" required>
                            </div>
                            
                            <div class="label-td">
                                <label for="lname" class="form-label">Last Name</label>
                                <input type="text" name="lname" class="input-text" placeholder="Last Name" value="' . htmlspecialchars($lname) . '" required>
                            </div>
                            
                            <div class="label-td">
                                <label for="Tele" class="form-label">Telephone</label>
                                <input type="tel" name="Tele" class="input-text" placeholder="Telephone Number" value="' . htmlspecialchars($tele) . '" required>
                            </div>
                            
                            <div class="label-td">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" name="address" class="input-text" placeholder="Address" value="' . htmlspecialchars($address) . '" required>
                            </div>
                            
                            <div class="btn-row">
                                <a href="settings.php" class="btn-outline">Cancel</a>
                                <button type="submit" class="btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';
        }
    }
    ?>
    <script>
        // Function to clear search and redirect
        function clearSearch() {
            window.location.href = 'settings.php';
        }

        // Search functionality
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const settingsCards = document.querySelectorAll('.settings-card');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    settingsCards.forEach(card => {
                        const title = card.querySelector('.settings-title').textContent.toLowerCase();
                        const description = card.querySelector('.settings-description').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || description.includes(searchTerm)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>