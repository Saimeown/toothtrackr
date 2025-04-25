<?php
require 'database_connection.php';

session_start();
if (!isset($_SESSION['user'])) {
    $data = array(
        'status' => false,
        'msg' => 'Error: User not logged in.'
    );
    echo json_encode($data);
    exit;
}

$useremail = $_SESSION['user'];

$admin_query = "SELECT * FROM admin WHERE aemail = '$useremail'";
$admin_result = mysqli_query($con, $admin_query);

if (mysqli_num_rows($admin_result) == 0) {
    $data = array(
        'status' => false,
        'msg' => 'Error: User is not an admin.'
    );
    echo json_encode($data);
    exit;
}

$selected_date = isset($_GET['selected_date']) ? $_GET['selected_date'] : null;
$dentist_id = isset($_GET['dentist_id']) ? $_GET['dentist_id'] : null;

$update_past_appointments = "
    UPDATE appointment 
    SET status = 'completed' 
    WHERE appodate < CURDATE() 
    AND status IN ('appointment', 'booking')
";
mysqli_query($con, $update_past_appointments);

$display_query = "
    SELECT 
        a.appoid, 
        a.appodate, 
        a.appointment_time, 
        a.event_name, 
        a.pid, 
        a.status,
        p.procedure_name, 
        pt.pname AS patient_name,
        d.docname AS dentist_name
    FROM appointment a
    LEFT JOIN procedures p ON a.procedure_id = p.procedure_id
    LEFT JOIN patient pt ON a.pid = pt.pid
    LEFT JOIN doctor d ON a.docid = d.docid
    WHERE a.status IN ('booking', 'appointment', 'completed')
";

if ($selected_date) {
    $display_query .= " AND a.appodate = '$selected_date'";  // Filter by selected date
}

if ($dentist_id) {
    $display_query .= " AND a.docid = '$dentist_id'";  // Filter by dentist_id
}

$results = mysqli_query($con, $display_query);   
$count = mysqli_num_rows($results);  

if ($count > 0) {
    $data_arr = array();
    $i = 0; 
    while ($data_row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        
        $appointments_query = "
            SELECT appointment_time 
            FROM appointment 
            WHERE appodate = '{$data_row['appodate']}' 
            AND status = 'booking'
        ";        
        $appointments_result = mysqli_query($con, $appointments_query);
        
        $booked_times = [];
        while ($appointment_row = mysqli_fetch_assoc($appointments_result)) {
            $booked_times[] = $appointment_row['appointment_time']; 
        }

        $data_arr[$i]['appointment_id'] = $data_row['appoid'];
        $data_arr[$i]['title'] = $data_row['event_name'] . " with " . $data_row['patient_name']; 
        $data_arr[$i]['start'] = date("Y-m-d H:i:s", strtotime($data_row['appodate'] . ' ' . $data_row['appointment_time']));
        $data_arr[$i]['end'] = date("Y-m-d H:i:s", strtotime($data_row['appodate'] . ' ' . $data_row['appointment_time']));
        $data_arr[$i]['status'] = $data_row['status']; 

        $data_arr[$i]['procedure_name'] = $data_row['procedure_name'];  
        $data_arr[$i]['patient_name'] = $data_row['patient_name'];      
        $data_arr[$i]['dentist_name'] = $data_row['dentist_name'];      

        $appointmentDate = strtotime($data_row['appodate']);
        $todayDate = strtotime(date("Y-m-d"));

        if ($data_row['status'] == 'appointment') {
            $data_arr[$i]['color'] = '#f5c447'; // confirmed appointments
        } elseif ($data_row['status'] == 'booking') {
            $data_arr[$i]['color'] = '#f5c447'; // booking color
        } elseif ($data_row['status'] == 'completed') {
            $data_arr[$i]['color'] = 'grey'; // Grey for completed appointments
        }


        $data_arr[$i]['booked_times'] = $booked_times; 
        
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
