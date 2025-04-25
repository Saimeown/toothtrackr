<?php
require 'database_connection.php'; 

// Start session to get the logged-in patient
session_start();
if (!isset($_SESSION['user'])) {
    $data = array(
        'status' => false,
        'msg' => 'Error: User not logged in.'
    );
    echo json_encode($data);
    exit;
}

// Get the logged-in patient's ID (from session)
$useremail = $_SESSION['user'];
$patient_query = "SELECT pid, pname FROM patient WHERE pemail = '$useremail'";
$patient_result = mysqli_query($con, $patient_query);
$patient_row = mysqli_fetch_assoc($patient_result);
$patient_id = $patient_row['pid'];
$patient_name = $patient_row['pname'];  // Get patient name

// Query to get all appointments with procedure and dentist name
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

$results = mysqli_query($con, $display_query);   
$count = mysqli_num_rows($results);  
$update_past_appointments = "
    UPDATE appointment 
    SET status = 'completed' 
    WHERE appodate < CURDATE() 
    AND status IN ('appointment', 'booking')
";
mysqli_query($con, $update_past_appointments);
if ($count > 0) {
    $data_arr = array();
    $i = 0; // Start index from 0
    while ($data_row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        
        // Get all booked times for the specific dentist and date
        $appointments_query = "
            SELECT appointment_time 
            FROM appointment 
            WHERE docid = '{$data_row['docid']}' 
            AND appodate = '{$data_row['appodate']}' 
            AND status = 'booking'
        ";        
        $appointments_result = mysqli_query($con, $appointments_query);
        
        $booked_times = [];
        while ($appointment_row = mysqli_fetch_assoc($appointments_result)) {
            $booked_times[] = $appointment_row['appointment_time']; // Collect booked times
        }

        // Check if this appointment is booked for the logged-in patient
        $appointment_id = $data_row['appoid'];
        $patient_appointment_query = "SELECT * FROM appointment WHERE appoid = '$appointment_id' AND pid = '$patient_id'";
        $patient_appointment_result = mysqli_query($con, $patient_appointment_query);
        
        $patient_booked = mysqli_num_rows($patient_appointment_result) > 0;

        // Add event data to response
        $data_arr[$i]['appointment_id'] = $data_row['appoid'];
        $data_arr[$i]['title'] = $data_row['event_name'] . " with " . $data_row['dentist_name']; // Display dentist name
        $data_arr[$i]['start'] = date("Y-m-d H:i:s", strtotime($data_row['appodate'] . ' ' . $data_row['appointment_time']));
        $data_arr[$i]['end'] = date("Y-m-d H:i:s", strtotime($data_row['appodate'] . ' ' . $data_row['appointment_time']));
        $data_arr[$i]['status'] = $data_row['status']; // Add the status to the event data

        // Additional fields
        $data_arr[$i]['procedure_name'] = $data_row['procedure_name'];  // Add procedure name
        $data_arr[$i]['patient_name'] = $patient_name;                  // Add patient name (from session query)
        $data_arr[$i]['dentist_name'] = $data_row['dentist_name'];     // Add dentist name

        // Set the color based on the status
        if ($data_row['status'] == 'appointment') {
            $data_arr[$i]['color'] = '#f5c447'; // confirmed appointments
        } elseif ($data_row['status'] == 'booking') {
            $data_arr[$i]['color'] = '#f5c447'; // booking color
        } elseif ($data_row['status'] == 'completed') {
            $data_arr[$i]['color'] = 'grey'; // Grey for completed appointments
        }

        $data_arr[$i]['booked_times'] = $booked_times;  // Include booked times for this doctor and date
        
        $i++;
    }


// Fetch non-working days
$non_working_days_query = "SELECT * FROM non_working_days";
$non_working_days_result = mysqli_query($con, $non_working_days_query);
if (mysqli_num_rows($non_working_days_result) > 0) {
    while ($row = mysqli_fetch_assoc($non_working_days_result)) {
        $data_arr[$i]['title'] = $row['description'];
        $data_arr[$i]['start'] = $row['date'];
        $data_arr[$i]['color'] = '#e23535'; // Red for non-working days
        $data_arr[$i]['type'] = "non-working"; 
        $i++;
    }
}

    $data = array(
        'status' => true,
        'msg' => 'Successfully fetched appointments!',
        'data' => $data_arr
    );
} else {
    $data = array(
        'status' => false,
        'msg' => 'Error: No appointments found.'                
    );
}

echo json_encode($data);
?>
