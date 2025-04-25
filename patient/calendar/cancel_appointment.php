<?php
require 'database_connection.php';

session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => false, 'msg' => 'User not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appoid'])) {
    $appoid = intval($_POST['appoid']); // Ensure it's an integer

    // Fetch full appointment details
    $query = $con->prepare("SELECT * FROM appointment WHERE appoid = ?");
    $query->bind_param("i", $appoid);
    $query->execute();
    $result = $query->get_result();
    $appointment = $result->fetch_assoc();

    if (!$appointment) {
        echo json_encode(['status' => false, 'msg' => 'Appointment not found.']);
        exit;
    }

    $newStatus = 'cancelled';

    // Insert appointment into the archive table
    $appodate = $appointment['appodate'] !== null ? date('Y-m-d', strtotime($appointment['appodate'])) : null;
    $archiveQuery = $con->prepare("
        INSERT INTO appointment_archive (appoid, pid, docid, apponum, scheduleid, appodate, appointment_time, procedure_id, event_name, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $archiveQuery->bind_param(
        "iiisisssss", // Change "iiisiissss" to "iiisisssss" (the 6th parameter is now 's' for the date)
        $appointment['appoid'],
        $appointment['pid'],
        $appointment['docid'],
        $appointment['apponum'],
        $appointment['scheduleid'],
        $appodate, // Use the formatted appodate
        $appointment['appointment_time'],
        $appointment['procedure_id'],
        $appointment['event_name'],
        $newStatus
    );
    

    if ($archiveQuery->execute()) {
        // Now delete the original appointment
        $deleteQuery = $con->prepare("DELETE FROM appointment WHERE appoid = ?");
        $deleteQuery->bind_param("i", $appoid);
        
        if ($deleteQuery->execute()) {
            echo json_encode(['status' => true, 'msg' => "Appointment moved to archive and deleted successfully."]);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Failed to delete the appointment.']);
        }
    } else {
        echo json_encode(['status' => false, 'msg' => 'Failed to archive the appointment.']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'Invalid request.']);
}
?>
