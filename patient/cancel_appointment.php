<?php
session_start();
include("../connection.php");

if(isset($_GET['id'])) {
    $appoid = $_GET['id'];
    $source = isset($_GET['source']) ? $_GET['source'] : 'admin';
    
    // Determine status based on source
    $status = ($source == 'patient') ? 'cancelled' : 'rejected';
    
    // First archive the appointment with correct status
    $archive_query = "INSERT INTO appointment_archive 
                     SELECT NULL, appoid, pid, docid, apponum, scheduleid, appodate, appointment_time, 
                            procedure_id, event_name, ?, NOW() 
                     FROM appointment 
                     WHERE appoid = ?";
    
    $stmt = $database->prepare($archive_query);
    $stmt->bind_param("si", $status, $appoid);
    $stmt->execute();
    
    // Then delete from appointments
    $delete_query = "DELETE FROM appointment WHERE appoid = ?";
    $stmt = $database->prepare($delete_query);
    $stmt->bind_param("i", $appoid);
    $result = $stmt->execute();
    
    if($result) {
        if($source == 'patient') {
            header("Location: my_appointment.php?status=cancel_success");
        } else {
            header("Location: my_appointment.php?status=reject_success");
        }
    } else {
        if($source == 'patient') {
            header("Location: my_appointment.php?status=cancel_error");
        } else {
            header("Location: my_appointment.php?status=reject_error");
        }
    }
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>