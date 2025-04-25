<?php
session_start();
include("../connection.php");

// Check if user is logged in and a dentist
if (!isset($_SESSION["user"]) || $_SESSION["usertype"] != 'd') {
    header("Location: ../dentist/login.php");
    exit();
}

$userid = $_SESSION["userid"];
$post_title = $_POST['post_title'];
$post_content = $_POST['post_content'];

// Insert into database
$sql = "INSERT INTO post_dentist (docid, title, content, created_at) VALUES ('$userid', ?, ?, NOW())";
$stmt = $database->prepare($sql);
$stmt->bind_param("ss", $post_title, $post_content);
$stmt->execute();
header("Location: dashboard.php");
?>
