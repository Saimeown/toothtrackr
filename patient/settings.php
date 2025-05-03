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
    <link rel="stylesheet" href="../css/settings.css">
    <link rel="stylesheet" href="../css/table.css">
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

    // Get notification count
    $unreadCount = $database->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = '$userid' AND user_type = 'p' AND is_read = 0");
    $unreadCount = $unreadCount->fetch_assoc()['count'];

    // Get notifications
    $notifications = $database->query("SELECT * FROM notifications WHERE user_id = '$userid' AND user_type = 'p' ORDER BY created_at DESC");


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
                            <!-- Notification Box -->
                            <div class="stat-box notification-container" id="notificationContainer">
                                <div class="stat-content">
                                    <h1 class="stat-number"><?php echo $unreadCount; ?></h1>
                                    <p class="stat-label">Notifications</p>
                                </div>
                                <div class="stat-icon">
                                    <img src="../Media/Icon/Blue/folder.png" alt="Notifications Icon">
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="notification-badge"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="notification-dropdown" id="notificationDropdown">
                                    <div class="notification-header">
                                        <span>Notifications</span>
                                        <span class="mark-all-read" onclick="markAllAsRead()">Mark all as read</span>
                                    </div>
                                    
                                    <?php if ($notifications->num_rows > 0): ?>
                                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                                 onclick="markAsRead(<?php echo $notification['id']; ?>, this)">
                                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                <div><?php echo htmlspecialchars($notification['message']); ?></div>
                                                <div class="notification-time">
                                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="no-notifications">No notifications</div>
                                    <?php endif; ?>
                                </div>
                            </div>

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
                        
                        <div class="action-buttons-right">
                            <a href="settings.php" class="action-btn cancel-btn">Cancel</a>
                            <a href="delete-account.php?id=' . $id . '" class="action-btn remove-btn">Deactivate</a>
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
    $rowMedical = $resultMedical->num_rows > 0 ? $resultMedical->fetch_assoc() : [];
    
    // Set default values if no medical history exists
    $good_health = $rowMedical['good_health'] ?? '';
    $under_treatment = $rowMedical['under_treatment'] ?? '';
    $condition_treated = $rowMedical['condition_treated'] ?? '';
    $serious_illness = $rowMedical['serious_illness'] ?? '';
    $hospitalized = $rowMedical['hospitalized'] ?? '';
    $medication = $rowMedical['medication'] ?? '';
    $medication_specify = $rowMedical['medication_specify'] ?? '';
    $tobacco = $rowMedical['tobacco'] ?? '';
    $drugs = $rowMedical['drugs'] ?? '';
    $allergies = isset($rowMedical['allergies']) ? explode(',', $rowMedical['allergies']) : [];
    $blood_pressure = $rowMedical['blood_pressure'] ?? '';
    $bleeding_time = $rowMedical['bleeding_time'] ?? '';
    $health_conditions = isset($rowMedical['health_conditions']) ? explode(',', $rowMedical['health_conditions']) : [];
    
    echo '
    <div id="popup1" class="overlay">
        <div class="popup" style="max-height: 80vh; display: flex; flex-direction: column;">
            <div class="popup-header" style="flex-shrink: 0;">
                <h2 class="popup-title">Medical History Form</h2>
                <a class="close" href="settings.php">&times;</a>
            </div>
            <div class="popup-content" style="overflow-y: auto; flex-grow: 1; padding: 0 20px;">
                <form action="settings.php" method="POST" class="popup-form" id="medical-history-form">
                    <input type="hidden" name="update_medical_history" value="1">
                    <input type="hidden" name="email" value="' . $email . '">
                    
                    <div class="form-section">
                        <div class="form-section-title">1. Are you in good health?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="good_health" value="Yes" ' . ($good_health == 'Yes' ? 'checked' : '') . ' id="good_health_yes">
                                <label for="good_health_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="good_health" value="No" ' . ($good_health == 'No' ? 'checked' : '') . ' id="good_health_no">
                                <label for="good_health_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">2. Are you under medical treatment now?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="under_treatment" value="Yes" ' . ($under_treatment == 'Yes' ? 'checked' : '') . ' id="under_treatment_yes">
                                <label for="under_treatment_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="under_treatment" value="No" ' . ($under_treatment == 'No' ? 'checked' : '') . ' id="under_treatment_no">
                                <label for="under_treatment_no">No</label>
                            </div>
                        </div>
                        <div class="label-td" id="condition_treated_field" style="margin-top: 10px; ' . ($under_treatment != 'Yes' ? 'display: none;' : '') . '">
                            <label for="condition_treated" class="form-label">If yes, what condition is being treated?</label>
                            <input type="text" name="condition_treated" class="input-text" placeholder="Condition being treated" value="' . htmlspecialchars($condition_treated) . '">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">3. Have you ever had a serious illness/surgical operation?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="serious_illness" value="Yes" ' . ($serious_illness == 'Yes' ? 'checked' : '') . ' id="serious_illness_yes">
                                <label for="serious_illness_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="serious_illness" value="No" ' . ($serious_illness == 'No' ? 'checked' : '') . ' id="serious_illness_no">
                                <label for="serious_illness_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">4. Have you ever been hospitalized?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="hospitalized" value="Yes" ' . ($hospitalized == 'Yes' ? 'checked' : '') . ' id="hospitalized_yes">
                                <label for="hospitalized_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="hospitalized" value="No" ' . ($hospitalized == 'No' ? 'checked' : '') . ' id="hospitalized_no">
                                <label for="hospitalized_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">5. Are you taking any prescription/non-prescription medication?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="medication" value="Yes" ' . ($medication == 'Yes' ? 'checked' : '') . ' id="medication_yes">
                                <label for="medication_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="medication" value="No" ' . ($medication == 'No' ? 'checked' : '') . ' id="medication_no">
                                <label for="medication_no">No</label>
                            </div>
                        </div>
                        <div class="label-td" id="medication_specify_field" style="margin-top: 10px; ' . ($medication != 'Yes' ? 'display: none;' : '') . '">
                            <label for="medication_specify" class="form-label">If yes, please specify:</label>
                            <input type="text" name="medication_specify" class="input-text" placeholder="Medication details" value="' . htmlspecialchars($medication_specify) . '">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">6. Do you use tobacco products?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="tobacco" value="Yes" ' . ($tobacco == 'Yes' ? 'checked' : '') . ' id="tobacco_yes">
                                <label for="tobacco_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="tobacco" value="No" ' . ($tobacco == 'No' ? 'checked' : '') . ' id="tobacco_no">
                                <label for="tobacco_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">7. Do you use alcohol, cocaine or other dangerous drugs?</div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="drugs" value="Yes" ' . ($drugs == 'Yes' ? 'checked' : '') . ' id="drugs_yes">
                                <label for="drugs_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="drugs" value="No" ' . ($drugs == 'No' ? 'checked' : '') . ' id="drugs_no">
                                <label for="drugs_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">8. Are you allergic to any of the following?</div>
                        <div class="checkbox-group">
                            <div class="checkbox-option">
                                <input type="checkbox" name="allergies[]" value="Local Anesthetics" ' . (in_array('Local Anesthetics', $allergies) ? 'checked' : '') . ' id="allergy_anesthetics">
                                <label for="allergy_anesthetics">Local Anesthetics</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="allergies[]" value="Penicillin Products" ' . (in_array('Penicillin Products', $allergies) ? 'checked' : '') . ' id="allergy_penicillin">
                                <label for="allergy_penicillin">Penicillin Products</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="allergies[]" value="Sulfa Drugs" ' . (in_array('Sulfa Drugs', $allergies) ? 'checked' : '') . ' id="allergy_sulfa">
                                <label for="allergy_sulfa">Sulfa Drugs</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="allergies[]" value="Aspirin" ' . (in_array('Aspirin', $allergies) ? 'checked' : '') . ' id="allergy_aspirin">
                                <label for="allergy_aspirin">Aspirin</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="allergies[]" value="Latex" ' . (in_array('Latex', $allergies) ? 'checked' : '') . ' id="allergy_latex">
                                <label for="allergy_latex">Latex</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="allergies[]" value="Other" ' . (in_array('Other', $allergies) ? 'checked' : '') . ' id="allergy_other">
                                <label for="allergy_other">Other</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">9. Please provide the following information:</div>
                        <div class="label-td">
                            <label for="blood_pressure" class="form-label">Blood Pressure:</label>
                            <input type="text" name="blood_pressure" class="input-text" placeholder="Blood pressure" value="' . htmlspecialchars($blood_pressure) . '">
                        </div>
                        <div class="label-td">
                            <label for="bleeding_time" class="form-label">Bleeding Time:</label>
                            <input type="text" name="bleeding_time" class="input-text" placeholder="Bleeding time" value="' . htmlspecialchars($bleeding_time) . '">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">10. Do you have any of the following?</div>
                        <div class="checkbox-group" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="High Blood Pressure" ' . (in_array('High Blood Pressure', $health_conditions) ? 'checked' : '') . ' id="hc_high_bp">
                                <label for="hc_high_bp">High Blood Pressure</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Heart Attack" ' . (in_array('Heart Attack', $health_conditions) ? 'checked' : '') . ' id="hc_heart_attack">
                                <label for="hc_heart_attack">Heart Attack</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Kidney Disease" ' . (in_array('Kidney Disease', $health_conditions) ? 'checked' : '') . ' id="hc_kidney">
                                <label for="hc_kidney">Kidney Disease</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Low Blood Pressure" ' . (in_array('Low Blood Pressure', $health_conditions) ? 'checked' : '') . ' id="hc_low_bp">
                                <label for="hc_low_bp">Low Blood Pressure</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Thyroid Problem" ' . (in_array('Thyroid Problem', $health_conditions) ? 'checked' : '') . ' id="hc_thyroid">
                                <label for="hc_thyroid">Thyroid Problem</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Diabetes" ' . (in_array('Diabetes', $health_conditions) ? 'checked' : '') . ' id="hc_diabetes">
                                <label for="hc_diabetes">Diabetes</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Epilepsy/Convulsions" ' . (in_array('Epilepsy/Convulsions', $health_conditions) ? 'checked' : '') . ' id="hc_epilepsy">
                                <label for="hc_epilepsy">Epilepsy/Convulsions</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Heart Disease" ' . (in_array('Heart Disease', $health_conditions) ? 'checked' : '') . ' id="hc_heart_disease">
                                <label for="hc_heart_disease">Heart Disease</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Chest Pain" ' . (in_array('Chest Pain', $health_conditions) ? 'checked' : '') . ' id="hc_chest_pain">
                                <label for="hc_chest_pain">Chest Pain</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="AIDS or HIV Infection" ' . (in_array('AIDS or HIV Infection', $health_conditions) ? 'checked' : '') . ' id="hc_aids">
                                <label for="hc_aids">AIDS or HIV Infection</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Heart Murmur" ' . (in_array('Heart Murmur', $health_conditions) ? 'checked' : '') . ' id="hc_murmur">
                                <label for="hc_murmur">Heart Murmur</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Stroke" ' . (in_array('Stroke', $health_conditions) ? 'checked' : '') . ' id="hc_stroke">
                                <label for="hc_stroke">Stroke</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Sexually Transmitted Disease" ' . (in_array('Sexually Transmitted Disease', $health_conditions) ? 'checked' : '') . ' id="hc_std">
                                <label for="hc_std">Sexually Transmitted Disease</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Hepatitis/Liver Disease" ' . (in_array('Hepatitis/Liver Disease', $health_conditions) ? 'checked' : '') . ' id="hc_hepatitis">
                                <label for="hc_hepatitis">Hepatitis/Liver Disease</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Cancer/Tumors" ' . (in_array('Cancer/Tumors', $health_conditions) ? 'checked' : '') . ' id="hc_cancer">
                                <label for="hc_cancer">Cancer/Tumors</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Stomach Ulcers" ' . (in_array('Stomach Ulcers', $health_conditions) ? 'checked' : '') . ' id="hc_ulcers">
                                <label for="hc_ulcers">Stomach Ulcers</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Rheumatic Fever" ' . (in_array('Rheumatic Fever', $health_conditions) ? 'checked' : '') . ' id="hc_rheumatic">
                                <label for="hc_rheumatic">Rheumatic Fever</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Anemia" ' . (in_array('Anemia', $health_conditions) ? 'checked' : '') . ' id="hc_anemia">
                                <label for="hc_anemia">Anemia</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Fainting or Seizures" ' . (in_array('Fainting or Seizures', $health_conditions) ? 'checked' : '') . ' id="hc_seizures">
                                <label for="hc_seizures">Fainting or Seizures</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Hay Fever/Allergies" ' . (in_array('Hay Fever/Allergies', $health_conditions) ? 'checked' : '') . ' id="hc_hay_fever">
                                <label for="hc_hay_fever">Hay Fever/Allergies</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Angina" ' . (in_array('Angina', $health_conditions) ? 'checked' : '') . ' id="hc_angina">
                                <label for="hc_angina">Angina</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Radiation Therapy" ' . (in_array('Radiation Therapy', $health_conditions) ? 'checked' : '') . ' id="hc_radiation">
                                <label for="hc_radiation">Radiation Therapy</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Respiratory Problems" ' . (in_array('Respiratory Problems', $health_conditions) ? 'checked' : '') . ' id="hc_respiratory">
                                <label for="hc_respiratory">Respiratory Problems</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Asthma" ' . (in_array('Asthma', $health_conditions) ? 'checked' : '') . ' id="hc_asthma">
                                <label for="hc_asthma">Asthma</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Joint Replacement/Implant" ' . (in_array('Joint Replacement/Implant', $health_conditions) ? 'checked' : '') . ' id="hc_joint">
                                <label for="hc_joint">Joint Replacement/Implant</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Hepatitis/Jaundice" ' . (in_array('Hepatitis/Jaundice', $health_conditions) ? 'checked' : '') . ' id="hc_jaundice">
                                <label for="hc_jaundice">Hepatitis/Jaundice</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Emphysema" ' . (in_array('Emphysema', $health_conditions) ? 'checked' : '') . ' id="hc_emphysema">
                                <label for="hc_emphysema">Emphysema</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Heart Surgery" ' . (in_array('Heart Surgery', $health_conditions) ? 'checked' : '') . ' id="hc_heart_surgery">
                                <label for="hc_heart_surgery">Heart Surgery</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Tuberculosis" ' . (in_array('Tuberculosis', $health_conditions) ? 'checked' : '') . ' id="hc_tb">
                                <label for="hc_tb">Tuberculosis</label>
                            </div>
                            <div class="checkbox-option">
                                <input type="checkbox" name="health_conditions[]" value="Bleeding Problem" ' . (in_array('Bleeding Problem', $health_conditions) ? 'checked' : '') . ' id="hc_bleeding">
                                <label for="hc_bleeding">Bleeding Problem</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="action-buttons-right" style="flex-shrink: 0; padding: 20px; border-top: 1px solid #eee;">
                <a href="settings.php" class="action-btn cancel-btn">Cancel</a>
                <button type="submit" form="medical-history-form" class="action-btn done-btn">Save</button>
            </div>
        </div>
    </div>
    ';
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
                            
                            <div class="action-buttons-right">
                                <a href="settings.php" class="action-btn cancel-btn">Cancel</a>
                                <button type="submit" class="action-btn done-btn">Change</button>
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
                            
                            <div class="action-buttons-right">
                                <a href="settings.php" class="action-btn cancel-btn">Cancel</a>
                                <button type="submit" class="action-btn done-btn">Save</button>
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

             // Notification dropdown toggle
        const notificationContainer = document.getElementById('notificationContainer');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            notificationContainer.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                notificationDropdown.classList.remove('show');
            });
        });

        function toggleExpand(button) {
            const announcementItem = button.closest('.announcement-item');
            const content = announcementItem.querySelector('.announcement-content');
            const fullContent = announcementItem.querySelector('.full-content');

            if (content.style.display === 'none') {
                // Collapse
                content.style.display = 'block';
                fullContent.style.display = 'none';
                button.textContent = 'See more...';
            } else {
                // Expand
                content.style.display = 'none';
                fullContent.style.display = 'block';
                button.textContent = 'See less';
            }
        }
        
        // Add this function to update notification count display
function updateNotificationCount(newCount) {
    // Update the stat number
    const statNumber = document.querySelector('#notificationContainer .stat-number');
    if (statNumber) {
        statNumber.textContent = newCount;
    }
    
    // Update or remove the badge
    const badge = document.querySelector('.notification-badge');
    if (newCount > 0) {
        if (badge) {
            badge.textContent = newCount;
        } else {
            // Create new badge if it doesn't exist
            const notificationIcon = document.querySelector('#notificationContainer .stat-icon');
            const newBadge = document.createElement('span');
            newBadge.className = 'notification-badge';
            newBadge.textContent = newCount;
            notificationIcon.appendChild(newBadge);
        }
    } else {
        if (badge) {
            badge.remove();
        }
    }
}

function markAsRead(notificationId, element) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            element.classList.remove('unread');
            
            // Count remaining unread notifications
            const unreadCount = document.querySelectorAll('.notification-item.unread').length;
            updateNotificationCount(unreadCount);
        }
    });
}

function markAllAsRead() {
    fetch('mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread class from all notifications
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Update count to zero
            updateNotificationCount(0);
        }
    });
}
    </script>
    <script>
        // Show/hide conditional fields
        document.addEventListener('DOMContentLoaded', function() {
            // Under treatment field
            document.querySelectorAll('input[name="under_treatment"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.getElementById('condition_treated_field').style.display = 
                        this.value === 'Yes' ? 'block' : 'none';
                });
            });
            
            // Medication field
            document.querySelectorAll('input[name="medication"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.getElementById('medication_specify_field').style.display = 
                        this.value === 'Yes' ? 'block' : 'none';
                });
            });
        });
    </script>
</body>

</html>