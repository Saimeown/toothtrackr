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
    <title>My Appointment - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .filter-date-input {
            padding: 6px 12px;
            border: 1px solid #303030;
            border-radius: 18px;
            font-size: 14px;
        }

        .filter-btn {
            padding: 8px 16px;
            background-color: #84b6e4;
            color: white;
            border: none;
            border-radius: 18px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .filter-btn:hover {
            background-color: #98c0e4;
        }

        .filter-clear-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background-color: #f5f5f5;
            color: #777;
            border-radius: 50%;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s;
        }

        .filter-clear-btn:hover {
            background-color: #e0e0e0;
            color: #333;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-upcoming {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .success-message {
            color: green;
            font-weight: bold;
        }
        .error-message {
            color: red;
            font-weight: bold;
        }
    </style>
    <?php
    date_default_timezone_set('Asia/Singapore');
    session_start();

    if (isset($_SESSION["user"])) {
        if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'p') {
            header("location: login.php");
        } else {
            $useremail = $_SESSION["user"];
        }
    } else {
        header("location: login.php");
    }

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
                <a href="my_appointment.php" class="nav-item active">
                    <img src="../Media/Icon/Blue/appointment.png" alt="Appointments" class="nav-icon">
                    <span class="nav-label">My Appointment</span>
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
                    <!-- search bar -->
                    <div class="search-container">
                        <form action="" method="GET" style="display: flex; width: 100%;">
                            <input type="search" name="search" id="searchInput" class="search-input"
                                placeholder="Search by dentist or procedure name"
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <?php if (isset($_GET['search']) && $_GET['search'] != ""): ?>
                                <button type="button" class="clear-btn" onclick="clearSearch()">×</button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- header section -->
                    <div class="announcements-header">
                        <h3 class="announcements-title">My Appointments (<?php echo $count_row['total']; ?>)</h3>
                        <div class="announcement-filters">
                            <form action="" method="POST" style="display: flex; align-items: center; gap: 8px;">
                                <input type="date" name="appodate" id="date" class="filter-date-input"
                                    value="<?php echo isset($_POST['appodate']) ? htmlspecialchars($_POST['appodate']) : ''; ?>">
                                <button type="submit" name="filter" class="filter-btn">
                                    Filter
                                </button>
                                <?php if (isset($_POST['appodate']) && $_POST['appodate'] != ""): ?>
                                    <a href="my_appointment.php" class="filter-clear-btn">×</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Profile</th>
                                        <th>Dentist</th>
                                        <th>Procedure</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    date_default_timezone_set('Asia/Kolkata');
                                    $today = date('Y-m-d');
                                    
                                    while ($row = $result->fetch_assoc()): 
                                        $appodate = $row["appodate"];
                                        $status = $row["status"];
                                        
                                        // Determine status badge
                                        if ($status == 'completed') {
                                            $statusClass = 'status-completed';
                                            $statusText = 'Completed';
                                        } elseif ($appodate < $today) {
                                            $statusClass = 'status-completed';
                                            $statusText = 'Completed';
                                        } else {
                                            $statusClass = 'status-upcoming';
                                            $statusText = 'Upcoming';
                                        }
                                    ?>
                                        <tr>
                                            <!-- Profile Column -->
                                            <td>
                                                <?php
                                                if (!empty($row['photo'])) {
                                                    $photo = "../admin/uploads/" . $row['photo'];
                                                } else {
                                                    $photo = "../Media/Icon/Blue/dentist.png";
                                                }
                                                ?>
                                                <img src="<?php echo $photo; ?>" alt="<?php echo $row['docname']; ?>"
                                                    class="profile-img-small">
                                            </td>

                                            <!-- Dentist Name Column -->
                                            <td>
                                                <div class="cell-text"><?php echo $row['docname']; ?></div>
                                            </td>

                                            <!-- Procedure Column -->
                                            <td>
                                                <div class="cell-text"><?php echo $row['procedure_name']; ?></div>
                                            </td>

                                            <!-- Date & Time Column -->
                                            <td>
                                                <div class="cell-text">
                                                    <?php echo date('F j, Y', strtotime($row['appodate'])); ?></div>
                                                <div class="cell-text" style="color: #666; font-size: 0.9em;">
                                                    <?php echo date('g:i A', strtotime($row['appointment_time'])); ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Status Column -->
                                            <td>
                                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>

                                            <!-- Actions Column -->
                                            <td>
                                                <?php if ($status == 'appointment' && $appodate >= $today): ?>
                                                    <a href="?action=cancel&id=<?php echo $row['appoid']; ?>&doc=<?php echo urlencode($row['docname']); ?>"
                                                        class="non-style-link">
                                                        <center><button class="action-btn remove-btn">Cancel</button></center>
                                                    </a>
                                                <?php else: ?>
                                                    <center><button class="action-btn done-btn" disabled>Completed</button></center>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination">
                            <?php
                            $searchParam = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';

                            // Previous link
                            if ($page > 1) {
                                echo '<a href="?page=' . ($page - 1) . $searchParam . '">&laquo; Previous</a>';
                            }

                            // Page links
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<a href="?page=' . $i . $searchParam . '"' . ($i == $page ? ' class="active"' : '') . '>' . $i . '</a>';
                            }

                            // Next link
                            if ($page < $total_pages) {
                                echo '<a href="?page=' . ($page + 1) . $searchParam . '">Next &raquo;</a>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <p>No appointments found!</p>
                            <a class="non-style-link" href="calendar/calendar.php">
                                <button class="login-btn btn-primary-soft btn" style="margin-top: 20px;">Book a
                                    Dentist</button>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- right sidebar section -->
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
    if ($_GET && isset($_GET['action'])) {
        $id = $_GET["id"];
        $action = $_GET["action"];

        if ($action == 'cancel') {
            $docname = $_GET["doc"];

            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2>Confirm Cancellation</h2>
                        <a class="close" href="my_appointment.php">&times;</a>
                        <div class="content">
                            Are you sure you want to cancel this appointment?<br><br>
                            Dentist: <b>' . htmlspecialchars(substr($docname, 0, 40)) . '</b><br><br>
                        </div>
                        <div style="display: flex;justify-content: center;">
                            <a href="cancel_appointment.php?id=' . $id . '&source=patient" class="non-style-link">
                                <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                    <font class="tn-in-text">Yes, Cancel</font>
                                </button>
                            </a>&nbsp;&nbsp;&nbsp;
                            <a href="my_appointment.php" class="non-style-link">
                                <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                    <font class="tn-in-text">No, Go Back</font>
                                </button>
                            </a>
                        </div>
                    </center>
                </div>
            </div>
            ';
        }
    }
    
    if (!empty($statusMessage)) {
        echo '<div id="statusPopup" class="popup" style="display: none;">
            <div class="popup-content">
                <span id="closePopup" class="close">&times;</span>
                <p id="popupMessage" class="' . $messageClass . '">' . $statusMessage . '</p>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const popup = document.getElementById("statusPopup");
                popup.style.display = "flex";
                
                document.getElementById("closePopup").onclick = function() {
                    popup.style.display = "none";
                    window.location.href = "my_appointment.php"; // Remove query params
                };
                
                window.onclick = function(event) {
                    if (event.target == popup) {
                        popup.style.display = "none";
                        window.location.href = "my_appointment.php"; // Remove query params
                    }
                };
            });
        </script>';
    }
    ?>

    <script>
        // Function to clear search and redirect
        function clearSearch() {
            window.location.href = 'my_appointment.php';
        }

        // Function to clear date filter
        function clearDateFilter() {
            window.location.href = 'my_appointment.php';
        }

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