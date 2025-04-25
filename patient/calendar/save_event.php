<?php
include("../../connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'];
    $procedure_id = $_POST['procedure'];
    $patient_name = $_POST['patient_name'];  // Patient's name passed
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];  // Appointment time passed
    $docid = $_POST['docid'];  // Dentist ID passed from the form

    // Query to get patient_id based on patient_name (use a safe method like prepared statements)
    $stmt = $database->prepare("SELECT pid FROM patient WHERE pname = ?");
    $stmt->bind_param("s", $patient_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Patient found, get their pid
        $patient_row = $result->fetch_assoc();
        $patient_id = $patient_row['pid'];

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

        // Insert the data into the appointment table using the pid (foreign key)
        $sql = "INSERT INTO appointment (event_name, procedure_id, pid, appodate, appointment_time, docid, status)
                VALUES (?, ?, ?, ?, ?, ?, 'booking')";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("siisss", $event_name, $procedure_id, $patient_id, $appointment_date, $appointment_time, $docid);

        if ($stmt->execute()) {
            echo json_encode(['status' => true, 'msg' => 'Appointment added successfully.']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => false, 'msg' => 'Patient not found.']);
    }

    $stmt->close();
}
?>