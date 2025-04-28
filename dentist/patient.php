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


    <title>My Patients - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        #addPopup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }


        .popup {
            animation: transitionIn-Y-bottom 0.5s;
            width: 80em;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            max-height: 80%;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        }


        /* Close button style */
        #addPopup .close {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 30px;
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }


        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }
       
        .patient-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }


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
            animation: transitionIn-Y-bottom 0.5s;
            width: 80em;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            max-height: 80%;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        }


        .popup .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
       
        .table-section {
            margin-bottom: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
       
        .table-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
       
        .inactive-table {
            opacity: 0.8;
        }
       
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
       
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
       
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .content-area {
            height: 100vh; /* This makes it take full viewport height */
            overflow-y: auto; /* This enables vertical scrolling */
            padding-bottom: 20px; /* Adds some padding at the bottom */
        }
    </style>
</head>


<body>
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


    //import database
    include("../connection.php");
    $userrow = $database->query("select * from doctor where docemail='$useremail'");
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["docid"];
    $username = $userfetch["docname"];


    $selecttype = "My";
    $current = "My patients Only";
    if ($_POST) {
        if (isset($_POST["search"])) {
            $keyword = $_POST["search12"];
            $sqlmain = "select distinct * from patient where (pemail='$keyword' or pname='$keyword' or pname like '$keyword%' or pname like '%$keyword' or pname like '%$keyword%') and status='active'";
            $sqlmain_inactive = "select distinct * from patient where (pemail='$keyword' or pname='$keyword' or pname like '$keyword%' or pname like '%$keyword' or pname like '%$keyword%') and status='inactive'";
            $selecttype = "my";
        }


        if (isset($_POST["filter"])) {
            if ($_POST["showonly"] == 'all') {
                $sqlmain = "select * from patient where status='active'";
                $sqlmain_inactive = "select * from patient where status='inactive'";
                $selecttype = "All";
                $current = "All patients";
            } else {
                $sqlmain = "SELECT patient.* FROM appointment INNER JOIN patient ON patient.pid = appointment.pid WHERE appointment.docid = $userid AND patient.status='active' GROUP BY patient.pid";
                $sqlmain_inactive = "SELECT patient.* FROM appointment INNER JOIN patient ON patient.pid = appointment.pid WHERE appointment.docid = $userid AND patient.status='inactive' GROUP BY patient.pid";
                $selecttype = "My";
                $current = "My patients Only";
            }
        }
    } else {
        // Active patients with most recent appointment
        $sqlmain = "
            SELECT patient.*
            FROM appointment
            INNER JOIN patient ON patient.pid = appointment.pid
            WHERE appointment.docid = $userid AND patient.status='active'
            GROUP BY patient.pid
        ";
       
        // Inactive patients who were previously patients
        $sqlmain_inactive = "
            SELECT patient.*
            FROM appointment
            INNER JOIN patient ON patient.pid = appointment.pid
            WHERE appointment.docid = $userid AND patient.status='inactive'
            GROUP BY patient.pid
        ";
       
        $selecttype = "My";
    }
   
    // Get counts for both tables
    $result_active = $database->query($sqlmain);
    $active_count = $result_active->num_rows;
   
    $result_inactive = $database->query($sqlmain_inactive);
    $inactive_count = $result_inactive->num_rows;
    ?>
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
                <a href="patient.php" class="nav-item active">
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
        <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
            <tr>
                <td width="13%">
                    <a href="patient.php"><button class="login-btn btn-primary-soft btn btn-icon-back"
                            style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                            <font class="tn-in-text">Back</font>
                        </button></a>
                </td>
                <td>
                    <form action="" method="post" class="header-search">
                        <input type="search" name="search12" class="input-text header-searchbar"
                            placeholder="Search Patient name or Email" list="patient">&nbsp;&nbsp;


                        <?php
                        echo '<datalist id="patient">';
                        $list11 = $database->query($sqlmain);
                       
                        for ($y = 0; $y < $list11->num_rows; $y++) {
                            $row00 = $list11->fetch_assoc();
                            $d = $row00["pname"];
                            $c = $row00["pemail"];
                            echo "<option value='$d'><br/>";
                            echo "<option value='$c'><br/>";
                        };
                        echo ' </datalist>';
                        ?>


                        <input type="Submit" value="Search" name="search" class="login-btn btn-primary btn"
                            style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                    </form>
                </td>
                <td width="15%">
                    <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                        Today's Date
                    </p>
                    <p class="heading-sub12" style="padding: 0;margin: 0;">
                        <?php
                        date_default_timezone_set('Asia/Kolkata');
                        $date = date('Y-m-d');
                        echo $date;
                        ?>
                    </p>
                </td>
                <td width="10%">
                    <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img
                            src="../img/calendar.svg" width="100%"></button>
                </td>
            </tr>


            <tr>
                <td colspan="4" style="padding-top:0px;width: 100%;">
                    <center>
                        <table class="filter-container" border="0">
                            <form action="" method="post">
                                <td style="text-align: right;">
                                    Show Details About : &nbsp;
                                </td>
                                <td width="30%">
                                    <select name="showonly" id="" class="box filter-container-items"
                                        style="width:90% ;height: 37px;margin: 0;">
                                        <option value="" disabled selected hidden><?php echo $current ?></option>
                                        <br />
                                        <option value="my">My Patients Only</option><br />
                                        <option value="all">All Patients</option><br />
                                    </select>
                                </td>
                                <td width="12%">
                                    <input type="submit" name="filter" value=" Filter"
                                        class=" btn-primary-soft btn button-icon btn-filter"
                                        style="padding: 15px; margin :0;width:100%">
                                </td>
                            </form>
                        </table>
                    </center>
                </td>
            </tr>


            <tr>
                <td colspan="4">
                    <center>
                        <!-- Active Patients Table -->
                        <div class="table-section">
                            <h3 class="table-title">Active Patients (<?php echo $active_count; ?>)</h3>
                            <div class="abc scroll">
                                <table width="100%" class="sub-table scrolldown" style="border-spacing:0;">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Status</th>
                                            <th class="table-headin">Name</th>
                                            <th class="table-headin">Picture</th>
                                            <th class="table-headin">Telephone</th>
                                            <th class="table-headin">Email</th>
                                            <th class="table-headin">Date of Birth</th>
                                            <th class="table-headin">Events</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $database->query($sqlmain);
                                        if ($result->num_rows == 0) {
                                            echo '<tr>
                                            <td colspan="7">
                                            <br><br><br><br>
                                            <center>
                                            <img src="../img/notfound.svg" width="25%">
                                           
                                            <br>
                                            <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">No active patients found!</p>
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
                                                $status = $row["status"];
                                                $profile_pic = !empty($row["profile_pic"]) ? "../" . $row["profile_pic"] : "../Media/Icon/Blue/profile.png";


                                                echo '<tr>
                                                    <td><span class="status-badge status-active">Active</span></td>
                                                    <td> &nbsp;' .
                                                        substr($name, 0, 35)
                                                        . '</td>
                                                    <td style="text-align: center;">
                                                        <img src="' . $profile_pic . '" alt="Profile" class="patient-pic">
                                                    </td>
                                                    <td>
                                                        ' . substr($tel, 0, 10) . '
                                                    </td>
                                                    <td>
                                                    ' . substr($email, 0, 20) . '
                                                     </td>
                                                    <td>
                                                    ' . substr($dob, 0, 10) . '
                                                    </td>
                                                    <td>
                                                    <div style="display:flex;justify-content: center;">
                                                   
                                                    <a href="?action=view&id=' . $pid . '" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 60px;padding-right: 60px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>


                                                    </div>
                                                    </td>
                                                </tr>';
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                       
                        <!-- Inactive Patients Table -->
                        <div class="table-section inactive-table">
                            <h3 class="table-title">Inactive Patients (<?php echo $inactive_count; ?>)</h3>
                            <div class="abc scroll">
                                <table width="100%" class="sub-table scrolldown" style="border-spacing:0;">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Status</th>
                                            <th class="table-headin">Name</th>
                                            <th class="table-headin">Picture</th>
                                            <th class="table-headin">Telephone</th>
                                            <th class="table-headin">Email</th>
                                            <th class="table-headin">Date of Birth</th>
                                            <th class="table-headin">Events</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result_inactive = $database->query($sqlmain_inactive);
                                        if ($result_inactive->num_rows == 0) {
                                            echo '<tr>
                                            <td colspan="7">
                                            <br><br><br><br>
                                            <center>
                                            <img src="../img/notfound.svg" width="25%">
                                           
                                            <br>
                                            <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">No inactive patients found!</p>
                                            </center>
                                            <br><br><br><br>
                                            </td>
                                            </tr>';
                                        } else {
                                            for ($x = 0; $x < $result_inactive->num_rows; $x++) {
                                                $row = $result_inactive->fetch_assoc();
                                                $pid = $row["pid"];
                                                $name = $row["pname"];
                                                $email = $row["pemail"];
                                                $dob = $row["pdob"];
                                                $tel = $row["ptel"];
                                                $status = $row["status"];
                                                $profile_pic = !empty($row["profile_pic"]) ? "../" . $row["profile_pic"] : "../Media/Icon/Blue/profile.png";


                                                echo '<tr>
                                                    <td><span class="status-badge status-inactive">Inactive</span></td>
                                                    <td> &nbsp;' .
                                                        substr($name, 0, 35)
                                                        . '</td>
                                                    <td style="text-align: center;">
                                                        <img src="' . $profile_pic . '" alt="Profile" class="patient-pic">
                                                    </td>
                                                    <td>
                                                        ' . substr($tel, 0, 10) . '
                                                    </td>
                                                    <td>
                                                    ' . substr($email, 0, 20) . '
                                                     </td>
                                                    <td>
                                                    ' . substr($dob, 0, 10) . '
                                                    </td>
                                                    <td>
                                                    <div style="display:flex;justify-content: center;">
                                                   
                                                    <a href="?action=view&id=' . $pid . '" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 60px;padding-right: 60px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>


                                                    </div>
                                                    </td>
                                                </tr>';
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </center>
                </td>
            </tr>
        </table>
    </div>
    </div>


    <?php
    if ($_GET) {
        $id = $_GET["id"];
        $action = $_GET["action"];


        $sqlPatient = "SELECT * FROM patient WHERE pid='$id'";
        $resultPatient = $database->query($sqlPatient);
        $rowPatient = $resultPatient->fetch_assoc();


        $name = $rowPatient["pname"];
        $email = $rowPatient["pemail"];
        $dob = $rowPatient["pdob"];
        $tele = $rowPatient["ptel"];
        $address = $rowPatient["paddress"];
        $status = $rowPatient["status"];
        $profile_pic = !empty($rowPatient["profile_pic"]) ? "../" . $rowPatient["profile_pic"] : "../Media/Icon/Blue/profile.png";


        $sqlHistory = "SELECT * FROM medical_history WHERE email='$email'";
        $resultHistory = $database->query($sqlHistory);


        echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="patient.php">&times;</a>
                        
                        <div style="display: flex; justify-content: center;">
                            <table width="90%" class="sub-table scrolldown add-doc-form-container" border="0" style="text-align: left;">
                                <tr>
                                    <td colspan="2" style="padding-bottom: 20px; text-align: center;">
                                        <img src="' . $profile_pic . '" alt="Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid ' . ($status == 'active' ? '#2ecc71' : '#e74c3c') . ';">
                                        <h3>Patient Information</h3>
                                        <span class="status-badge ' . ($status == 'active' ? 'status-active' : 'status-inactive') . '">' . ucfirst($status) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 50%; padding: 10px;"><strong>Patient ID:</strong></td>
                                    <td style="width: 50%; padding: 10px;">P-' . htmlspecialchars($id) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Patient Name:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($name) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Email:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($email) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Telephone:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($tele) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Date of Birth:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($dob) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Address:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($address) . '</td>
                                </tr>';


        if ($resultHistory->num_rows > 0) {
            $rowHistory = $resultHistory->fetch_assoc();


            echo '
                                <tr>
                                    <td colspan="2" style="padding-top: 20px; text-align: center;">
                                        <h3>Medical History</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Good Health:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["good_health"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Under Treatment:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["under_treatment"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Had a serious surgical operation:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["condition_treated"] ?: "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Had a serious illness:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["serious_illness"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Hospitalized:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["hospitalized"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Taking any prescription/non-prescription medication:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["medication"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Medication Specify:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["medication_specify"] ?: "-") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Use Tobacco:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["tobacco"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Use Alcohol or Dangerous Drugs:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["drugs"] ?? "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Have Allergies:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["allergies"] ?: "No") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Blood Pressure:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["blood_pressure"] ?: "-") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Bleeding Time:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["bleeding_time"] ?: "-") . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px;"><strong>Health Conditions:</strong></td>
                                    <td style="padding: 10px;">' . htmlspecialchars($rowHistory["health_conditions"] ?: "None") . '</td>
                                </tr>';
        } else {
            echo '
                                <tr>
                                    <td colspan="2" style="padding: 20px; text-align: center;">
                                        <p>No medical history found for this patient.</p>
                                    </td>
                                </tr>';
        }


        echo '
                                <tr>
                                    <td colspan="2" style="text-align: center; padding-top: 20px;">
                                        <a href="patient.php"><input type="button" value="Back" class="login-btn btn-primary-soft btn"></a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>';
    }
    ?>


    <script>
        function openAddPopup() {
            document.getElementById('addPopup').style.display = 'block';
        }


        function closeAddPopup() {
            document.getElementById('addPopup').style.display = 'none';
        }
        window.onload = function() {
            <?php if(isset($_GET['action']) && $_GET['action'] == 'view'): ?>
            document.getElementById('popup1').style.display = 'flex';
            <?php endif; ?>
        };
        document.addEventListener('click', function(e) {
            if(e.target.classList.contains('close')) {
                e.target.closest('.overlay').style.display = 'none';
            }
        });
    </script>


</body>
</html>

