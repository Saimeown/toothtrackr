<?php

session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
        header("location: login.php");
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: login.php");
}

// Import database connection
include("../connection.php");
$userrow = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

if ($_GET) {
    // Import database again for processing
    include("../connection.php");
    $id = $_GET["id"];
    $result001 = $database->query("SELECT * FROM patient WHERE pid=$id;");
    $email = ($result001->fetch_assoc())["pemail"];
    
    // Update patient status to 'inactive' instead of deleting
    $sql = $database->query("UPDATE patient SET status='inactive' WHERE pemail='$email';");
    
    // Optionally, you can also update the status in the webuser table if needed
    // $sqlWebUser = $database->query("UPDATE patient SET status='inactive' WHERE email='$email';");

    // Redirect to the logout page (or wherever you'd like)
    header("location: ../logout.php");
}
?>
