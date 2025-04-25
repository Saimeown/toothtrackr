<?php
session_start();
include("../connection.php");

// Check if user is logged in and a dentist
if (!isset($_SESSION["user"]) || $_SESSION["usertype"] != 'd') {
    header("Location: ../dentist/login.php");
    exit();
}

$post_id = $_POST['post_id'];

// Delete post
$sql = "DELETE FROM post_dentist WHERE id = ? AND docid = ?";
$stmt = $database->prepare($sql);
$stmt->bind_param("ii", $post_id, $_SESSION['userid']);
$stmt->execute();
header("Location: dashboard.php");
?>
