<?php
require 'database_connection.php'; 

// Start session to get the logged-in patient
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => false, 'msg' => 'Error: User not logged in.']);
    exit;
}

// Get the logged-in patient's ID (from session)
$useremail = mysqli_real_escape_string($con, $_SESSION['user']);
$patient_query = "SELECT pid, pname FROM patient WHERE pemail = '$useremail'";
$patient_result = mysqli_query($con, $patient_query);

if (!$patient_result || mysqli_num_rows($patient_result) == 0) {
    echo json_encode(['status' => false, 'msg' => 'Error: Patient not found.']);
    exit;
}

$patient_row = mysqli_fetch_assoc($patient_result);
$patient_id = $patient_row['pid'];
$patient_name = $patient_row['pname'];

// Get dentist_id from request if provided
$dentist_id = isset($_GET['dentist_id']) ? mysqli_real_escape_string($con, $_GET['dentist_id']) : null;

// Base query
$display_query = "
    SELECT 
        a.appoid, 
        a.appodate, 
        a.appointment_time, 
        a.event_name, 
        a.docid, 
        a.status,
        p.procedure_name, 
        d.docname AS dentist_name
    FROM appointment a
    LEFT JOIN procedures p ON a.procedure_id = p.procedure_id
    LEFT JOIN doctor d ON a.docid = d.docid
    WHERE a.pid = '$patient_id' 
    AND a.status IN ('booking', 'appointment', 'completed')
";

// Add dentist filter if provided
if ($dentist_id) {
    $display_query .= " AND a.docid = '$dentist_id'";
}

// Mark past appointments as completed
mysqli_query($con, "
    UPDATE appointment 
    SET status = 'completed' 
    WHERE appodate < CURDATE() 
    AND status IN ('appointment', 'booking')
    AND pid = '$patient_id'
");

$results = mysqli_query($con, $display_query);   
$data_arr = array();

if ($results && mysqli_num_rows($results) > 0) {
    while ($data_row = mysqli_fetch_assoc($results)) {
        $event_color = '';
        switch ($data_row['status']) {
            case 'appointment':
                $event_color = '#90EE90'; // Green for confirmed
                break;
            case 'booking':
                $event_color = '#F9C74F'; // Yellow for bookings
                break;
            case 'completed':
                $event_color = '#BBBBBB'; // Grey for completed
                break;
        }

        $data_arr[] = [
            'appointment_id' => $data_row['appoid'],
            'title' => $data_row['event_name'] . " with " . $data_row['dentist_name'],
            'start' => date("Y-m-d H:i:s", strtotime($data_row['appodate'] . ' ' . $data_row['appointment_time'])),
            'end' => date("Y-m-d H:i:s", strtotime($data_row['appodate'] . ' ' . $data_row['appointment_time'])),
            'status' => $data_row['status'],
            'procedure_name' => $data_row['procedure_name'],
            'patient_name' => $patient_name,
            'dentist_name' => $data_row['dentist_name'],
            'color' => $event_color
        ];
    }
}

// Add non-working days
$non_working_days = mysqli_query($con, "SELECT * FROM non_working_days");
if ($non_working_days && mysqli_num_rows($non_working_days) > 0) {
    while ($row = mysqli_fetch_assoc($non_working_days)) {
        $data_arr[] = [
            'title' => $row['description'],
            'start' => $row['date'],
            'color' => '#F94144', // Red for non-working days
            'type' => "non-working"
        ];
    }
}

echo json_encode([
    'status' => !empty($data_arr),
    'msg' => !empty($data_arr) ? 'Successfully fetched appointments!' : 'No appointments found.',
    'data' => $data_arr
]);
?>