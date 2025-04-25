<?php
// Import the database connection
include("../connection.php");

// Check if the form is submitted via POST
if ($_POST) {
    // Fetch medical history fields from POST data
    $id = $_POST['id00']; // Patient ID
    $email = $_POST['email']; // Email is required for identifying the medical history record
    $good_health = $_POST['good_health'];
    $under_treatment = $_POST['under_treatment'];
    $condition_treated = $_POST['condition_treated'];
    $serious_illness = $_POST['serious_illness'];
    $hospitalized = $_POST['hospitalized'];
    $medication = $_POST['medication'];
    $medication_specify = $_POST['medication_specify'];
    $tobacco = $_POST['tobacco'];
    $drugs = $_POST['drugs'];
    $allergies = isset($_POST['allergies']) ? implode(',', $_POST['allergies']) : '';  // Store allergies as a comma-separated string
    $blood_pressure = $_POST['blood_pressure'];
    $bleeding_time = $_POST['bleeding_time'];
    $health_conditions = $_POST['health_conditions'];

    // Update the medical history in the database
    $sql3 = "UPDATE medical_history SET 
            good_health = ?, 
            under_treatment = ?, 
            condition_treated = ?, 
            serious_illness = ?, 
            hospitalized = ?, 
            medication = ?, 
            medication_specify = ?, 
            tobacco = ?, 
            drugs = ?, 
            allergies = ?, 
            blood_pressure = ?, 
            bleeding_time = ?, 
            health_conditions = ? 
            WHERE email = ?";

    // Prepare the query to prevent SQL injection
    $stmt = $database->prepare($sql3);
    $stmt->bind_param(
        "ssssssssssssss", 
        $good_health, $under_treatment, $condition_treated, $serious_illness, 
        $hospitalized, $medication, $medication_specify, $tobacco, 
        $drugs, $allergies, $blood_pressure, $bleeding_time, $health_conditions, $email
    );

    // Execute the query for medical history update
    if ($stmt->execute()) {
        // On successful update, set success error code
        $error = '4';  // Success
    } else {
        // On failure, set error code for medical history update
        $error = '5';  // Failed to update medical history
    }
} else {
    // If no POST data, set a default error
    $error = '3';
}

// Redirect to settings page with the error code
header("Location: settings.php?action=edit&error=".$error."&id=".$id);
exit();
?>
