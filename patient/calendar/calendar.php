<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../Media/white-icon/white-ToothTrackr_Logo.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js"></script>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../../css/calendar.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/animations.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <title>Calendar - ToothTrackr</title>
    <link rel="icon" href="../../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        /* Notification styles */
        .notification-container {
            position: relative;
            display: flex;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        
        .notification-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 300px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            height: 500px;
            margin-top: 500px;
        }
        
        .notification-dropdown.show {
            display: block;
        }
        
        .notification-header {
            padding: 12px 16px;
            background-color: #f1f7fe;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        
        .notification-item.unread {
            background-color: #f1f7fe;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .notification-time {
            font-size: 12px;
            color: #777;
        }
        
        .mark-all-read {
            color: #3a86ff;
            cursor: pointer;
            font-size: 14px;
        }
        
        .no-notifications {
            padding: 16px;
            text-align: center;
            color: #777;
        }
        .select-dentist-message {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 300px;
            background-color: #f9f9f9;
            border: 1px dashed #ccc;
            border-radius: 8px;
            margin: 20px 0;
        }

        .select-dentist-message p {
            font-size: 18px;
            color: #666;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>

<body>
    <?php
    date_default_timezone_set('Asia/Singapore');
    session_start();

    if (isset($_SESSION["user"])) {
        if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'p') {
            header("location: ../login.php");
        } else {
            $useremail = $_SESSION["user"];
        }
    } else {
        header("location: ../login.php");
    }

    include("../../connection.php");
    $userrow = $database->query("select * from patient where pemail='$useremail'");
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["pid"];
    $username = $userfetch["pname"];

    // Get notification count
    $unreadCount = $database->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = '$userid' AND user_type = 'p' AND is_read = 0");
    $unreadCount = $unreadCount->fetch_assoc()['count'];

    // Get notifications
    $notifications = $database->query("SELECT * FROM notifications WHERE user_id = '$userid' AND user_type = 'p' ORDER BY created_at DESC");

    $procedures = $database->query("SELECT * FROM procedures");
    $procedure_options = '';
    while ($procedure = $procedures->fetch_assoc()) {
        $procedure_options .= '<option value="' . $procedure['procedure_id'] . '">' . $procedure['procedure_name'] . '</option>';
    }

    $doctorrow = $database->query("select * from doctor where status='active';");
    $appointmentrow = $database->query("select * from appointment where status='booking' AND pid='$userid';");
    $schedulerow = $database->query("select * from appointment where status='appointment' AND pid='$userid';");

    $results_per_page = 10;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    } else {
        $page = 1;
    }
    $start_from = ($page - 1) * $results_per_page;
    $search = "";
    $sort_param = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $sort_order = ($sort_param === 'oldest') ? 'DESC' : 'ASC';

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $query = "SELECT * FROM doctor WHERE status='active' AND (docname LIKE '%$search%' OR docemail LIKE '%$search%' OR doctel LIKE '%$search%') ORDER BY docname $sort_order LIMIT $start_from, $results_per_page";
        $count_query = "SELECT COUNT(*) as total FROM doctor WHERE status='active' AND (docname LIKE '%$search%' OR docemail LIKE '%$search%' OR doctel LIKE '%$search%')";
    } else {
        $query = "SELECT * FROM doctor WHERE status='active' ORDER BY docname $sort_order LIMIT $start_from, $results_per_page";
        $count_query = "SELECT COUNT(*) as total FROM doctor WHERE status='active'";
    }

    $result = $database->query($query);
    $count_result = $database->query($count_query);
    $count_row = $count_result->fetch_assoc();
    $total_pages = ceil($count_row['total'] / $results_per_page);

    $today = date('Y-m-d');
    $currentMonth = date('F');
    $currentYear = date('Y');
    $daysInMonth = date('t');
    $firstDayOfMonth = date('N', strtotime("$currentYear-" . date('m') . "-01"));
    $currentDay = date('j');

    $doctors = $database->query("SELECT docid, docname FROM doctor");
    $doctor_options = '';
    while ($doctor = $doctors->fetch_assoc()) {
        $doctor_options .= '<option value="' . $doctor['docid'] . '">' . $doctor['docname'] . '</option>';
    }
    ?>
    <div class="nav-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="../../Media/Icon/ToothTrackr/ToothTrackr.png" alt="ToothTrackr Logo">
            </div>

            <div class="user-profile">
                <div class="profile-image">
                    <?php
                    $profile_pic = isset($userfetch['profile_pic']) ? $userfetch['profile_pic'] : '../Media/Icon/Blue/profile.png';
                    ?>
                    <img src="../../<?php echo $profile_pic; ?>" alt="Profile" class="profile-img">
                </div>
                <h3 class="profile-name"><?php echo substr($username, 0, 25) ?></h3>
                <p style="color: #777; margin: 0; font-size: 14px; text-align: center;">
                    <?php echo substr($useremail, 0, 30) ?>
                </p>
            </div>

            <div class="nav-menu">
                <a href="../dashboard.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/home.png" alt="Home" class="nav-icon">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="../profile.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/profile.png" alt="Profile" class="nav-icon">
                    <span class="nav-label">Profile</span>
                </a>
                <a href="../dentist.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/dentist.png" alt="Dentist" class="nav-icon">
                    <span class="nav-label">Dentist</span>
                </a>
                <a href="calendar.php" class="nav-item active">
                    <img src="../../Media/Icon/Blue/calendar.png" alt="Calendar" class="nav-icon">
                    <span class="nav-label">Calendar</span>
                </a>
                <a href="../my_booking.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/booking.png" alt="Bookings" class="nav-icon">
                    <span class="nav-label">My Booking</span>
                </a>
                <a href="../my_appointment.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/appointment.png" alt="Appointments" class="nav-icon">
                    <span class="nav-label">My Appointment</span>
                </a>
                <a href="../settings.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/settings.png" alt="Settings" class="nav-icon">
                    <span class="nav-label">Settings</span>
                </a>
            </div>

            <div class="log-out">
                <a href="../logout.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/logout.png" alt="Log Out" class="nav-icon">
                    <span class="nav-label">Log Out</span>
                </a>
            </div>
        </div>
        <div class="content-area">
            <div class="content">
                <div class="main-section">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="choose_dentist">Book an Appointment:</label>
                                    <div class="select-wrapper">
                                        <select class="form-dentist" id="choose_dentist">
                                            <option value="">Select a Dentist</option>
                                            <?php echo $doctor_options; ?>
                                        </select>
                                    </div>
                                </div>
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="event_entry_modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-md" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalLabel">Create Appointment</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">x</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="save_event.php" method="POST">
                                        <div class="form-group">
                                            <label for="event_name">Event Name</label>
                                            <input type="text" name="event_name" id="event_name" class="form-control" placeholder="Enter your event name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="procedure">Procedure</label>
                                            <select class="form-control" id="procedure" name="procedure" onchange="showProcedureDescription(this)">
                                                <?php
                                                $procedures = $database->query("SELECT * FROM procedures");
                                                while ($procedure = $procedures->fetch_assoc()) {
                                                    echo '<option value="' . $procedure['procedure_id'] . '" 
                            title="' . htmlspecialchars($procedure['description']) . '"
                            data-description="' . htmlspecialchars($procedure['description']) . '">'
                                                        . $procedure['procedure_name'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <div id="procedure-description" class="alert alert-info mt-2" style="display: none;">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_name">Patient Name</label>
                                            <input type="text" name="patient_name" id="patient_name" class="form-control" value="<?php echo $username; ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="appointment_date">Date</label>
                                            <input type="text" name="appointment_date" id="appointment_date" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="appointment_time">Time</label>
                                            <select class="form-control" id="appointment_time" name="appointment_time">
                                                <option value="09:00:00"> 9:00 AM - 9:30 AM</option>
                                                <option value="09:30:00"> 9:30 AM - 10:00 AM</option>
                                                <option value="10:00:00"> 10:00 AM - 10:30 AM</option>
                                                <option value="10:30:00"> 10:30 AM - 11:00 AM</option>
                                                <option value="11:00:00"> 11:00 AM - 11:30 AM</option>
                                                <option value="11:30:00"> 11:30 AM - 12:00 PM</option>
                                                <option value="13:00:00"> 1:00 PM - 1:30 PM</option>
                                                <option value="13:30:00"> 1:30 PM - 2:00 PM</option>
                                                <option value="14:00:00"> 2:00 PM - 2:30 PM</option>
                                                <option value="14:30:00"> 2:30 PM - 3:00 PM</option>
                                                <option value="16:00:00"> 4:00 PM - 4:30 PM</option>
                                                <option value="16:30:00"> 4:30 PM - 5:00 PM</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="docid" id="docid" value="">
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Confirm</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                                    <img src="../../Media/Icon/Blue/folder.png" alt="Notifications Icon">
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
                            <a href="../my_booking.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php echo $appointmentrow->num_rows ?></h1>
                                        <p class="stat-label">My Bookings</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../../Media/Icon/Blue/booking.png" alt="Booking Icon">
                                    </div>
                                </div>
                            </a>

                            <a href="../my_appointment.php" class="stat-box-link">
                                <div class="stat-box">
                                    <div class="stat-content">
                                        <h1 class="stat-number"><?php
                                        $appointmentCount = $schedulerow->num_rows;
                                        echo $appointmentCount;
                                        ?></h1>
                                        <p class="stat-label">My Appointments</p>
                                    </div>
                                    <div class="stat-icon">
                                        <img src="../../Media/Icon/Blue/appointment.png" alt="Appointment Icon">
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
                                    <a href="calendar.php" class="schedule-btn">Schedule an appointment</a>
                                </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function () {
                    // Don't initialize calendar by default
                    // Instead, show a message prompting to select a dentist
                    $('#calendar').html('<div class="select-dentist-message"><p>Please select a dentist to view available appointment slots.</p></div>');
                    
                    // Event listener for selecting a dentist
                    $('#choose_dentist').change(function () {
                        var dentistId = $(this).val();
                        if (dentistId) {
                            $('#docid').val(dentistId);
                            // Clear the message and initialize calendar
                            $('#calendar').html('');
                            display_events(dentistId);
                        } else {
                            // If "Select a Dentist" is chosen, show message again
                            $('#calendar').html('<div class="select-dentist-message"><p>Please select a dentist to view available appointment slots.</p></div>');
                        }
                    });

                    // Bind the "Confirm" button click event
                    $('#event_entry_modal').on('click', '.btn-primary', function () {
                        save_event();
                    });
                });

                function display_events(dentistId) {
                    var events = new Array();
                    var bookedTimes = [];

                    function fetchBookedTimes(date) {
                        $.ajax({
                            url: 'fetch_booked_times.php',
                            data: { dentist_id: dentistId, date: date },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status) {
                                    var bookedTimes = response.booked_times;
                                    updateTimeDropdown(bookedTimes);
                                }
                            },
                            error: function (xhr, status) {
                                alert("Error fetching booked times.");
                            }
                        });
                    }

                    function updateTimeDropdown(bookedTimes) {
                        var timeSlots = [
                            { time: "09:00:00", label: "9:00 AM  -  9:30 AM" },
                            { time: "09:30:00", label: "9:30 AM  - 10:00 AM" },
                            { time: "10:00:00", label: "10:00 AM  - 10:30 AM" },
                            { time: "10:30:00", label: "10:30 AM  - 11:00 AM" },
                            { time: "11:00:00", label: "11:00 AM  - 11:30 AM" },
                            { time: "11:30:00", label: "11:30 AM  - 12:00 PM" },
                            { time: "13:00:00", label: "1:00 PM  -  1:30 PM" },
                            { time: "13:30:00", label: "1:30 PM  -  2:00 PM" },
                            { time: "14:00:00", label: "2:00 PM  -  2:30 PM" },
                            { time: "14:30:00", label: "2:30 PM  -  3:00 PM" },
                            { time: "16:00:00", label: "4:00 PM  -  4:30 PM" },
                            { time: "16:30:00", label: "4:30 PM  -  5:00 PM" }
                        ];

                        $('#appointment_time').empty();

                        $.each(timeSlots, function (index, slot) {
                            var option = $("<option></option>").val(slot.time).text(slot.label);

                            if (bookedTimes[slot.time] && bookedTimes[slot.time] >= 3) {
                                option.attr("disabled", "disabled");
                                option.css("background-color", "#F46E34");
                            }

                            $('#appointment_time').append(option);
                        });
                    }

                    $.ajax({
                        url: 'display_event.php',
                        data: { dentist_id: dentistId },
                        dataType: 'json',
                        success: function (response) {
                            var result = response.data;
                            $.each(result, function (i, item) {
                                var eventColor = (item.status === 'appointment') ? '#6BB663' : item.color;
                                events.push({
                                    event_id: result[i].appointment_id,
                                    title: result[i].title,
                                    start: result[i].start,
                                    end: result[i].end,
                                    color: eventColor,
                                    url: result[i].url,
                                    status: result[i].status,
                                    procedure_name: item.procedure_name,
                                    patient_name: item.patient_name,
                                    dentist_name: item.dentist_name
                                });
                            });

                            if ($('#calendar').fullCalendar('getView')) {
                                $('#calendar').fullCalendar('destroy');
                            }

                            $('#calendar').fullCalendar({
                                defaultView: 'month',
                                timeZone: 'local',
                                fixedWeekCount: false,
                                editable: true,
                                selectable: true,
                                selectHelper: true,
                                select: function (start, end) {
                                    var selectedDate = moment(start).format('YYYY-MM-DD');
                                    $('#appointment_date').val(selectedDate);
                                    $('#event_name').val("Patient's Choice");
                                    var today = moment().format('YYYY-MM-DD');
                                    var maxAllowedDate = moment().add(2, 'months').format('YYYY-MM-DD');
                                    var dayOfWeek = moment(start).day();

                                    if (selectedDate < today) {
                                        alert("You cannot book appointments for past dates!");
                                        return;
                                    }

                                    if (selectedDate > maxAllowedDate) {
                                        alert("You can only book appointments within 2 months from today!");
                                        return;
                                    }

                                    if (dayOfWeek === 4) {
                                        alert("No Service during Thursdays.");
                                        return;
                                    }

                                    fetchBookedTimes(selectedDate);
                                    $('#event_entry_modal').modal('show');
                                },
                                events: events,
                                eventRender: function (event, element, view) {
                                    element.on('click', function () {
                                        $('#modalProcedureName').text(event.procedure_name || 'N/A');
                                        $('#modalPatientName').text(event.patient_name || 'N/A');
                                        $('#modalDentistName').text(event.dentist_name || 'N/A');
                                        $('#modalDate').text(event.start ? new Date(event.start).toLocaleDateString() : 'N/A');
                                        var startTime = moment(event.start).format('h:mm A');
                                        var endTime = moment(event.end).format('h:mm A');
                                        $('#modalTime').text(startTime);
                                        $('#modalStatus').text(
                                            event.status === 'appointment'
                                                ? 'Appointment Confirmed'
                                                : event.status === 'completed'
                                                    ? 'Completed'
                                                    : 'Booking'
                                        );

                                        $('#appointmentModal').on('show.bs.modal', function () {
                                            $('#cancel-appointment').hide();
                                            if (event.status === 'booking') {
                                                $('#confirm-booking').show();
                                                $('#cancel-appointment').show();
                                            } else if (event.status === 'appointment') {
                                                $('#confirm-booking').hide();
                                                $('#cancel-appointment').show();
                                            } else if (event.status === 'completed') {
                                                $('#confirm-booking').hide();
                                                $('#cancel-appointment').hide();
                                            }
                                        });

                                        $('#cancel-appointment').off('click').on('click', function() {
                                            var confirmMessage = (event.status === 'booking')
                                                ? "Are you sure you want to cancel this booking?"
                                                : "Are you sure you want to cancel this appointment?";

                                            if (confirm(confirmMessage)) {
                                                $.ajax({
                                                    url: 'cancel_appointment.php',
                                                    type: 'POST',
                                                    data: { appoid: event.event_id },
                                                    dataType: 'json',
                                                    success: function (response) {
                                                        if (response && response.status) {
                                                            alert(event.status === 'booking'
                                                                ? "Booking cancelled successfully."
                                                                : "Appointment cancelled successfully.");
                                                            // Close the modal first
                                                            $('#appointmentModal').modal('hide');
                                                            // Then reload the page
                                                            location.reload();
                                                        } else {
                                                            alert("Error: " + (response.msg || 'Unknown error occurred'));
                                                        }
                                                    },
                                                    error: function(xhr, status, error) {
                                                        try {
                                                            // Try to parse response if it's JSON
                                                            var response = JSON.parse(xhr.responseText);
                                                            alert("Error: " + (response.msg || error));
                                                        } catch (e) {
                                                            alert("Error: " + error);
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                        $('#appointmentModal').modal('show');
                                    });

                                    element.css('background-color', event.color);
                                },
                                dayRender: function (date, cell) {
                                    if (date.day() === 4) {
                                        cell.css("background-color", "#FFF2F2");
                                    }

                                    var today = moment().startOf('day');
                                    var maxAllowedDate = moment().add(2, 'months').startOf('day');
                                    if (date < today || date > maxAllowedDate) {
                                        cell.css("background-color", "#fff2f2");
                                        cell.css("pointer-events", "none");
                                    }
                                }
                            });
                        },
                        error: function (xhr, status) {
                            alert("Error fetching events.");
                        }
                    });
                }

                function save_event() {
                    var event_name = $("#event_name").val();
                    var procedure = $("#procedure").val();
                    var patient_name = $("#patient_name").val();
                    var appointment_date = $("#appointment_date").val();
                    var appointment_time = $("#appointment_time").val();
                    var docid = $('#docid').val();

                    if (!event_name || !procedure || !appointment_date || !appointment_time || !docid) {
                        alert("Please enter all required details.");
                        return false;
                    }

                    var submitButton = $('.btn-primary');
                    submitButton.prop('disabled', true);
                    submitButton.text('Submitting...');

                    $.ajax({
                        url: "save_event.php",
                        type: "POST",
                        dataType: 'json',
                        data: {
                            event_name: event_name,
                            procedure: procedure,
                            patient_name: patient_name,
                            appointment_date: appointment_date,
                            appointment_time: appointment_time,
                            docid: docid
                        },
                        success: function (response) {
                            $('#event_entry_modal').modal('hide');
                            if (response.status === true) {
                                alert(response.msg);
                                location.reload();
                            } else {
                                alert(response.msg);
                            }
                        },
                        error: function () {
                            console.log('AJAX error');
                            alert('Error saving event');
                        },
                        complete: function () {
                            submitButton.prop('disabled', false);
                            submitButton.text('Confirm');
                        }
                    });
                }

                function showProcedureDescription(select) {
                    var description = select.options[select.selectedIndex].getAttribute('data-description');
                    var descDiv = document.getElementById('procedure-description');

                    if (description) {
                        descDiv.innerHTML = description;
                        descDiv.style.display = 'block';
                    } else {
                        descDiv.style.display = 'none';
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
                    $.ajax({
                        url: '../mark_notification_read.php',
                        method: 'POST',
                        data: { id: notificationId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                element.classList.remove('unread');
                                
                                // Count remaining unread notifications
                                const unreadCount = document.querySelectorAll('.notification-item.unread').length;
                                updateNotificationCount(unreadCount);
                            }
                        },
                        error: function(xhr) {
                            console.error("Error marking notification as read:", xhr.responseText);
                        }
                    });
                }

                function markAllAsRead() {
                    $.ajax({
                        url: '../mark_all_notifications_read.php',
                        method: 'POST',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Remove unread class from all notifications
                                document.querySelectorAll('.notification-item.unread').forEach(item => {
                                    item.classList.remove('unread');
                                });
                                
                                // Update count to zero
                                updateNotificationCount(0);
                            }
                        },
                        error: function(xhr) {
                            console.error("Error marking all notifications as read:", xhr.responseText);
                        }
                    });
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var firstOption = document.querySelector('#procedure option');
                    if (firstOption) {
                        showProcedureDescription(document.getElementById('procedure'));
                    }
                    $('[data-toggle="tooltip"]').tooltip();

                    // Notification dropdown toggle
                    const notificationContainer = document.getElementById('notificationContainer');
                    const notificationDropdown = document.getElementById('notificationDropdown');

                    if (notificationContainer && notificationDropdown) {
                        notificationContainer.addEventListener('click', function(e) {
                            e.stopPropagation();
                            notificationDropdown.classList.toggle('show');
                        });
                        
                        // Close dropdown when
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        notificationDropdown.classList.remove('show');
    });
}

function markAsRead(notificationId, element) {
    $.ajax({
        url: 'mark_notification_read.php',
        method: 'POST',
        data: { id: notificationId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                element.classList.remove('unread');
                // Update badge count
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent);
                    if (currentCount > 1) {
                        badge.textContent = currentCount - 1;
                    } else {
                        badge.remove();
                    }
                }
                // Update the stat number
                const statNumber = document.querySelector('#notificationContainer .stat-number');
                if (statNumber) {
                    statNumber.textContent = parseInt(statNumber.textContent) - 1;
                }
            }
        },
        error: function(xhr) {
            console.error("Error marking notification as read:", xhr.responseText);
        }
    });
}
function markAllAsRead() {
    $.ajax({
        url: 'mark_all_notifications_read.php',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Remove unread class from all notifications
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                // Remove badge
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.remove();
                }
                // Update the stat number
                const statNumber = document.querySelector('#notificationContainer .stat-number');
                if (statNumber) {
                    statNumber.textContent = '0';
                }
            }
        },
        error: function(xhr) {
            console.error("Error marking all notifications as read:", xhr.responseText);
        }
    });
}
                });
            </script>
        </div>
        <div class="modal fade" id="appointmentModal" tabindex="-1" role="dialog" aria-labelledby="appointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appointmentModalLabel">Appointment Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Procedure:</strong> <span id="modalProcedureName"></span></p>
                        <p><strong>Patient:</strong> <span id="modalPatientName"></span></p>
                        <p><strong>Dentist:</strong> <span id="modalDentistName"></span></p>
                        <p><strong>Date:</strong> <span id="modalDate"></span></p>
                        <p><strong>Time:</strong> <span id="modalTime"></span></p>
                        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="cancel-appointment">Cancel</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>