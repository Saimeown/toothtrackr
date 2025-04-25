<?php
session_start();

// Check if the user is logged in and has admin privileges
if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
        exit(); // Ensure no further code is executed
    }
} else {
    header("location: ../login.php");
    exit(); // Ensure no further code is executed
}

// Check if the id is provided in the URL
if (isset($_GET['id'])) {
    // Import the database connection
    include("../connection.php");

    $id = $_GET['id'];

    // Using prepared statements to prevent SQL injection
    $stmt = $database->prepare("UPDATE appointment SET status = 'Cancelled' WHERE appoid = ?");
    $stmt->bind_param("i", $id);  // 'i' means the id is an integer
    $result = $stmt->execute();   // Execute the query

    // Check if the query was successful
    if ($result) {
        // If successful, redirect back to appointment.php
        header("location: appointment.php");
        exit(); // Ensure no further code is executed
    } else {
        // If the query fails, show an error message
        echo "Error: " . $stmt->error;
    }
} else {
    // If 'id' is not set, show an error or redirect back
    echo "Error: Appointment ID not provided.";
}

?>
