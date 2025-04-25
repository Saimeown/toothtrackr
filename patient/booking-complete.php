<?php

//learn from w3schools.com

session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'p') {
        header("location: login.php");
    } else {
        $useremail = $_SESSION["user"];
    }

} else {
    header("location: login.php");
}


//import database
include("../connection.php");
$userrow = $database->query("select * from patient where pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];


if ($_POST) {
    if (isset($_POST["booknow"])) {
        $apponum = $_POST["apponum"];
        $scheduleid = $_POST["scheduleid"];
        $date = $_POST["date"];
        $scheduleid = $_POST["scheduleid"];
        $sql2 = "INSERT INTO appointment (scheduleid, pid, appodate, apponum, status) 
          VALUES ('$scheduleid', '$userid', '$date', '$apponum', 'booking')";
        $result = $database->query($sql2);
        //echo $apponom;
        header("location: my_booking.php?action=booking-added&id=" . $apponum . "&titleget=none");

    }
}
?>