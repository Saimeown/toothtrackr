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

    <title>My Appointments - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }

        .sub-table {
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
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            text-align: center;
            animation: transitionIn-Y-bottom 0.3s;
        }

        .modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
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
            transition: background-color 0.3s;
        }

        .btn-secondary:hover {
            background-color: #da190b;
        }

        .cancel-reason {
            width: 100%;
            margin: 15px 0;
        }

        .cancel-reason select, .cancel-reason textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }

        .cancel-reason textarea {
            height: 80px;
            resize: vertical;
            display: none;
        }
    </style>
</head>

<body>
    <?php    
    session_start();

    if (isset($_SESSION["user"])) {
        if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'd') {
            header("location: login.php");
        } else {
            $useremail = $_SESSION["user"];
        }
    } else {
        header("location: login.php");
    }

    include("../connection.php");
    $userrow = $database->query("select * from doctor where docemail='$useremail'");
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["docid"];
    $username = $userfetch["docname"];
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
                <a href="appointment.php" class="nav-item active">
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
        <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
            <tr>
                <td width="13%">
                    <a href="appointment.php"><button class="login-btn btn-primary-soft btn btn-icon-back"
                            style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                            <font class="tn-in-text">Back</font>
                        </button></a>
                </td>
                <td>
                    <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Appointment Manager</p>

                </td>
                <td width="15%">
                    <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                        Today's Date
                    </p>
                    <p class="heading-sub12" style="padding: 0;margin: 0;">
                        <?php
                        date_default_timezone_set('Asia/Kolkata');
                        $today = date('Y-m-d');
                        echo $today;

                        $list110 = $database->query("SELECT appointment.* 
                            FROM appointment 
                            JOIN schedule ON appointment.scheduleid = schedule.scheduleid 
                            WHERE appointment.status = 'appointment' AND schedule.docid = '$userid';");
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
                                        <th class="table-headin">Patient name</th>
                                        <th class="table-headin">Procedure</th>
                                        <th class="table-headin">Date</th>
                                        <th class="table-headin">Time</th>
                                        <th class="table-headin">Events</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sqlmain = "SELECT appointment.appoid, procedures.procedure_name, patient.pname, appointment.appodate, appointment.appointment_time 
                                    FROM appointment
                                    INNER JOIN patient ON appointment.pid = patient.pid
                                    INNER JOIN procedures ON appointment.procedure_id = procedures.procedure_id
                                    WHERE appointment.docid = '$userid' AND appointment.status = 'appointment'
                                    ORDER BY appointment.appodate, appointment.appointment_time";
                                    
                                    if (isset($_POST['filter'])) {
                                        $filterDate = $_POST['appodate'];
                                        if (!empty($filterDate)) {
                                            $sqlmain .= " AND appointment.appodate = '$filterDate'";
                                        }
                                    }
                                                                        
                                    $result = $database->query($sqlmain);

                                    if ($result->num_rows == 0) {
                                        echo "<tr><td colspan='5'>No appointments found.</td></tr>";
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
                                                <td>' . $appodate . '</td>
                                                <td>' . $appointment_time . '</td>
                                                <td>
                                                    <button onclick="showCancelModal(' . $appoid . ', \'' . $pname . '\')" class="btn-primary-soft btn button-icon btn-delete" style="padding-left: 40px; padding-top: 10px; padding-bottom: 10px; margin-top: 10px; margin-bottom: 10px;">
                                                        <font class="tn-in-text">Cancel</font>
                                                    </button>
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

    <div id="cancelModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h2>Cancel Appointment</h2>
            <p>You are about to cancel an appointment for <span id="patientName"></span></p>
            
            <div class="cancel-reason">
                <label for="cancelReason">Reason for cancellation:</label>
                <select id="cancelReason" class="form-control">
                    <option value="">-- Select a reason --</option>
                    <option value="Dentist Unavailable">Dentist Unavailable</option>
                    <option value="Emergency Situation">Emergency Situation</option>
                    <option value="Patient Request">Patient Request</option>
                    <option value="Clinic Closed">Clinic Closed</option>
                    <option value="Other">Other (please specify)</option>
                </select>
                <textarea id="otherReason" placeholder="Please specify the reason..." class="form-control"></textarea>
            </div>
            
            <div class="modal-buttons">
                <button id="confirmCancelBtn" class="btn-primary">Confirm</button>
                <button id="cancelCancelBtn" class="btn-secondary">Close</button>
            </div>
        </div>
    </div>

    <script>
        let currentAppointmentId = null;
        
        function showCancelModal(appoid, patientName) {
            currentAppointmentId = appoid;
            document.getElementById('patientName').textContent = patientName;
            document.getElementById('cancelModal').style.display = 'flex';
            
            document.getElementById('cancelReason').value = '';
            document.getElementById('otherReason').value = '';
            document.getElementById('otherReason').style.display = 'none';
        }
        
        document.getElementById('cancelReason').addEventListener('change', function() {
            const otherReason = document.getElementById('otherReason');
            if (this.value === 'Other') {
                otherReason.style.display = 'block';
                otherReason.required = true;
            } else {
                otherReason.style.display = 'none';
                otherReason.required = false;
            }
        });
        
        document.getElementById('cancelCancelBtn').addEventListener('click', function() {
            document.getElementById('cancelModal').style.display = 'none';
        });
        
        document.getElementById('confirmCancelBtn').addEventListener('click', function() {
            const reason = document.getElementById('cancelReason').value;
            const otherReason = document.getElementById('otherReason').value;
            
            if (!reason) {
                alert('Please select a cancellation reason');
                return;
            }
            
            if (reason === 'Other' && !otherReason) {
                alert('Please specify the cancellation reason');
                return;
            }
            
            const fullReason = reason === 'Other' ? otherReason : reason;
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            fetch('delete-appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${currentAppointmentId}&reason=${encodeURIComponent(fullReason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.msg);
                    document.getElementById(`row-${currentAppointmentId}`).remove();
                } else {
                    alert(data.msg || 'Error cancelling appointment');
                }
                document.getElementById('cancelModal').style.display = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to cancel appointment. Please try again.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Confirm';
            });
        });
    </script>
</body>
</html>