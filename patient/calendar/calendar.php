<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../Media/white-icon/white-ToothTrackr_Logo.png" type="image/png">
    <!-- *Note: You must have internet connection on your laptop or pc other wise below code is not working -->
    <!-- CSS for full calender -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css" rel="stylesheet" />
    <!-- JS for jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <!-- JS for full calender -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js"></script>
    <!-- bootstrap css and js -->
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
-->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="../../css/calendar.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/animations.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <title>Calendar - ToothTrackr</title>
    <link rel="icon" href="../../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
</head>

<body>
    <?php
    date_default_timezone_set('Asia/Singapore');
    //learn from w3schools.com
    
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


    //import database
    include("../../connection.php");
    $userrow = $database->query("select * from patient where pemail='$useremail'");
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["pid"];
    $username = $userfetch["pname"];


    //echo $userid;
    //echo $username;
    
    $procedures = $database->query("SELECT * FROM procedures");
    $procedure_options = '';
    while ($procedure = $procedures->fetch_assoc()) {
        $procedure_options .= '<option value="' . $procedure['procedure_id'] . '">' . $procedure['procedure_name'] . '</option>';
    }

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

    // This is the key part that needs fixing - check if 'sort' parameter exists and equals 'oldest'
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

    // Calendar variables
    $today = date('Y-m-d');
    $currentMonth = date('F');
    $currentYear = date('Y');
    $daysInMonth = date('t');
    $firstDayOfMonth = date('N', strtotime("$currentYear-" . date('m') . "-01"));
    $currentDay = date('j');

    // Fetch doctors
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
                    $profile_pic = isset($userfetch['profile_pic']) ? $userfetch['profile_pic'] : '../Media/Icon/Blue/profile.png'
                    ;
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
                                <!--
                                <div class="legend">
                                    <div class="legend-item bookings">Bookings</div>
                                    <div class="legend-item appointments">Appointments</div>
                                    <div class="legend-item no-service">No Service</div>
                                    <div class="legend-item timeslot-taken">Timeslot Taken</div>
                                    <div class="legend-item completed">Completed</div>
                                </div>
                                 -->
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Start popup dialog box -->
                    <div class="modal fade" id="event_entry_modal" tabindex="-1" role="dialog"
                        aria-labelledby="modalLabel" aria-hidden="true">
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
                                            <input type="text" name="event_name" id="event_name" class="form-control"
                                                placeholder="Enter your event name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="procedure">Procedure</label>
                                            <select class="form-control" id="procedure" name="procedure"
                                                onchange="showProcedureDescription(this)">
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
                                            <div id="procedure-description" class="alert alert-info mt-2"
                                                style="display: none;">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="patient_name">Patient Name</label>
                                            <input type="text" name="patient_name" id="patient_name"
                                                class="form-control" value="<?php echo $username; ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="appointment_date">Date</label>
                                            <input type="text" name="appointment_date" id="appointment_date"
                                                class="form-control" readonly>
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
                                        <img src="../../Media/Icon/Blue/dentist.png" alt="Dentist Icon">
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
                                        <img src="../../Media/Icon/Blue/booking.png" alt="Booking Icon">
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
                        <!-- Color Guide -->
                        <div class="color-guide-container">
                            <div class="calendar-header">
                                <h3 class="color-guide-title">Color guide</h3>
                            </div>
                            <div class="color-legend">
                                <div class="color-item">
                                    <div class="color-circle" style="background-color: #F9C74F;"></div>
                                    <div class="color-label">Booking</div>
                                </div>
                                <div class="color-item">
                                    <div class="color-circle" style="background-color: #90EE90;"></div>
                                    <div class="color-label">Appointment</div>
                                </div>
                                <div class="color-item">
                                    <div class="color-circle" style="background-color: #F94144;"></div>
                                    <div class="color-label">No Service</div>
                                </div>
                                <div class="color-item">
                                    <div class="color-circle" style="background-color: #F9A15D;"></div>
                                    <div class="color-label">Timeslot Taken</div>
                                </div>
                                <div class="color-item">
                                    <div class="color-circle" style="background-color: #BBBBBB;"></div>
                                    <div class="color-label">Completed</div>
                                </div>
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

            <!-- End popup dialog box -->
            <script>
                $(document).ready(function () {
                    // Event listener for selecting a dentist
                    $('#choose_dentist').change(function () {
                        var dentistId = $(this).val();
                        if (dentistId) {
                            $('#docid').val(dentistId);  // Set the hidden docid field
                            display_events(dentistId);    // Fetch events for the selected dentist
                        } else {
                            alert("Please select a dentist.");
                        }
                    });

                    // Bind the "Confirm" button click event using jQuery
                    $('#event_entry_modal').on('click', '.btn-primary', function () {
                        save_event();
                    });
                });

                // AJAX function to fetch and display events
                // AJAX function to fetch and display events
                function display_events(dentistId) {
                    var events = new Array();
                    var bookedTimes = [];

                    // This function will fetch the booked times for the selected dentist and date
                    function fetchBookedTimes(date) {
                        $.ajax({
                            url: 'fetch_booked_times.php',
                            data: { dentist_id: $('#choose_dentist').val(), date: date },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status) {
                                    var bookedTimes = response.booked_times; // Array of booked times with counts
                                    updateTimeDropdown(bookedTimes); // Update the dropdown with booked times
                                }
                            },
                            error: function (xhr, status) {
                                alert("Error fetching booked times.");
                            }
                        });
                    }

                    // This function will update the time dropdown by disabling the booked slots
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

                        $('#appointment_time').empty(); // Clear existing options

                        $.each(timeSlots, function (index, slot) {
                            var option = $("<option></option>").val(slot.time).text(slot.label);

                            // Check if the timeslot has reached three bookings
                            if (bookedTimes[slot.time] && bookedTimes[slot.time] >= 3) {
                                option.attr("disabled", "disabled"); // Disable the option
                                option.css("background-color", "#F46E34"); // Highlight as "Timeslot Taken"
                            }

                            $('#appointment_time').append(option);
                        });
                    }

                    $.ajax({
                        url: 'display_event.php',
                        dataType: 'json',
                        success: function (response) {
                            var result = response.data;
                            $.each(result, function (i, item) {
                                var eventColor = (item.status === 'appointment') ? '#6BB663' : item.color; // Set color to blue if status is "appointment"
                                events.push({
                                    event_id: result[i].appointment_id,
                                    title: result[i].title,
                                    start: result[i].start,
                                    end: result[i].end,
                                    color: eventColor, // Set the color here
                                    url: result[i].url,
                                    status: result[i].status,
                                    procedure_name: item.procedure_name,  // Add procedure name
                                    patient_name: item.patient_name,      // Add patient name
                                    dentist_name: item.dentist_name       // Add dentist name
                                });
                            });

                            // Destroy the existing calendar before reinitializing it
                            if ($('#calendar').fullCalendar) {
                                $('#calendar').fullCalendar('destroy');
                            }

                            // Reinitialize the calendar with new events
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
                                        return; // Stop the function
                                    }

                                    if (selectedDate > maxAllowedDate) {
                                        alert("You can only book appointments within 2 months from today!");
                                        return; // Stop the function
                                    }

                                    if (dayOfWeek === 4) { // 4 represents Thursday
                                        alert("No Service during Thursdays.");
                                        return; // Stop function if the date is a Thursday
                                    }


                                    fetchBookedTimes(selectedDate);

                                    $('#event_entry_modal').modal('show');
                                },
                                events: events,
                                eventRender: function (event, element, view) {
                                    element.on('click', function () {
                                        // Fill modal fields with event data
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
                                            $('#cancel-appointment').hide(); // Hide by default

                                            if (event.status === 'booking') {
                                                $('#confirm-booking').show(); // Show Confirm Booking button
                                                $('#cancel-appointment').show(); // Show Cancel button
                                            } else if (event.status === 'appointment') {
                                                $('#confirm-booking').hide(); // Hide Confirm Booking button
                                                $('#cancel-appointment').show(); // Show Cancel button
                                            } else if (event.status === 'completed') {
                                                $('#confirm-booking').hide(); // Hide Confirm Booking button
                                                $('#cancel-appointment').hide(); // Hide Cancel button
                                            }

                                        });


                                        $('#cancel-appointment').off('click').on('click', function () {
                                            var confirmMessage = (event.status === 'booking')
                                                ? "Are you sure you want to cancel this booking?"
                                                : "Are you sure you want to cancel this appointment?";

                                            if (confirm(confirmMessage)) {
                                                $.ajax({
                                                    url: 'cancel_appointment.php',
                                                    type: 'POST',
                                                    data: { appoid: event.event_id }, // Pass appointment ID
                                                    success: function (response) {
                                                        let res = JSON.parse(response);
                                                        if (res.status) {
                                                            alert(event.status === 'booking'
                                                                ? "Booking cancelled successfully."
                                                                : "Appointment cancelled successfully.");

                                                            location.reload(); // 💥 Force refresh the page immediately
                                                        } else {
                                                            alert("Error: " + res.msg);
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                        // Show the modal
                                        $('#appointmentModal').modal('show');

                                    });

                                    element.css('background-color', event.color);
                                }

                                ,
                                dayRender: function (date, cell) {
                                    // Change the background of Thursdays to light red
                                    if (date.day() === 4) {  // 4 is Thursday
                                        cell.css("background-color", "#FFF2F2");
                                    }

                                    // Disable dates beyond 2 months from today
                                    var today = moment().startOf('day');
                                    var maxAllowedDate = moment().add(2, 'months').startOf('day');
                                    if (date < today || date > maxAllowedDate) {
                                        cell.css("background-color", "#fff2f2"); // Gray out disabled dates
                                        cell.css("pointer-events", "none"); // Disable click events
                                    }
                                }
                            });
                        },
                        error: function (xhr, status) {
                            alert("Error fetching events.");
                        }
                    });
                }



                // AJAX function to fetch event details
                function fetchEventDetails(eventId) {
                    $.ajax({
                        url: 'fetch_event_details.php',  // You need to create this PHP file to fetch event details
                        data: { event_id: eventId },
                        dataType: 'json',
                        success: function (response) {
                            if (response.status) {
                                var event = response.data;
                                var eventDetails = `
                    <strong>Event Name:</strong> ${event.title}<br>
                    <strong>Patient Name:</strong> ${event.patient_name}<br>
                    <strong>Procedure:</strong> ${event.procedure_name}<br>
                    <strong>Appointment Time:</strong> ${event.start} - ${event.end}<br>
                    <strong>Doctor:</strong> ${event.doc_name}
                `;
                                $('#event-info').html(eventDetails);
                                $('#info_modal').modal('show');
                            } else {
                                alert('Error fetching event details.');
                            }
                        },
                        error: function () {
                            alert('Error fetching event details.');
                        }
                    });
                }

                // Modify eventRender to handle clicks on the event (Booking or Appointment)
                $('#calendar').fullCalendar({
                    // other configurations...
                    events: events,
                    eventRender: function (event, element) {
                        // Add a click event for the event element
                        element.on('click', function () {
                            // When the event is clicked, fetch and display details
                            fetchEventDetails(event.event_id);
                        });

                        element.css('background-color', event.color);  // Apply the color based on event data
                    }
                });






                // Function to save the event
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
                            docid: docid  // Send dentist ID as well
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

                // Initialize on page load
                document.addEventListener('DOMContentLoaded', function () {
                    // Show first procedure's description if exists
                    var firstOption = document.querySelector('#procedure option');
                    if (firstOption) {
                        showProcedureDescription(document.getElementById('procedure'));
                    }

                    // Add tooltips
                    $('[data-toggle="tooltip"]').tooltip();
                });


            </script>
        </div>
        <!-- Appointment Details Modal -->
        <div class="modal fade" id="appointmentModal" tabindex="-1" role="dialog"
            aria-labelledby="appointmentModalLabel" aria-hidden="true">
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

</body>

</html>