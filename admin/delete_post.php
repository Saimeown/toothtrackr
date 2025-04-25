<?php
session_start();
include("../connection.php");

// Check if user is logged in and a dentist
if (!isset($_SESSION["user"]) || $_SESSION["usertype"] != 'a') {
    header("Location: ../dentist/login.php");
    exit();
}

$post_id = $_POST['post_id'];

// Delete post
$sql = "DELETE FROM post_admin WHERE id = ?";
$stmt = $database->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
header("Location: dashboard.php");
?>
