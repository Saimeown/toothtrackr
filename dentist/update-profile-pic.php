<?php
session_start();

// Check if user is logged in and has correct user type
if (!isset($_SESSION["user"]) || $_SESSION['usertype'] != 'd') {
    header("location: login.php");
    exit();
}

// Include database connection
include("../connection.php");

// Get user details
$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
if ($userrow->num_rows == 0) {
    $_SESSION["profile_pic_error"] = "User not found.";
    header("Location: settings.php");
    exit();
}
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$current_photo = $userfetch["photo"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["upload_photo"])) {
        // Handle file upload
        if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
            $allowed = ["jpg", "jpeg", "png", "gif"];
            $filename = $_FILES["profile_picture"]["name"];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $filesize = $_FILES["profile_picture"]["size"];

            // Validate file
            if (!in_array($filetype, $allowed)) {
                $_SESSION["profile_pic_error"] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } elseif ($filesize > 5 * 1024 * 1024) { // 5MB limit
                $_SESSION["profile_pic_error"] = "File size must be less than 5MB.";
            } else {
                // Generate unique filename
                $new_filename = "profile_" . $userid . "_" . time() . "." . $filetype;
                $upload_path = "../admin/uploads/" . $new_filename;

                // Delete existing photo if it exists
                if (!empty($current_photo) && file_exists("../admin/uploads/" . $current_photo)) {
                    unlink("../admin/uploads/" . $current_photo);
                }

                // Move uploaded file
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $upload_path)) {
                    // Update database
                    $update_query = "UPDATE doctor SET photo=? WHERE docid=?";
                    $stmt = $database->prepare($update_query);
                    $stmt->bind_param("si", $new_filename, $userid);
                    $stmt->execute();
                    $stmt->close();

                    $_SESSION["profile_pic_success"] = "Profile picture updated successfully!";
                } else {
                    $_SESSION["profile_pic_error"] = "Failed to upload the file.";
                }
            }
        } else {
            $_SESSION["profile_pic_error"] = "No file was uploaded or an error occurred.";
        }
    } elseif (isset($_POST["delete_photo"])) {
        // Handle photo deletion
        if (!empty($current_photo) && file_exists("../admin/uploads/" . $current_photo)) {
            unlink("../admin/uploads/" . $current_photo);
            // Update database to remove photo
            $update_query = "UPDATE doctor SET photo=NULL WHERE docid=?";
            $stmt = $database->prepare($update_query);
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->close();
            $_SESSION["profile_pic_success"] = "Profile picture removed successfully!";
        } else {
            $_SESSION["profile_pic_error"] = "No profile picture to delete.";
        }
    }

    // Redirect back to settings.php
    header("Location: settings.php");
    exit();
} else {
    // Invalid request method
    $_SESSION["profile_pic_error"] = "Invalid request.";
    header("Location: settings.php");
    exit();
}
?>