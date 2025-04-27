<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
session_start();
include("../connection.php");

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appoid = $_POST['id'];
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
    $appointment = $appointmentQuery->fetch_assoc();
    
    // Determine status based on source
    $status = ($source == 'patient') ? 'cancelled' : 'rejected';
    
    // First archive the appointment with correct status and reason
    $archive_query = "INSERT INTO appointment_archive 
                     SELECT NULL, appoid, pid, docid, apponum, scheduleid, appodate, appointment_time, 
                            procedure_id, event_name, ?, ?, NOW() 
                     FROM appointment 
                     WHERE appoid = ?";
    
    $stmt = $database->prepare($archive_query);
    $stmt->bind_param("ssi", $status, $full_reason, $appoid);
    $stmt->execute();
    
    // Then delete from appointments
    $delete_query = "DELETE FROM appointment WHERE appoid = ?";
    $stmt = $database->prepare($delete_query);
    $stmt->bind_param("i", $appoid);
    $result = $stmt->execute();
    
    if($result) {
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
            
            // Recipients - send to dentist
            $mail->setFrom('songcodent@gmail.com', 'ToothTrackr');
            $mail->addAddress($appointment['docemail'], $appointment['docname']);
            $mail->addCC('songcodent@gmail.com'); // CC to admin
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment ' . ucfirst($status) . ' Notification';
            $mail->Body = "
                <h3>Appointment " . ucfirst($status) . " Notification</h3>
                <p>An appointment has been $status by the patient.</p>
                <p><strong>Patient Name:</strong> {$appointment['pname']}</p>
                <p><strong>Appointment Date:</strong> " . date('F j, Y', strtotime($appointment['appodate'])) . "</p>
                <p><strong>Appointment Time:</strong> " . date('g:i A', strtotime($appointment['appointment_time'])) . "</p>
                <p><strong>Procedure:</strong> {$appointment['procedure_name']}</p>
                <p><strong>Reason:</strong> $full_reason</p>
                <p>Please check your schedule in ToothTrackr for updates.</p>
            ";
            
            $mail->send();
            
            // Send confirmation to patient
            $mail->clearAddresses();
            $mail->addAddress($appointment['pemail'], $appointment['pname']);
            $mail->Subject = 'Your Appointment ' . ucfirst($status) . ' Confirmation';
            $mail->Body = "
                <h3>Appointment " . ucfirst($status) . " Confirmation</h3>
                <p>Your appointment has been successfully $status.</p>
                <p><strong>Appointment Date:</strong> " . date('F j, Y', strtotime($appointment['appodate'])) . "</p>
                <p><strong>Appointment Time:</strong> " . date('g:i A', strtotime($appointment['appointment_time'])) . "</p>
                <p><strong>Procedure:</strong> {$appointment['procedure_name']}</p>
                <p><strong>Reason:</strong> $full_reason</p>
                <p>If this was a mistake or you'd like to reschedule, please contact our office.</p>
            ";
            
            $mail->send();
            
            // Redirect with success message
            if($source == 'patient') {
                header("Location: my_appointment.php?status=cancel_success");
            } else {
                header("Location: my_appointment.php?status=reject_success");
            }
            exit();
        } catch (Exception $e) {
            // Email failed but appointment was still processed
            if($source == 'patient') {
                header("Location: my_appointment.php?status=cancel_success_no_email");
            } else {
                header("Location: my_appointment.php?status=reject_success_no_email");
            }
            exit();
        }
    } else {
        if($source == 'patient') {
            header("Location: my_appointment.php?status=cancel_error");
        } else {
            header("Location: my_appointment.php?status=reject_error");
        }
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>