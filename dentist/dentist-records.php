<?php
session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'd') {
        header("location: login.php");
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: login.php");
}

include("../connection.php");
$docid = $_SESSION['userid']; // Get dentist ID from session

// Fetch dentist information for the profile section
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$username = $userfetch["docname"];

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
    <link rel="stylesheet" href="../css/dental-record.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <title>Patient Records - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
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
        .overlay {
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
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
            width: 80%;
            max-width: 800px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            max-height: 80%;
            overflow-y: auto;
            position: relative;
        }
        .popup .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
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
            gap: 5px;
            justify-content: center;
        }
        .action-buttons a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="nav-container">
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
            <?php if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($patient)): ?>
                 <!-- Patient Record View -->
                 <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;">
                    <tr>
                        <td width="13%">
                            <a href="dentist-records.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="margin-left:20px;">Back</button></a>
                        </td>
                        <td>
                            <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Patient Record: <?php echo $patient['pname']; ?></p>
                        </td>
                        <td width="15%">
                            <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Today's Date</p>
                            <p class="heading-sub12" style="padding: 0;margin: 0;">
                                <?php echo date('Y-m-d'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="3" style="padding-top:20px;">
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
                        </td>
                    </tr>
                </table>
            <?php else: ?>
                <!-- Patient List View - Only shows patients with appointments -->
                <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                    <tr>
                        <td width="13%"></td>
                        <td>
                            <form action="" method="post" class="header-search">
                                <input type="search" name="search" class="input-text header-searchbar" placeholder="Search Patient name or Email" list="patient">&nbsp;&nbsp;
                                <?php
                                echo '<datalist id="patient">';
                                $list11 = $database->query("SELECT DISTINCT p.pname, p.pemail 
                                    FROM patient p 
                                    JOIN appointment a ON p.pid = a.pid 
                                    WHERE a.docid = $docid AND p.status = 'active'");
                                
                                for ($y = 0; $y < $list11->num_rows; $y++) {
                                    $row00 = $list11->fetch_assoc();
                                    $d = $row00["pname"];
                                    $c = $row00["pemail"];
                                    echo "<option value='$d'><br/>";
                                    echo "<option value='$c'><br/>";
                                }
                                echo ' </datalist>';
                                ?>
                                <input type="Submit" value="Search" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                            </form>
                        </td>
                        <td width="15%">
                            <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Today's Date</p>
                            <p class="heading-sub12" style="padding: 0;margin: 0;">
                                <?php echo date('Y-m-d'); ?>
                            </p>
                        </td>
                        <td width="10%">
                            <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" style="padding-top:10px;">
                            <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">My Patients (<?php echo $list11->num_rows; ?>)</p>
                        </td>
                    </tr>

                    <?php
                    if ($_POST) {
                        $keyword = $_POST["search"];
                        $sqlmain = "SELECT DISTINCT p.* FROM patient p 
                            JOIN appointment a ON p.pid = a.pid 
                            WHERE (p.pemail='$keyword' OR p.pname='$keyword' OR p.pname LIKE '$keyword%' OR p.pname LIKE '%$keyword' OR p.pname LIKE '%$keyword%') 
                            AND a.docid = $docid AND p.status = 'active'";
                    } else {
                        $sqlmain = "SELECT DISTINCT p.* FROM patient p 
                            JOIN appointment a ON p.pid = a.pid 
                            WHERE a.docid = $docid AND p.status = 'active' 
                            ORDER BY p.pid DESC";
                    }
                    ?>

                    <tr>
                        <td colspan="4">
                            <center>
                                <div class="abc scroll">
                                    <table width="93%" class="sub-table scrolldown" style="border-spacing:0;">
                                        <thead>
                                            <tr>
                                                <th class="table-headin">Name</th>
                                                <th class="table-headin">Telephone</th>
                                                <th class="table-headin">Email</th>
                                                <th class="table-headin">Date of Birth</th>
                                                <th class="table-headin">Last/Upcoming Appointment</th>
                                                <th class="table-headin">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $result = $database->query($sqlmain);

                                            if ($result->num_rows == 0) {
                                                echo '<tr>
                                                <td colspan="6">
                                                <br><br><br><br>
                                                <center>
                                                <img src="../img/notfound.svg" width="25%">
                                                <br>
                                                <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">No patient records found!</p>
                                                <p class="heading-main12" style="margin-left: 45px;font-size:15px;color:rgb(49, 49, 49)">You will see patients here after you have appointments with them.</p>
                                                </center>
                                                <br><br><br><br>
                                                </td>
                                                </tr>';
                                            } else {
                                                for ($x = 0; $x < $result->num_rows; $x++) {
                                                    $row = $result->fetch_assoc();
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

                                                    echo '<tr>
                                                    <td> &nbsp;' . substr($name, 0, 35) . '</td>
                                                    <td>' . substr($tel, 0, 10) . '</td>
                                                    <td>' . substr($email, 0, 20) . '</td>
                                                    <td>' . substr($dob, 0, 10) . '</td>
                                                    <td>' . $last_appt_date . '</td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="dentist-records.php?action=view&id=' . $pid . '" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-view" style="padding: 8px 15px;">
                                                                    <font class="tn-in-text">View Records</font>
                                                                </button>
                                                            </a>
                                                            <a href="#" onclick="openDentalRecords(' . $pid . ')" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-view" style="padding: 8px 15px;">
                                                                    <font class="tn-in-text">Dental Records</font>
                                                                </button>
                                                            </a>
                                                            <a href="#" onclick="openAddDentalRecord(' . $pid . ')" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-view" style="padding: 8px 15px;">
                                                                    <font class="tn-in-text">Add Record</font>
                                                                </button>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    </tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </center>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>
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
    </script>
</body>
</html>