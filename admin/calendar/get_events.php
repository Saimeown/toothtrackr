<?php
include("../../connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $events = [];
    $query = "SELECT * FROM appointment";
    $result = $database->query($query);
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['appoid'],
            'title' => $row['event_name'],
            'start' => $row['appodate'] . 'T' . $row['appointment_time'],
            'doctor' => $row['docid'],
            'procedure' => $row['procedure_id'],
            'patient' => $row['pid'],
        ];
    }
    echo json_encode($events);
    exit();
}
?>
