<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/loading.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Alfa+Slab+One&family=Architects+Daughter&family=Archivo+Black&family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Mulish:ital,wght@0,200..1000;1,200..1000&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <title>Dashboard - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .announcement-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background-color: white;
            border: 3px solid #84b6e4;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>


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


//import database
include("../connection.php");
$userrow = $database->query("select * from patient where pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];


$patientrow = $database->query("select * from patient;");
$doctorrow = $database->query("select * from doctor where status='active';");
$appointmentrow = $database->query("select * from appointment where status='booking' AND pid='$userid';");
$schedulerow = $database->query("select * from appointment where status='appointment' AND pid='$userid';");


$today = date('Y-m-d');
$currentMonth = date('F');
$currentYear = date('Y');
$daysInMonth = date('t');
$firstDayOfMonth = date('N', strtotime("$currentYear-" . date('m') . "-01"));
$currentDay = date('j');


// sort order for announcements
$sortOrder = isset($_GET['sort']) && $_GET['sort'] === 'oldest' ? 'ASC' : 'DESC';
?>


<body>
    <div class="main-container">
        <!-- sidebar -->
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
                <a href="dashboard.php" class="nav-item active">
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
            <!-- main content -->
            <div class="content">
                <div class="main-section">
                    <!-- search bar -->
                    <div class="search-container">
                        <form id="announcementSearchForm" style="display: flex; width: 100%;">
                            <input type="search" name="search" id="announcementSearch" class="search-input"
                                placeholder="Search announcements"
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="button" class="clear-btn">×</button>
                        </form>
                    </div>


                    <!-- announcements header -->
                    <div class="announcements-header">
                        <h3 class="announcements-title">Announcements</h3>
                        <div class="announcement-filters">
                            <?php
                            $currentSort = isset($_GET['sort']) && $_GET['sort'] === 'oldest' ? 'oldest' : 'newest';
                            $searchTermEncoded = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                            ?>
                            <a href="?sort=newest<?php echo $searchTermEncoded; ?>"
                                class="filter-btn newest-btn <?php echo $currentSort === 'newest' ? 'active' : 'inactive'; ?>">
                                Newest
                            </a>


                            <a href="?sort=oldest<?php echo $searchTermEncoded; ?>"
                                class="filter-btn oldest-btn <?php echo $currentSort === 'oldest' ? 'active' : 'inactive'; ?>">
                                Oldest
                            </a>
                        </div>
                    </div>


                    <!-- announcements container -->
                    <div class="announcements">
                        <div class="announcements-content" id="announcementsContent">
                            <?php
                            // Get search term if submitted
                            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
                            $sortOrder = isset($_GET['sort']) && $_GET['sort'] === 'oldest' ? 'ASC' : 'DESC';


                            // Base query
                            $query = "
                                SELECT post_dentist.*, doctor.docname, doctor.photo as docphoto
                                FROM post_dentist
                                LEFT JOIN doctor ON post_dentist.docid = doctor.docid
                                UNION ALL
                                SELECT post_admin.*, 'Admin' AS docname, 'SDMC Logo.png' AS docphoto
                                FROM post_admin
                                WHERE post_admin.aemail = ''
                            ";


                            // Add search condition if term exists
                            if (!empty($searchTerm)) {
                                $searchTerm = $database->real_escape_string($searchTerm);
                                $query = "
                                    SELECT * FROM (
                                        $query
                                    ) AS combined_posts
                                    WHERE title LIKE '%$searchTerm%' OR content LIKE '%$searchTerm%'
                                ";
                            }


                            // Add sorting and limit
                            $query .= " ORDER BY created_at $sortOrder LIMIT 6";


                            $result = $database->query($query);


                            if ($result->num_rows > 0) {
                                // Loop through the posts and display them
                                while ($post = $result->fetch_assoc()) {
                                    $content = htmlspecialchars($post['content']);
                                    $isLong = strlen($content) > 400;
                                    $shortContent = $isLong ? substr($content, 0, 255) . '...' : $content;
                                   
                                    // Determine the photo path
                                    $photoPath = $post['docname'] === 'Admin'
                                        ? '../Media/Icon/SDMC Logo.png'
                                        : '../admin/uploads/' . $post['docphoto'];


                                    echo '<div class="announcement-item">';
                                    echo '<div class="announcement-header">';
                                    echo '<div class="clinic-logo"><img src="' . $photoPath . '" alt="Profile" class="announcement-photo"></div>';
                                    echo '<div class="clinic-info">';
                                    echo '<h4 class="clinic-name">' . htmlspecialchars($post['title']) . '</h4>';
                                    echo '<p class="clinic-date">Posted by: ' . htmlspecialchars($post['docname']) . ' on ' . date('M d, Y', strtotime($post['created_at'])) . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '<div class="announcement-content">' . nl2br($shortContent) . '</div>';
                                    echo '<div class="full-content">' . nl2br($content) . '</div>';


                                    if ($isLong) {
                                        echo '<div class="announcement-footer">';
                                        echo '<button class="see-more-btn" onclick="toggleExpand(this)">See more...</button>';
                                        echo '</div>';
                                    }


                                    echo '</div>';
                                }
                            } else {
                                echo '<p>No announcements found' . (!empty($searchTerm) ? ' matching "' . htmlspecialchars($searchTerm) . '"' : '') . '.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>


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


    <script>
        // Script for clear button in search
        document.querySelector('.clear-btn').addEventListener('click', function () {
            document.querySelector('input[name="search"]').value = '';
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('announcementSearch');
            const searchForm = document.getElementById('announcementSearchForm');
            const clearBtn = document.querySelector('.clear-btn');


            // Clear search functionality
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                searchForm.submit();
            });
        });
    </script>


    <script>
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
    </script>
</body>


</html>

