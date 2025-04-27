<?php
session_start();
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION["user"]) || ($_SESSION["user"] == "" || $_SESSION['usertype'] != 'a')) {
    header("location: ../login.php");
    exit();
}

include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appoid = $_POST['appoid'];
    $source = $_POST['source'];
    $reason = $_POST['cancel_reason'];
    $other_reason = isset($_POST['other_reason']) ? $_POST['other_reason'] : '';
    $full_reason = ($reason == 'Other') ? "Other: " . $other_reason : $reason;
    
    // First get appointment details before archiving
    $appointmentQuery = $database->query("
        SELECT a.*, d.docemail, d.docname, p.pemail, p.pname, pr.procedure_name
        FROM appointment a
        JOIN doctor d ON a.docid = d.docid
        JOIN patient p ON a.pid = p.pid
        JOIN procedures pr ON a.procedure_id = pr.procedure_id
        WHERE a.appoid = '$appoid'
    ");
    
    if (!$appointmentQuery || $appointmentQuery->num_rows == 0) {
        echo json_encode(['status' => false, 'message' => 'Appointment not found.']);
        exit();
    }
    
    $appointment = $appointmentQuery->fetch_assoc();
    
    // Determine status based on source
    $status = 'cancelled'; // Since this is from admin side
    
    // Archive the appointment with correct status and reason
    $archive_query = "INSERT INTO appointment_archive 
                     SELECT NULL, appoid, pid, docid, apponum, scheduleid, appodate, appointment_time, 
                            procedure_id, event_name, ?, ?, NOW() 
                     FROM appointment 
                     WHERE appoid = ?";
    
    $stmt = $database->prepare($archive_query);
    $stmt->bind_param("ssi", $status, $full_reason, $appoid);
    $archive_result = $stmt->execute();
    
    if (!$archive_result) {
        echo json_encode(['status' => false, 'message' => 'Failed to archive appointment.']);
        exit();
    }
    
    // Delete from appointments
    $delete_query = "DELETE FROM appointment WHERE appoid = ?";
    $stmt = $database->prepare($delete_query);
    $stmt->bind_param("i", $appoid);
    $delete_result = $stmt->execute();
    
    if ($delete_result) {
        // Send email notifications
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'songcodent@gmail.com';
            $mail->Password = 'gzdr afos onqq ppnv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients - send to patient
            $mail->setFrom('songcodent@gmail.com', 'ToothTrackr');
            $mail->addAddress($appointment['pemail'], $appointment['pname']);
            $mail->addCC($appointment['docemail'], $appointment['docname']); // CC to dentist
            $mail->addCC('songcodent@gmail.com'); // CC to admin
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Cancellation Notification';
            $mail->Body = "
                <h3>Appointment Cancellation Notification</h3>
                <p>Your appointment has been cancelled by the clinic administration.</p>
                <p><strong>Patient Name:</strong> {$appointment['pname']}</p>
                <p><strong>Dentist Name:</strong> {$appointment['docname']}</p>
                <p><strong>Appointment Date:</strong> " . date('F j, Y', strtotime($appointment['appodate'])) . "</p>
                <p><strong>Appointment Time:</strong> {$appointment['appointment_time']}</p>
                <p><strong>Procedure:</strong> {$appointment['procedure_name']}</p>
                <p><strong>Reason for Cancellation:</strong> $full_reason</p>
                <p>Please contact the clinic if you wish to reschedule or for more information.</p>
                <p>We apologize for any inconvenience.</p>
            ";
            
            $mail->send();
            
            echo json_encode([
                'status' => true,
                'message' => 'Appointment cancelled successfully. Notification sent to patient.'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => true,
                'message' => 'Appointment cancelled successfully, but failed to send notification email.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Failed to cancel appointment. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>