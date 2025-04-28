<?php
require 'database_connection.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => false, 'msg' => 'User not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appoid'])) {
    $appoid = intval($_POST['appoid']);
    $reason = $_POST['cancel_reason'];
    $other_reason = isset($_POST['other_reason']) ? $_POST['other_reason'] : '';
    $full_reason = ($reason == 'Other') ? "Other: " . $other_reason : $reason;

    // Fetch full appointment details with patient and doctor information
    $query = $con->prepare("
        SELECT a.*, p.pname, p.pemail, d.docname, d.docemail, pr.procedure_name
        FROM appointment a
        JOIN patient p ON a.pid = p.pid
        JOIN doctor d ON a.docid = d.docid
        JOIN procedures pr ON a.procedure_id = pr.procedure_id
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

    // Insert appointment into the archive table with reason
    $archiveQuery = $con->prepare("
    INSERT INTO appointment_archive 
    (appoid, pid, docid, appodate, appointment_time, 
    procedure_id, event_name, status, cancel_reason, archived_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // Convert the time format before binding
    $time_parts = explode('-', $appointment['appointment_time']);
    $start_time = trim($time_parts[0]) . ':00'; // Convert "9:00" to "9:00:00"

    $archiveQuery->bind_param(
    "iiissssss",
    $appointment['appoid'],
    $appointment['pid'],
    $appointment['docid'],
    $appointment['appodate'],
    $start_time, // Use the converted time format
    $appointment['procedure_id'],
    $appointment['event_name'],
    $newStatus,
    $full_reason
    );

    if ($archiveQuery->execute()) {
        // Now delete the original appointment
        $deleteQuery = $con->prepare("DELETE FROM appointment WHERE appoid = ?");
        $deleteQuery->bind_param("i", $appoid);
        
        if ($deleteQuery->execute()) {
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
                $mail->Subject = 'Appointment ' . ucfirst($newStatus) . ' Notification';
                $mail->Body = "
                    <h3>Appointment " . ucfirst($newStatus) . " Notification</h3>
                    <p>Your appointment has been $newStatus by the clinic.</p>
                    <p><strong>Patient Name:</strong> {$appointment['pname']}</p>
                    <p><strong>Dentist Name:</strong> {$appointment['docname']}</p>
                    <p><strong>Appointment Date:</strong> " . date('F j, Y', strtotime($appointment['appodate'])) . "</p>
                    <p><strong>Appointment Time:</strong> {$appointment['appointment_time']}</p>
                    <p><strong>Procedure:</strong> {$appointment['procedure_name']}</p>
                    <p><strong>Reason:</strong> $full_reason</p>
                    <p>Please contact the clinic if you wish to reschedule or for more information.</p>
                ";
                
                $mail->send();
                
                echo json_encode([
                    'status' => true,
                    'msg' => "Appointment has been $newStatus successfully. Notification sent."
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'status' => true,
                    'msg' => "Appointment has been $newStatus successfully, but failed to send notification email."
                ]);
            }
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