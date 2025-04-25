<?php
session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
    }
} else {
    header("location: ../login.php");
}

// Import database connection
include("../connection.php");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Media/white-icon/white-ToothTrackr_Logo.png" type="image/png">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <title>All Dentists</title>
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }
    </style>
</head>
<body>
    <div class="nav-container">
        <div class="menu">
            <table class="menu-container" border="0">
                <!-- Menu content here, unchanged from dentist.php -->
                <table class="menu-container" border="0">
                <tr>
                    <td style="padding:10px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td>
                                    <img class="profile-pic" src="../Media/SDMC Logo.png" alt="">
                                </td>
                                <td>
                                    <p class="profile-name">Songco Dental and Medical Clinic</p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                <a href="logout.php" ><input type="button" value="Log out" class="logout-btn btn-primary-soft btn"></a>
                                <br><br>
                                    <?php
if (isset($_SESSION['temporary_admin']) && $_SESSION['temporary_admin']) {
    echo '<a href="switch_back_to_dentist.php"><input type="button" value="Go Back to Dentist View" class="btn-primary-soft btn"></a>';
}
?>    
                            </td>
                            </tr>
                    </table>
                    </td>
                
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-dashbord" >
                        <a href="dashboard.php" class="non-style-link-menu"><div><p class="menu-text">Dashboard</p></a></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor menu-active menu-icon-doctor-active">
                        <a href="dentist.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Dentists</p></a></div>
                    </td>
                </tr>
               
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-schedule">
                        <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Sessions</p></div></a>
                    </td>
                </tr>
                
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appointment">
                        <a href="appointment.php" class="non-style-link-menu">
                            <div>
                                <p class="menu-text">Appointment</p>
                            </div>
                        </a>
                    </td>
                </tr>
                
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-patient">
                        <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Patients</p></a></div>
                    </td>
                </tr>

                <tr class="menu-row">
                    <td class="menu-btn menu-icon-calendar">
                        <a href="calendar/calendar.php" class="non-style-link-menu">
                            <div>
                                <p class="menu-text">Calendar</p>
                            </div>
                        </a>
                    </td>
                </tr>

            </table>
            </table>
        </div>
        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0; margin:0; padding:0; margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="dentist.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px; padding-bottom:11px; margin-left:20px; width:125px"><font class="tn-in-text">Back</font></button></a>
                    </td>
                    <td>
                        <form action="" method="post" class="header-search">
                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Search Dentist name or Email" list="doctors">&nbsp;&nbsp;
                            <?php
                                echo '<datalist id="doctors">';
                                $list11 = $database->query("SELECT docname, docemail FROM doctor;");
                                for ($y = 0; $y < $list11->num_rows; $y++) {
                                    $row00 = $list11->fetch_assoc();
                                    $d = $row00["docname"];
                                    $c = $row00["docemail"];
                                    echo "<option value='$d'><br/>";
                                    echo "<option value='$c'><br/>";
                                }
                                echo '</datalist>';
                            ?>
                            <input type="submit" value="Search" class="login-btn btn-primary btn" style="padding-left: 25px; padding-right: 25px; padding-top: 10px; padding-bottom: 10px;">
                        </form>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px; color: rgb(119, 119, 119); padding: 0; margin: 0; text-align: right;">Today's Date</p>
                        <p class="heading-sub12" style="padding: 0; margin: 0;">
                            <?php 
                            date_default_timezone_set('Asia/Kolkata');
                            $date = date('Y-m-d');
                            echo $date;
                            ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex; justify-content: center; align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px; font-size:18px; color:rgb(49, 49, 49)">All Dentists</p>
                    </td>
                </tr>
                <?php
                    if ($_POST) {
                        $keyword = $_POST["search"];
                        $sqlmain = "SELECT * FROM doctor WHERE docemail='$keyword' OR docname='$keyword' OR docname LIKE '$keyword%' OR docname LIKE '%$keyword' OR docname LIKE '%$keyword%'";
                    } else {
                        $sqlmain = "SELECT * FROM doctor ORDER BY status DESC, docname ASC";
                    }
                ?>
                <tr>
                    <td colspan="4">
                        <center>
                            <div class="abc scroll">
                                <table width="93%" class="sub-table scrolldown" border="0">
                                    <thead>
                                        <tr>
                                            <th class="table-headin">Dentist Name</th>
                                            <th class="table-headin">Email</th>
                                            <th class="table-headin">Phone Number</th>
                                            <th class="table-headin">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $result = $database->query($sqlmain);
                                            if ($result->num_rows == 0) {
                                                echo '<tr><td colspan="5"><center>No dentists found.</center></td></tr>';
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                                    $docid = $row["docid"];
                                                    $name = $row["docname"];
                                                    $email = $row["docemail"];
                                                    $tele = $row["doctel"];
                                                    $status = $row["status"] == 'active' ? 'Active' : 'Inactive';
                                                    echo '<tr>
                                                        <td>&nbsp;' . htmlspecialchars($name) . '</td>
                                                        <td>' . htmlspecialchars($email) . '</td>
                                                        <td>' . htmlspecialchars($tele) . '</td>
                                                        <td>' . $status . '</td>
                                                        <td>
                                                            <div style="display:flex;justify-content: center;">
                                                                &nbsp;&nbsp;&nbsp;
                                                                <br><br>
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
        </div>
    </div>
</body>
</html>