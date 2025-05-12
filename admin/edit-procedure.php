<?php
session_start();
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    // Check if procedure already exists (excluding current one)
    $check = $database->query("SELECT * FROM procedures WHERE procedure_name = '$name' AND procedure_id != '$id'");
    if ($check->num_rows > 0) {
        header("location: settings.php?action=edit_procedure&id=$id&error=1");
        exit;
    }
    
    // Validate fields
    if (empty($name)) {
        header("location: settings.php?action=edit_procedure&id=$id&error=2");
        exit;
    }
    
    // Update procedure
    $sql = "UPDATE procedures SET procedure_name = '$name', description = '$description' WHERE procedure_id = '$id'";
    if ($database->query($sql)) {
        header("location: settings.php?action=edit_procedure&id=$id&error=3");
    } else {
        header("location: settings.php?action=edit_procedure&id=$id&error=2");
    }
} else {
    header("location: settings.php");
}
?>