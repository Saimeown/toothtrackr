<?php
require 'database_connection.php';
require '../../vendor/autoload.php'; // Include PHPMailer


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => false, 'msg' => 'User not logged in.']);
    exit;
}


function sendCancellationEmail($patientEmail, $patientName, $appointmentDate, $appointmentTime, $reason = "cancelled") {
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


        // Recipients
        $mail->setFrom('songcodent@gmail.com', 'ToothTrackr');
        $mail->addAddress($patientEmail, $patientName);


        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Appointment '.ucfirst($reason);
       
        if ($reason == 'cancelled') {
            $mail->Body = "Dear $patientName,<br><br>
                        We regret to inform you that your appointment scheduled for:<br><br>
                        <strong>Date:</strong> $appointmentDate<br>
                        <strong>Time:</strong> $appointmentTime<br><br>
                        has been cancelled by the clinic.<br><br>
                        Please contact us to reschedule or for more information.<br><br>
                        We apologize for any inconvenience.<br><br>
                        Sincerely,<br>
                        Songco Dental and Medical Clinic";
        } else {
            $mail->Body = "Dear $patientName,<br><br>
                        Your booking request for:<br><br>
                        <strong>Date:</strong> $appointmentDate<br>
                        <strong>Time:</strong> $appointmentTime<br><br>
                        has been rejected by the clinic.<br><br>
                        Please contact us to choose another time slot or for more information.<br><br>
                        Sincerely,<br>
                        Songco Dental and Medical Clinic";
        }


        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send cancellation email: " . $mail->ErrorInfo);
        return false;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appoid'])) {
    $appoid = intval($_POST['appoid']); // Ensure it's an integer


    // Fetch full appointment details with patient information
    $query = $con->prepare("
        SELECT a.*, p.pname, p.pemail
        FROM appointment a
        JOIN patient p ON a.pid = p.pid
        WHERE a.appoid = ?
    ");
    $query->bind_param("i", $appoid);
    $query->execute();
    $result = $query->get_result();
    $appointment = $result->fetch_assoc();


    if (!$appointment) {
        echo json_encode(['status' => false, 'msg' => 'Appointment not found.']);
        exit;
    }


    $newStatus = ($appointment['status'] === 'booking') ? 'rejected' : 'cancelled';


    // Insert appointment into the archive table
    $appodate = $appointment['appodate'] !== null ? date('Y-m-d', strtotime($appointment['appodate'])) : null;
    $archiveQuery = $con->prepare("
        INSERT INTO appointment_archive (appoid, pid, docid, apponum, scheduleid, appodate, appointment_time, procedure_id, event_name, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $archiveQuery->bind_param(
        "iiisisssss",
        $appointment['appoid'],
        $appointment['pid'],
        $appointment['docid'],
        $appointment['apponum'],
        $appointment['scheduleid'],
        $appodate,
        $appointment['appointment_time'],
        $appointment['procedure_id'],
        $appointment['event_name'],
        $newStatus
    );
   
    if ($archiveQuery->execute()) {
        // Now delete the original appointment
        $deleteQuery = $con->prepare("DELETE FROM appointment WHERE appoid = ?");
        $deleteQuery->bind_param("i", $appoid);
       
        if ($deleteQuery->execute()) {
            // Send cancellation email to patient
            $emailSent = sendCancellationEmail(
                $appointment['pemail'],
                $appointment['pname'],
                $appodate,
                $appointment['appointment_time'],
                $newStatus
            );
           
            $response = [
                'status' => true,
                'msg' => "Appointment moved to archive and deleted successfully.",
                'email_sent' => $emailSent
            ];
           
            if (!$emailSent) {
                $response['msg'] .= " (Failed to send notification email)";
            }
           
            echo json_encode($response);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Failed to delete the appointment.']);
        }
    } else {
        echo json_encode(['status' => false, 'msg' => 'Failed to archive the appointment.']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'Invalid request.']);
}
?>
