<?php
session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../admin/login.php");
    }
} else {
    header("location: ../admin/login.php");
}

if ($_GET) {
    // Import database
    include("../connection.php");

    $id = $_GET["id"];

    // Set dentist's status to 'inactive'
    $sql = $database->query("UPDATE doctor SET status='inactive' WHERE docid=$id;");

    // Redirect to dentist management page
    header("location: dentist.php");
}
?>
