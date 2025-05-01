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
$docid = $_SESSION['userid']; // Get dentist ID from session

// Fetch dentist information for the profile section
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$username = $userfetch["docname"];

// Get counts for sidebar
$patient_count = $database->query("SELECT COUNT(DISTINCT pid) FROM appointment WHERE docid='$docid'")->fetch_row()[0];
$booking_count = $database->query("SELECT COUNT(*) FROM appointment WHERE status='booking' AND docid='$docid'")->fetch_row()[0];
$appointment_count = $database->query("SELECT COUNT(*) FROM appointment WHERE status='appointment' AND docid='$docid'")->fetch_row()[0];

// Calendar variables
$today = date('Y-m-d');
$currentMonth = date('F');
$currentYear = date('Y');
$daysInMonth = date('t');
$firstDayOfMonth = date('N', strtotime("$currentYear-" . date('m') . "-01"));
$currentDay = date('j');

// Handle patient record view
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'view' && isset($_GET['id'])) {
        $patient_id = $_GET['id'];
        
        // Verify this patient has appointments with the current dentist
        $verify_sql = "SELECT * FROM appointment WHERE pid = ? AND docid = ?";
        $stmt = $database->prepare($verify_sql);
        $stmt->bind_param("ii", $patient_id, $docid);
        $stmt->execute();
        $verify_result = $stmt->get_result();
        
        if ($verify_result->num_rows == 0) {
            header("Location: dentist-records.php?error=unauthorized_access");
            exit();
        }
        
        // Fetch patient basic info
        $patient_sql = "SELECT * FROM patient WHERE pid = ?";
        $stmt = $database->prepare($patient_sql);
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $patient_result = $stmt->get_result();
        $patient = $patient_result->fetch_assoc();
        
        if (!$patient) {
            header("Location: dentist-records.php?error=patient_not_found");
            exit();
        }
        
        // Fetch medical history
        $medical_sql = "SELECT * FROM medical_history WHERE email = ?";
        $stmt = $database->prepare($medical_sql);
        $stmt->bind_param("s", $patient['pemail']);
        $stmt->execute();
        $medical_result = $stmt->get_result();
        $medical_history = $medical_result->fetch_assoc();
        
        // Fetch informed consent
        $consent_sql = "SELECT * FROM informed_consent WHERE email = ? ORDER BY consent_date DESC LIMIT 1";
        $stmt = $database->prepare($consent_sql);
        $stmt->bind_param("s", $patient['pemail']);
        $stmt->execute();
        $consent_result = $stmt->get_result();
        $informed_consent = $consent_result->fetch_assoc();
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
    <title>Patient Records - ToothTrackr</title>
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
            max-width: 800px;
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
        
        .record-section {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .record-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .record-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .record-label {
            font-weight: bold;
            width: 250px;
        }
        
        .signature-image {
            max-width: 300px;
            max-height: 150px;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        
        .record-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .record-thumbnail {
            max-width: 200px;
            max-height: 150px;
            display: block;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .download-btn {
            display: inline-block;
            padding: 5px 15px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin-top: 10px;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .vieww-btn {
            background-color: #4CAF50;
            color: white;
            width: 140px;
            padding-left: 20px;
        }
        
        .vieww-btn:hover {
            background-color: #45a049;
        }
        
        .dentalw-btn {
            background-color: #2196F3;
            color: white;
            width: 140px;
        }
        
        .dentalw-btn:hover {
            background-color: #0b7dda;
        }
        
        .addw-btn {
            background-color: #ff9800;
            color: white;
            width: 140px;
            padding-left: 27px;
        }
        
        .addw-btn:hover {
            background-color: #e68a00;
        }
        
        .profile-img-small {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
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
        
        .no-results {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .no-results img {
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
                    <?php
                    $userphoto = $userfetch["photo"];
                    if (!empty($userphoto) && file_exists("../admin/uploads/" . $userphoto)) {
                        $photopath = "../admin/uploads/" . $userphoto;
                    } else {
                        $photopath = "../Media/Icon/Blue/profile.png";
                    }
                    ?>
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
                <a href="booking.php" class="nav-item">
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
                <a href="dentist-records.php" class="nav-item active">
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
                    <?php if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($patient)): ?>
                        <!-- Patient Record View -->
                        <div class="announcements-header">
                            <h3 class="announcements-title">Patient Record: <?php echo $patient['pname']; ?></h3>
                            <div class="announcement-filters">
                                <a href="dentist-records.php" class="filter-btn active">Back to Records</a>
                            </div>
                        </div>
                        
                        <div class="record-section">
                            <h3>Patient Information</h3>
                            <div class="record-row">
                                <span class="record-label">Patient ID:</span>
                                <span>P-<?php echo $patient['pid']; ?></span>
                            </div>
                            <div class="record-row">
                                <span class="record-label">Name:</span>
                                <span><?php echo $patient['pname']; ?></span>
                            </div>
                            <div class="record-row">
                                <span class="record-label">Email:</span>
                                <span><?php echo $patient['pemail']; ?></span>
                            </div>
                            <div class="record-row">
                                <span class="record-label">Phone:</span>
                                <span><?php echo $patient['ptel']; ?></span>
                            </div>
                            <div class="record-row">
                                <span class="record-label">Date of Birth:</span>
                                <span><?php echo $patient['pdob']; ?></span>
                            </div>
                            <div class="record-row">
                                <span class="record-label">Address:</span>
                                <span><?php echo $patient['paddress']; ?></span>
                            </div>
                        </div>
                        
                        <div class="record-section">
                            <h3>Medical History</h3>
                            <?php if ($medical_history): ?>
                                <div class="record-row">
                                    <span class="record-label">In Good Health:</span>
                                    <span><?php echo $medical_history['good_health']; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Under Medical Treatment:</span>
                                    <span><?php echo $medical_history['under_treatment']; ?></span>
                                    <?php if ($medical_history['under_treatment'] == 'Yes'): ?>
                                        <div style="margin-left: 250px;"><?php echo $medical_history['condition_treated']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Serious Illness/Surgery:</span>
                                    <span><?php echo $medical_history['serious_illness']; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Hospitalized:</span>
                                    <span><?php echo $medical_history['hospitalized']; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Taking Medication:</span>
                                    <span><?php echo $medical_history['medication']; ?></span>
                                    <?php if ($medical_history['medication'] == 'Yes'): ?>
                                        <div style="margin-left: 250px;"><?php echo $medical_history['medication_specify']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Tobacco Use:</span>
                                    <span><?php echo $medical_history['tobacco']; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Recreational Drug Use:</span>
                                    <span><?php echo $medical_history['drugs']; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Allergies:</span>
                                    <span><?php echo $medical_history['allergies'] ? $medical_history['allergies'] : 'None reported'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Blood Pressure:</span>
                                    <span><?php echo $medical_history['blood_pressure'] ? $medical_history['blood_pressure'] : 'Not recorded'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Bleeding Time:</span>
                                    <span><?php echo $medical_history['bleeding_time'] ? $medical_history['bleeding_time'] : 'Not recorded'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Other Health Conditions:</span>
                                    <span><?php echo $medical_history['health_conditions'] ? $medical_history['health_conditions'] : 'None reported'; ?></span>
                                </div>
                            <?php else: ?>
                                <p>No medical history recorded for this patient.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="record-section">
                            <h3>Informed Consent</h3>
                            <?php if ($informed_consent): ?>
                                <div class="record-row">
                                    <span class="record-label">Consent Date:</span>
                                    <span><?php echo $informed_consent['consent_date']; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Treatment to be Done:</span>
                                    <span><?php echo $informed_consent['initial_treatment_to_be_done'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Drugs/Medications:</span>
                                    <span><?php echo $informed_consent['initial_drugs_medications'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Changes to Treatment Plan:</span>
                                    <span><?php echo $informed_consent['initial_changes_treatment_plan'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Radiographs (X-rays):</span>
                                    <span><?php echo $informed_consent['initial_radiograph'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Removal of Teeth:</span>
                                    <span><?php echo $informed_consent['initial_removal_teeth'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Crowns/Bridges:</span>
                                    <span><?php echo $informed_consent['initial_crowns_bridges'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Endodontics (Root Canal):</span>
                                    <span><?php echo $informed_consent['initial_endodontics'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Periodontal Disease Treatment:</span>
                                    <span><?php echo $informed_consent['initial_periodontal_disease'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Fillings:</span>
                                    <span><?php echo $informed_consent['initial_fillings'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <div class="record-row">
                                    <span class="record-label">Dentures:</span>
                                    <span><?php echo $informed_consent['initial_dentures'] == 'y' ? 'Agreed' : 'Not agreed'; ?></span>
                                </div>
                                <?php if ($informed_consent['id_signature_path']): ?>
                                    <div class="record-row">
                                        <span class="record-label">Signature:</span>
                                        <img src="<?php echo $informed_consent['id_signature_path']; ?>" alt="Patient Signature" class="signature-image">
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>No informed consent recorded for this patient.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="record-section">
                            <h3>Dental Records</h3>
                            <div class="action-buttons">
                                <a href="#" onclick="openDentalRecords(<?php echo $patient['pid']; ?>)" class="action-btn dental-btn">View Dental Records</a>
                                <a href="#" onclick="openAddDentalRecord(<?php echo $patient['pid']; ?>)" class="action-btn add-btn">Add Dental Record</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Patient List View -->
                        <div class="search-container">
                            <form action="" method="GET" style="display: flex; width: 100%;">
                                <input type="search" name="search" id="searchInput" class="search-input"
                                    placeholder="Search by name, email or phone number"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <?php if (isset($_GET['search']) && $_GET['search'] != ""): ?>
                                    <button type="button" class="clear-btn" onclick="clearSearch()">×</button>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="announcements-header">
                            <h3 class="announcements-title">Patient Records</h3>
                            <div class="announcement-filters">
                                <?php
                                $currentSort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
                                $searchParam = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                                ?>
                                <a href="?sort=newest<?php echo $searchParam; ?>"
                                    class="filter-btn newest-btn <?php echo ($currentSort === 'newest' || $currentSort === '') ? 'active' : 'inactive'; ?>">
                                    A-Z
                                </a>

                                <a href="?sort=oldest<?php echo $searchParam; ?>"
                                    class="filter-btn oldest-btn <?php echo $currentSort === 'oldest' ? 'active' : 'inactive'; ?>">
                                    Z-A
                                </a>
                            </div>
                        </div>

                        <?php
                        if ($_GET) {
                            $keyword = $_GET["search"] ?? '';
                            $sqlmain = "SELECT DISTINCT p.* FROM patient p 
                                JOIN appointment a ON p.pid = a.pid 
                                WHERE (p.pemail='$keyword' OR p.pname='$keyword' OR p.pname LIKE '$keyword%' OR p.pname LIKE '%$keyword' OR p.pname LIKE '%$keyword%') 
                                AND a.docid = $docid AND p.status = 'active' 
                                ORDER BY p.pname " . ($currentSort === 'oldest' ? 'DESC' : 'ASC');
                        } else {
                            $sqlmain = "SELECT DISTINCT p.* FROM patient p 
                                JOIN appointment a ON p.pid = a.pid 
                                WHERE a.docid = $docid AND p.status = 'active' 
                                ORDER BY p.pname " . ($currentSort === 'oldest' ? 'DESC' : 'ASC');
                        }
                        
                        $result = $database->query($sqlmain);
                        ?>

                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Profile</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Date of Birth</th>
                                            <th>Last Appointment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): 
                                            $pid = $row["pid"];
                                            $name = $row["pname"];
                                            $email = $row["pemail"];
                                            $dob = $row["pdob"];
                                            $tel = $row["ptel"];
                                            
                                            // Get last appointment date
                                            $appt_sql = "SELECT appodate FROM appointment 
                                                WHERE pid = $pid AND docid = $docid 
                                                ORDER BY appodate DESC LIMIT 1";
                                            $appt_result = $database->query($appt_sql);
                                            $last_appt = $appt_result->fetch_assoc();
                                            $last_appt_date = $last_appt ? $last_appt['appodate'] : 'N/A';
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $profile_pic = !empty($row["profile_pic"]) ? "../" . $row["profile_pic"] : "../Media/Icon/Blue/profile.png";
                                                    ?>
                                                    <img src="<?php echo $profile_pic; ?>" alt="<?php echo $name; ?>" class="profile-img-small">
                                                </td>
                                                <td><div class="cell-text"><?php echo $name; ?></div></td>
                                                <td><div class="cell-text"><?php echo $email; ?></div></td>
                                                <td><div class="cell-text"><?php echo $tel; ?></div></td>
                                                <td><div class="cell-text"><?php echo $dob; ?></div></td>
                                                <td><div class="cell-text"><?php echo $last_appt_date; ?></div></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="dentist-records.php?action=view&id=<?php echo $pid; ?>" class="action-btn vieww-btn">View Records</a>
                                                        <a href="#" onclick="openDentalRecords(<?php echo $pid; ?>)" class="action-btn dentalw-btn">Dental Records</a>
                                                        <a href="#" onclick="openAddDentalRecord(<?php echo $pid; ?>)" class="action-btn addw-btn">Add Record</a>
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
                                <p>No patient records found!</p>
                                <p>You will see patients here after you have appointments with them.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Right sidebar section -->
                <div class="right-sidebar">
                    <div class="stats-section">
                        <div class="stats-container">
                            <!-- First row -->
<a href="patient.php" class="stat-box-link">
    <div class="stat-box">
        <div class="stat-content">
            <h1 class="stat-number"><?php echo $patient_count; ?></h1>
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

<a href="appointment.php" class="stat-box-link">
    <div class="stat-box">
        <div class="stat-content">
            <h1 class="stat-number"><?php echo $appointment_count; ?></h1>
            <p class="stat-label">Appointments</p>
        </div>
        <div class="stat-icon">
            <img src="../Media/Icon/Blue/appointment.png" alt="Appointment Icon">
            <?php if ($appointment_count > 0): ?>
                <span class="notification-badge"><?php echo $appointment_count; ?></span>
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
                                    appointment.docid = '$docid'
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
    
    <!-- Dental Records Popup -->
    <div id="dentalRecordsPopup" class="overlay">
        <div class="popup">
            <a class="close" href="#">&times;</a>
            <h2>Dental Records</h2>
            <div class="content" id="dentalRecordsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Add Dental Record Popup -->
    <div id="addDentalRecordPopup" class="overlay">
        <div class="popup">
            <a class="close" href="#">&times;</a>
            <h2>Upload Dental Record</h2>
            <form action="upload-dental-record.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="patient_id" id="uploadPatientId">
                <div class="form-group">
                    <label>Select Image:</label>
                    <input type="file" name="dental_record" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn-primary btn">Upload Record</button>
            </form>
        </div>
    </div>

    <script>
    function openDentalRecords(pid) {
        document.getElementById('dentalRecordsContent').innerHTML = 'Loading...';
        document.getElementById('dentalRecordsPopup').style.display = 'flex';
        
        // Load dental records via AJAX
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get-dental-records.php?pid=' + pid, true);
        xhr.onload = function() {
            if (this.status == 200) {
                document.getElementById('dentalRecordsContent').innerHTML = this.responseText;
            } else {
                document.getElementById('dentalRecordsContent').innerHTML = 'Error loading records';
            }
        };
        xhr.send();
    }

    function openAddDentalRecord(pid) {
        document.getElementById('uploadPatientId').value = pid;
        document.getElementById('addDentalRecordPopup').style.display = 'flex';
    }

    function clearSearch() {
        window.location.href = 'dentist-records.php';
    }

    // Close popups when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('overlay')) {
            event.target.style.display = 'none';
        }
    });

    // Close popups when clicking the close button
    document.querySelectorAll('.popup .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            this.closest('.overlay').style.display = 'none';
        });
    });

    // Search input event listener
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.querySelector('.clear-btn');

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                clearSearch();
            });
        }
    });
    </script>
</body>
</html>