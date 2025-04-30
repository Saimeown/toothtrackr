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

    <title>Bookings - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .popup, .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }

        .modal-buttons {
            margin-top: 20px;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
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
        }

        .btn-secondary:hover {
            background-color: #da190b;
        }
    </style>
</head>

<body>
<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION['usertype'] != 'd') {
    header("location: login.php");
    exit();
}

include("../connection.php");

$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$username = $userfetch["docname"];

date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

// Fetch bookings query
$sqlmain = "SELECT 
            appointment.appoid, 
            procedures.procedure_name, 
            patient.pname, 
            appointment.appodate, 
            appointment.appointment_time 
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
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="dashboard.php"><button class="login-btn btn-primary-soft btn btn-icon-back"
                                style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                <font class="tn-in-text">Back</font>
                            </button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Booking Manager</p>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Today's Date
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php echo $today; ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img
                                src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;width: 100%;">
                        <center>
                            <table class="filter-container" border="0">
                                <tr>
                                    <td width="10%"></td>
                                    <td width="5%" style="text-align: center;">Date:</td>
                                    <td width="30%">
                                        <form action="" method="post">
                                            <input type="date" name="appodate" id="date"
                                                class="input-text filter-container-items" style="margin: 0;width: 95%;">
                                    </td>
                                    <td width="12%">
                                        <input type="submit" name="filter" value="Filter"
                                            class="btn-primary-soft btn button-icon btn-filter"
                                            style="padding: 15px; margin:0;width:100%">
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </center>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll">
                                <table width="93%" class="sub-table scrolldown" border="0">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Patient Name</th>
                                            <th class="table-headin">Procedure</th>
                                            <th class="table-headin">Date & Time</th>
                                            <th class="table-headin">Events</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows == 0) {
                                            echo '<tr>
                                                    <td colspan="4">
                                                        <center>
                                                            <img src="../img/notfound.svg" width="25%">
                                                            <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">No pending bookings found!</p>
                                                        </center>
                                                    </td>
                                                  </tr>';
                                        } else {
                                            while ($row = $result->fetch_assoc()) {
                                                $appoid = $row["appoid"];
                                                $procedure_name = $row["procedure_name"];
                                                $pname = $row["pname"];
                                                $appodate = $row["appodate"];
                                                $appointment_time = $row["appointment_time"];
                                        
                                                echo '<tr id="row-' . $appoid . '">
                                                    <td>' . $pname . '</td>
                                                    <td>' . $procedure_name . '</td>
                                                    <td>' . $appodate . ' @ ' . $appointment_time . '</td>
                                                    <td>
                                                        <a href="#" onclick="updateBooking(' . $appoid . ', \'accept\')" class="btn-primary-soft btn">Accept</a>
                                                        <a href="#" onclick="updateBooking(' . $appoid . ', \'reject\')" class="btn-primary-soft btn">Reject</a>
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
        </div>
    </div>

    <div id="confirmationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="modalMessage">Are you sure you want to proceed?</p>
            <div class="modal-buttons">
                <button onclick="confirmAction()" class="btn-primary">Yes</button>
                <button onclick="closeModal()" class="btn-secondary">No</button>
            </div>
        </div>
    </div>

    <?php
    if ($_GET) {
        $id = $_GET["id"];
        $action = $_GET["action"];

        if ($action == 'accept') {
            $database->query("UPDATE appointment SET status='appointment' WHERE appoid='$id'");
            header("Location: booking.php");
            exit();
        } elseif ($action == 'reject') {
            $database->query("DELETE FROM appointment WHERE appoid='$id'");
            header("Location: booking.php");
            exit();
        }
    }
    ?>

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
            fetch(`booking.php?action=${currentAction}&id=${currentAppoid}`)
                .then(response => {
                    if (response.ok) {
                        document.getElementById(`row-${currentAppoid}`).remove();
                        closeModal();
                    } else {
                        alert("Failed to update booking. Please try again.");
                        closeModal();
                    }
                })
                .catch(err => {
                    console.error("Error:", err);
                    closeModal();
                });
        }

        function closeModal() {
            document.getElementById("confirmationModal").style.display = "none";
            currentAppoid = null;
            currentAction = null;
        }
    </script>

</body>

</html>
