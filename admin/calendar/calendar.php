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
    <link rel="stylesheet" href="../../css/animations.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/dashboard.css">


    <title>Calendar - ToothTrackr</title>
    <link rel="icon" href="../../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
</head>


<body>
    <?php


    session_start();


    require '../../vendor/autoload.php';
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;


    function sendCancellationEmail($patientEmail, $patientName, $appointmentDate, $appointmentTime, $reason = "cancelled") {
        $mail = new PHPMailer(true);
       
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'songcodent@gmail.com';
            $mail->Password = 'gzdr afos onqq ppnv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
   
            // Recipients
            $mail->setFrom('songcodent@gmail.com', 'ToothTrackr');
            $mail->addAddress($patientEmail, $patientName);
   
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment '.ucfirst($reason);
           
            if ($reason == 'cancelled') {
                $mail->Body = "Dear $patientName,<br><br>
                            We regret to inform you that your appointment scheduled for:<br><br>
                            <strong>Date:</strong> $appointmentDate<br>
                            <strong>Time:</strong> $appointmentTime<br><br>
                            has been cancelled by the clinic.<br><br>
                            Please contact us to reschedule or for more information.<br><br>
                            We apologize for any inconvenience.<br><br>
                            Sincerely,<br>
                            Songco Dental and Medical Clinic";
            } else {
                $mail->Body = "Dear $patientName,<br><br>
                            Your booking request for:<br><br>
                            <strong>Date:</strong> $appointmentDate<br>
                            <strong>Time:</strong> $appointmentTime<br><br>
                            has been rejected by the clinic.<br><br>
                            Please contact us to choose another time slot or for more information.<br><br>
                            Sincerely,<br>
                            Songco Dental and Medical Clinic";
            }
   
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Failed to send cancellation email: " . $mail->ErrorInfo);
            return false;
        }
    }


    if (!isset($_SESSION["user"]) || ($_SESSION["user"] == "" || $_SESSION['usertype'] != 'a')) {
        header("location: ../login.php");
        exit();
    }


    include("../../connection.php");


    $procedures = $database->query("SELECT * FROM procedures");
    $procedure_options = '';
    while ($procedure = $procedures->fetch_assoc()) {
        $procedure_options .= '<option value="' . $procedure['procedure_id'] . '">' . $procedure['procedure_name'] . '</option>';
    }


    $doctors = $database->query("SELECT docid, docname FROM doctor");
    $doctor_options = '';
    while ($doctor = $doctors->fetch_assoc()) {
        $doctor_options .= '<option value="' . $doctor['docid'] . '">' . $doctor['docname'] . '</option>';
    }


    $patients = $database->query("SELECT pid, pname FROM patient");
    $patient_name = '';
    while ($patient = $patients->fetch_assoc()) {
        $patient_name .= '<option value="' . $patient['pid'] . '">' . $patient['pname'] . '</option>';
    }


    ?>
    <div class="nav-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="../../Media/Icon/ToothTrackr/ToothTrackr.png" alt="ToothTrackr Logo">
            </div>


            <div class="user-profile">
                <div class="profile-image">
                    <img src="../../Media/Icon/SDMC Logo.png" alt="Profile" class="profile-img">
                </div>
                <h3 class="profile-name">Songco Dental and Medical Clinic</h3>
                <p style="color: #777; margin: 0; font-size: 14px; text-align: center;">
                    Administrator
                </p>
            </div>


            <div class="nav-menu">
                <a href="../dashboard.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/home.png" alt="Home" class="nav-icon">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="../dentist.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/dentist.png" alt="Dentist" class="nav-icon">
                    <span class="nav-label">Dentist</span>
                </a>
                <a href="../patient.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/care.png" alt="Patient" class="nav-icon">
                    <span class="nav-label">Patient</span>
                </a>
                <a href="../records.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/edit.png" alt="Records" class="nav-icon">
                    <span class="nav-label">Patient Records</span>
                </a>
                <a href="calendar.php" class="nav-item active">
                    <img src="../../Media/Icon/Blue/calendar.png" alt="Calendar" class="nav-icon">
                    <span class="nav-label">Calendar</span>
                </a>
                <a href="../booking.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/booking.png" alt="Booking" class="nav-icon">
                    <span class="nav-label">Booking</span>
                </a>
                <a href="../appointment.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/appointment.png" alt="Appointment" class="nav-icon">
                    <span class="nav-label">Appointment</span>
                </a>
                <a href="../history.php" class="nav-item">
                    <img src="../../Media/Icon/Blue/folder.png" alt="Archive" class="nav-icon">
                    <span class="nav-label">Archive</span>
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
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">


                        <div class="form-group">
                            <label class="create-title" for="choose_dentist">Create an Appointment:</label>
                            <div class="select-wrapper">
                                <select class="form-dentist" id="choose_dentist">
                                    <option value="">Select a Dentist</option>
                                    <?php echo $doctor_options; ?>
                                </select>
                            </div>
                        </div>
                        <div class="legend">
                            <div class="legend-item bookings">Bookings</div>
                            <div class="legend-item appointments">Appointments</div>
                            <div class="legend-item no-service">No Service</div>
                            <div class="legend-item timeslot-taken">Timeslot Taken</div>
                            <div class="legend-item completed">Completed</div>
                            <button class="legend-item" id="addNonWorkingDay">Add Non-Working Day</button>
                        </div>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>


            <!-- Start popup dialog box -->
            <div class="modal fade" id="event_entry_modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
                aria-hidden="true">
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
                                    <select class="form-control" id="procedure" name="procedure">
                                        <?php echo $procedure_options; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="patient_name">Patient Name</label>
                                    <select class="form-control" id="patient_name" name="patient_name">
                                        <option value="">Select a Patient</option>
                                        <?php echo $patient_name; ?>
                                    </select>
                                </div>


                                <div class="form-group">
                                    <label for="appointment_date">Date</label>
                                    <input type="text" name="appointment_date" id="appointment_date"
                                        class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="appointment_time">Time</label>
                                    <select class="form-control" id="appointment_time" name="appointment_time">
                                        <option value="9:00-10:00">9:00 AM - 10:00 AM</option>
                                        <option value="10:00-11:00">10:00 AM - 11:00 AM</option>
                                        <option value="11:00-12:00">11:00 AM - 12:00 PM</option>
                                        <option value="1:00-2:00">1:00 PM - 2:00 PM</option>
                                        <option value="2:00-3:00">2:00 PM - 3:00 PM</option>
                                        <option value="4:00-5:00">4:00 PM - 5:00 PM</option>
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


            <script>
                $(document).ready(function () {
                    $('#choose_dentist').change(function () {
                        var dentistId = $(this).val();
                        if (dentistId) {
                            $('#docid').val(dentistId);
                            display_events(dentistId);
                        } else {
                            alert("Please select a dentist.");
                        }
                    });


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
                                    bookedTimes = response.booked_times;
                                    updateTimeDropdown();
                                }
                            },
                            error: function (xhr, status) {
                                alert("Error fetching booked times.");
                            }
                        });
                    }


                    function updateTimeDropdown() {
                        var timeSlots = [
                            "09:00:00", "09:30:00",
                            "10:00:00", "10:30:00",
                            "11:00:00", "11:30:00",
                            "13:00:00", "13:30:00",
                            "14:00:00", "14:30:00",
                            "16:00:00", "16:30:00"
                        ];


                        $('#appointment_time').empty(); // Clear existing options
                        $.each(timeSlots, function (index, time) {
                            var option = $("<option></option>").val(time).text(time);
                            if (bookedTimes.indexOf(time) !== -1) {
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


                            if ($('#calendar').fullCalendar) {
                                $('#calendar').fullCalendar('destroy');
                            }


                            $(document).ready(function () {
                                var nonWorkingDays = [];


                                function fetchNonWorkingDays() {
                                    $.ajax({
                                        url: 'fetch_non_working_days.php',
                                        method: 'GET',
                                        dataType: 'json',
                                        success: function (response) {
                                            if (response.status) {
                                                nonWorkingDays = response.non_working_days;
                                                $('#calendar').fullCalendar('rerenderEvents');
                                            } else {
                                                console.error("Error: Invalid response format", response);
                                                alert("Invalid response from server.");
                                            }
                                        },
                                        error: function (xhr, status, error) {
                                            console.error("AJAX error:", status, error);
                                            console.error("Server response:", xhr.responseText);
                                            alert('Error fetching non-working days.');
                                        }
                                    });
                                }


                                $("#addNonWorkingDay").click(function () {
                                    $("#nonWorkingDayModal").modal("show");
                                });


                                $("#saveNonWorkingDay").click(function () {
                                    var selectedDate = $("#nonWorkingDate").val();
                                    var description = $("#nonWorkingDesc").val();


                                    if (!selectedDate || !description) {
                                        alert("Please enter a date and description.");
                                        return;
                                    }


                                    $.ajax({
                                        url: "save_non_working_day.php",
                                        type: "POST",
                                        dataType: "json",
                                        data: { date: selectedDate, description: description },
                                        success: function (res) {
                                            console.log("Processed Response:", res); // Debugging log


                                            if (res.status) {
                                                alert("Non-Working Day added successfully!");
                                                fetchNonWorkingDays(); // Refresh calendar
                                                $("#nonWorkingDayModal").modal("hide");
                                                $(".modal-backdrop").remove();
                                            } else {
                                                alert("Error: " + res.message);
                                            }
                                        },
                                        error: function (xhr) {
                                            alert("Failed to save Non-Working Day. Server Error.");
                                            console.error("Server response:", xhr.responseText);
                                        }
                                    });


                                });


                                fetchNonWorkingDays();


                                $('#calendar').fullCalendar({
                                    defaultView: 'month',
                                    selectable: true,
                                    selectHelper: true,
                                    selectAllow: function (selectInfo) {
                                        var selectedDate = moment(selectInfo.start).format('YYYY-MM-DD');


                                        if (moment(selectInfo.start).day() === 4) {
                                            return false;  // Disable Thursdays
                                        }


                                        if (nonWorkingDays.some(day => day.date === selectedDate)) {
                                            return false;  // Disable Non-Working Days
                                        }


                                        return true;
                                    },
                                    events: function (start, end, timezone, callback) {
                                        $.ajax({
                                            url: 'display_event.php',
                                            dataType: 'json',
                                            success: function (response) {
                                                var events = response.data.map(event => ({
                                                    event_id: event.appointment_id,
                                                    title: event.title,
                                                    start: event.start,
                                                    end: event.end,
                                                    color: event.status === 'appointment' ? '#6BB663' : event.color,
                                                    status: event.status,
                                                    procedure_name: event.procedure_name,
                                                    patient_name: event.patient_name,
                                                    dentist_name: event.dentist_name
                                                }));


                                                // Fetch Non-Working Days and Add Them
                                                $.ajax({
                                                    url: 'fetch_non_working_days.php',
                                                    dataType: 'json',
                                                    success: function (nonWorkingDays) {
                                                        nonWorkingDays.forEach(day => {
                                                            events.push({
                                                                title: day.description || "Non-Working Day",
                                                                start: day.date,
                                                                color: "#e23535", // Red for non-working days
                                                                textColor: "#ffffff", // White text for better contrast
                                                                allDay: true
                                                            });
                                                        });


                                                        callback(events); // Now load all events into the calendar
                                                    }
                                                });
                                            }
                                        });
                                    },
                                    eventClick: function (event) {
                                        if (event.color === "#e23535") {
                                            alert("Non-Working Day: " + event.title);
                                            return false; // This prevents the modal from opening
                                        }
                                    }




                                });
                            });






                            // Reinitialize the calendar with new events
                            $('#calendar').fullCalendar({
                                defaultView: 'month',
                                timeZone: 'local',
                                editable: true,
                                selectable: true,
                                selectHelper: true,
                                selectAllow: function (selectInfo) {
                                    // Disable selection if the chosen date is a Thursday (day() === 4)
                                    return moment(selectInfo.start).day() !== 4;
                                },
                                select: function (start, end) {
                                    var selectedDate = moment(start).format('YYYY-MM-DD');
                                    var today = moment().format('YYYY-MM-DD');


                                    if (selectedDate < today) {
                                        alert("You cannot create appointments for past dates!");
                                        return; // Stop the function
                                    }


                                    $('#appointment_date').val(selectedDate);
                                    $('#event_name').val("Event Name");


                                    fetchBookedTimes(selectedDate);


                                    $('#event_entry_modal').modal('show');
                                }
                                ,
                                events: events,
                                eventRender: function (event, element, view) {
                                    var eventDate = moment(event.start).format('YYYY-MM-DD');
                                    var todayDate = moment().format('YYYY-MM-DD');


                                    // If the event date is before today, set its background color to gray
                                    if (eventDate < todayDate) {
                                        element.css({
                                            'background-color': '#B0B0B0',  // Gray background
                                            'color': '#FFFFFF'  // White text for contrast
                                        });
                                    }


                                    element.on('click', function () {
                                        // Fill modal fields with event data
                                        $('#confirm-booking').off('click').on('click', function() {
                                            if (confirm("Are you sure you want to confirm this booking?")) {
                                                var submitButton = $(this);
                                                submitButton.prop('disabled', true);
                                                submitButton.text('Processing...');
                                               
                                                $.ajax({
                                                    url: 'confirm_appointment.php',
                                                    type: 'POST',
                                                    data: { appoid: event.event_id },
                                                    dataType: 'json',
                                                    success: function(response) {
                                                        if (response.status) {
                                                            alert(response.msg);
                                                            $('#appointmentModal').modal('hide');
                                                            // Refresh the calendar
                                                            $('#calendar').fullCalendar('refetchEvents');
                                                        } else {
                                                            alert(response.msg);
                                                        }
                                                    },
                                                    error: function() {
                                                        alert("Failed to process the request. Please try again.");
                                                    },
                                                    complete: function() {
                                                        submitButton.prop('disabled', false);
                                                        submitButton.text('Confirm Booking');
                                                    }
                                                });
                                            }
                                        });


                                        $('#modalProcedureName').text(event.procedure_name || 'N/A');
                                        $('#modalPatientName').text(event.patient_name || 'N/A');
                                        $('#modalDentistName').text(event.dentist_name || 'N/A');
                                        $('#modalDate').text(event.start ? new Date(event.start).toLocaleDateString() : 'N/A');
                                        $('#modalTime').text(event.start ? moment(event.start).format("hh:mm A") : 'N/A');
                                        $('#modalStatus').text(event.status === 'appointment' ? 'Confirmed Appointment' : event.status === 'booking' ? 'Booking' : event.status === 'completed' ? 'Completed' : 'Clinic Closed.');


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












                                        // Show the modal
                                        $('#appointmentModal').modal('show');
                                        $('#cancel-appointment').off('click').on('click', function () {
                                            var confirmMessage = (event.status === 'booking')
                                                ? "Are you sure you want to reject this booking?"
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
                                                                ? "Booking rejected successfully."
                                                                : "Appointment cancelled successfully.");


                                                            location.reload(); // 💥 Force refresh the page immediately
                                                        } else {
                                                            alert("Error: " + res.msg);
                                                        }
                                                    }
                                                    ,
                                                    error: function () {
                                                        alert("Failed to process the request. Please try again.");
                                                    }
                                                });
                                            }


                                        });


                                    });


                                    element.css('background-color', event.color);
                                },
                                dayRender: function (date, cell) {
                                    // Change the background of Thursdays to light red
                                    if (date.day() === 4) {  // 4 is Thursday
                                        cell.css("background-color", "#FFF2F2");
                                    }
                                }
                            });
                        },
                        error: function (xhr, status) {
                            alert("Error fetching events.");
                        }
                    });
                }








                function fetchEventDetails(eventId) {
                    $.ajax({
                        url: 'fetch_event_details.php',
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


                $('#calendar').fullCalendar({
                    events: events,
                    eventRender: function (event, element) {
                        element.on('click', function () {
                            fetchEventDetails(event.event_id);
                        });


                        element.css('background-color', event.color);
                    },
                    events: {
                        url: 'get_events.php',
                        method: 'GET',
                        failure: function () {
                            alert('There was an error fetching events!');
                        }
                    }


                });


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






            </script>
        </div>
        <div class="modal fade" id="appointmentModal" tabindex="-1" role="dialog"
            aria-labelledby="appointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appointmentModalLabel">Event Details</h5>
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
                        <button type="button" class="btn btn-danger" id="cancel-appointment">Cancel/Reject</button>
                        <button id="confirm-booking" class="btn btn-success" style="display: none;">Confirm
                            Booking</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="nonWorkingDayModal" class="modal fade" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Non-Working Day</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <label>Select Date:</label>
                        <input type="date" id="nonWorkingDate" class="form-control">
                        <label>Description:</label>
                        <input type="text" id="nonWorkingDesc" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button id="saveNonWorkingDay" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

</body>

</html>

