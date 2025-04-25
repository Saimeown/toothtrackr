<?php
include("../../connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'] ?? '';
    $procedure_id = $_POST['procedure'] ?? '';
    $patient_id = $_POST['patient_name'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $docid = $_POST['docid'] ?? '';
    
    if (empty($event_name) || empty($procedure_id) || empty($patient_id) || empty($appointment_date) || empty($appointment_time) || empty($docid)) {
        echo json_encode(['status' => false, 'msg' => 'All fields are required.']);
        exit();
    }
    
    $stmt = $database->prepare("SELECT pid FROM patient WHERE pid = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo json_encode(['status' => false, 'msg' => 'Patient not found.']);
        exit();
    }

    $sql = "INSERT INTO appointment (event_name, procedure_id, pid, appodate, appointment_time, docid, status) VALUES (?, ?, ?, ?, ?, ?, 'appointment')";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("siisss", $event_name, $procedure_id, $patient_id, $appointment_date, $appointment_time, $docid);

    if ($stmt->execute()) {
        echo json_encode(['status' => true, 'msg' => 'Appointment added successfully.']);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error: ' . $stmt->error]);
    }
    
    $stmt->close();
}
?>
