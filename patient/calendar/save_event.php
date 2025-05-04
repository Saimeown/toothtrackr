<?php
include("../../connection.php");
session_start();

if (!isset($_SESSION["user"]) || $_SESSION['usertype'] != 'p') {
    echo json_encode(['status' => false, 'msg' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'];
    $procedure_id = $_POST['procedure'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $docid = $_POST['docid'];
    
    // Get the user email from session and find the patient ID
    $useremail = $_SESSION["user"];
    $patientQuery = $database->query("SELECT pid, pname FROM patient WHERE pemail = '$useremail'");
    $patient = $patientQuery->fetch_assoc();
    $pid = $patient['pid'];

    // Get procedure and doctor details
    $procedureQuery = $database->query("SELECT procedure_name FROM procedures WHERE procedure_id = '$procedure_id'");
    $procedure = $procedureQuery->fetch_assoc();
    
    $doctorQuery = $database->query("SELECT docname, docemail FROM doctor WHERE docid = '$docid'");
    $doctor = $doctorQuery->fetch_assoc();

    // Check if the timeslot already has three bookings
    $count_query = "
        SELECT COUNT(*) as count 
        FROM appointment 
        WHERE docid = ? 
        AND appodate = ? 
        AND appointment_time = ?
    ";
    $count_stmt = $database->prepare($count_query);
    $count_stmt->bind_param("iss", $docid, $appointment_date, $appointment_time);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();

    if ($count_row['count'] >= 3) {
        echo json_encode(['status' => false, 'msg' => 'This timeslot is already fully booked.']);
        exit;
    }

    // Insert the appointment
    $sql = "INSERT INTO appointment (event_name, procedure_id, pid, appodate, appointment_time, docid, status)
            VALUES (?, ?, ?, ?, ?, ?, 'booking')";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("siisss", $event_name, $procedure_id, $pid, $appointment_date, $appointment_time, $docid);

    if ($stmt->execute()) {
        $appoid = $stmt->insert_id;
        
        // Create notification for patient
        $notificationTitle = "Booking Request Sent";
        $notificationMessage = "Your booking for " . $procedure['procedure_name'] . " on " . 
                              date('M j, Y', strtotime($appointment_date)) . " at " . 
                              date('g:i A', strtotime($appointment_time)) . " has been requested successfully.";
        
        $notificationStmt = $database->prepare("INSERT INTO notifications 
                                              (user_id, user_type, title, message, related_id, related_type) 
                                              VALUES (?, 'p', ?, ?, ?, 'appointment')");
        $notificationStmt->bind_param("issi", $pid, $notificationTitle, $notificationMessage, $appoid);
        $notificationStmt->execute();
        $notificationStmt->close();
        
        // Create notification for dentist
        $dentistNotificationTitle = "New Appointment Booking";
        $dentistNotificationMessage = "New appointment booked by " . $patient['pname'] . " for " . 
                                     $procedure['procedure_name'] . " on " . 
                                     date('M j, Y', strtotime($appointment_date)) . " at " . 
                                     date('g:i A', strtotime($appointment_time));
        
        $dentistNotificationStmt = $database->prepare("INSERT INTO notifications 
                                                     (user_id, user_type, title, message, related_id, related_type) 
                                                     VALUES (?, 'd', ?, ?, ?, 'appointment')");
        $dentistNotificationStmt->bind_param("issi", $docid, $dentistNotificationTitle, $dentistNotificationMessage, $appoid);
        $dentistNotificationStmt->execute();
        $dentistNotificationStmt->close();

        echo json_encode(['status' => true, 'msg' => 'Booking added successfully.']);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => false, 'msg' => 'Invalid request method']);
}
?>
