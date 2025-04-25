<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'database_connection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST["date"] ?? "";
    $description = $_POST["description"] ?? "";

    if (empty($date) || empty($description)) {
        echo json_encode(["status" => false, "message" => "Missing date or description"]);
        exit;
    }

    // Check if connection exists
    if (!$con) {
        echo json_encode(["status" => false, "message" => "Database connection failed"]);
        exit;
    }

    $sql = "INSERT INTO non_working_days (date, description) VALUES (?, ?)";
    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $date, $description);
        if ($stmt->execute()) {
            echo json_encode(["status" => true, "message" => "Saved successfully"]);
        } else {
            echo json_encode(["status" => false, "message" => "Database insert failed: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => false, "message" => "SQL prepare failed: " . $con->error]);
    }
} else {
    echo json_encode(["status" => false, "message" => "Invalid request"]);
}
?>
