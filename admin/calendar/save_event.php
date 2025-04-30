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

// Handle new event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_name'])) {
    // Sanitize and validate input data
    $event_name = trim($_POST['event_name']);
    $procedure = intval($_POST['procedure']);
    $patient_name = intval($_POST['patient_name']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $docid = intval($_POST['docid']);

    // Validate inputs
    if (empty($event_name) || $procedure <= 0 || $patient_name <= 0 || 
        empty($appointment_date) || empty($appointment_time) || $docid <= 0) {
        echo json_encode(['status' => false, 'msg' => 'All fields are required.']);
        exit;
    }

    // Check if the time slot is available
    $checkQuery = $con->prepare("SELECT appoid FROM appointment 
                                WHERE docid = ? AND appodate = ? AND appointment_time = ?
                                AND status IN ('appointment', 'booking')");
    $checkQuery->bind_param("iss", $docid, $appointment_date, $appointment_time);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['status' => false, 'msg' => 'This time slot is already booked.']);
        exit;
    }

    // Insert new appointment
    $query = $con->prepare("INSERT INTO appointment 
                           (pid, docid, appodate, appointment_time, procedure_id, event_name, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'appointment')");
    $query->bind_param("iissss", $patient_name, $docid, $appointment_date, 
                      $appointment_time, $procedure, $event_name);

    if ($query->execute()) {
        $appoid = $con->insert_id;
        
        // Fetch details for notification and email
        $detailsQuery = $con->prepare("
            SELECT p.pid, p.pname, p.pemail, d.docname, d.docemail, pr.procedure_name
            FROM appointment a
            JOIN patient p ON a.pid = p.pid
            JOIN doctor d ON a.docid = d.docid
            JOIN procedures pr ON a.procedure_id = pr.procedure_id
            WHERE a.appoid = ?
        ");
        $detailsQuery->bind_param("i", $appoid);
        $detailsQuery->execute();
        $result = $detailsQuery->get_result();
        $appointment = $result->fetch_assoc();

        if (!$appointment) {
            echo json_encode([
                'status' => true, 
                'msg' => 'Appointment created but could not fetch details for notification.'
            ]);
            exit;
        }

        // Format the date for display
        $formattedDate = date('F j, Y', strtotime($appointment_date));
        
        // Create notification for patient
        $notificationTitle = "New Appointment Confirmed";
        $notificationMessage = "Your appointment for " . $appointment['procedure_name'] . " with Dr. " . 
                             $appointment['docname'] . " on " . $formattedDate . " at " . 
                             $appointment_time . " has been confirmed.";
        
        $notificationQuery = $con->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, related_id, related_type, created_at, is_read)
            VALUES (?, 'p', ?, ?, ?, 'appointment', NOW(), 0)
        ");
        $notificationQuery->bind_param("issi", 
            $appointment['pid'], 
            $notificationTitle, 
            $notificationMessage, 
            $appoid
        );
        $notificationQuery->execute();

        // Send confirmation email
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
            $mail->addAddress($appointment['pemail'], $appointment['pname']);
            $mail->addCC($appointment['docemail'], $appointment['docname']);
            $mail->addCC('songcodent@gmail.com'); // CC to admin
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Appointment Confirmation';
            $mail->Body = "
                <h3>New Appointment Confirmation</h3>
                <p>Your appointment has been successfully scheduled with Songco Dental and Medical Clinic.</p>
                
                <h4>Appointment Details:</h4>
                <p><strong>Patient Name:</strong> {$appointment['pname']}</p>
                <p><strong>Dentist:</strong> Dr. {$appointment['docname']}</p>
                <p><strong>Date:</strong> $formattedDate</p>
                <p><strong>Time:</strong> $appointment_time</p>
                <p><strong>Procedure:</strong> {$appointment['procedure_name']}</p>
                
                <p>Please arrive 10 minutes before your scheduled time.</p>
                <p>If you need to reschedule or cancel, please contact us at least 24 hours in advance.</p>
                
                <p>Thank you for choosing Songco Dental and Medical Clinic!</p>
            ";
            
            $mail->send();
            echo json_encode([
                'status' => true, 
                'msg' => 'Appointment created successfully. Notification and confirmation sent to patient.'
            ]);
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            echo json_encode([
                'status' => true, 
                'msg' => 'Appointment created successfully. Notification created but email failed to send.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => false, 
            'msg' => 'Failed to create appointment. Database error: ' . $con->error
        ]);
    }
    exit;
}

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appoid'])) {
    $appoid = intval($_POST['appoid']);
    $reason = trim($_POST['cancel_reason']);
    $other_reason = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : '';
    $full_reason = ($reason == 'Other') ? "Other: " . $other_reason : $reason;

    if (empty($reason)) {
        echo json_encode(['status' => false, 'msg' => 'Cancellation reason is required.']);
        exit;
    }

    // Fetch full appointment details
    $query = $con->prepare("
        SELECT a.*, p.pid, p.pname, p.pemail, d.docname, d.docemail, pr.procedure_name
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

    // Archive the appointment
    $archiveQuery = $con->prepare("
        INSERT INTO appointment_archive 
        (appoid, pid, docid, appodate, appointment_time, 
         procedure_id, event_name, status, cancel_reason, archived_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    // Convert time format if needed
    $time_parts = explode('-', $appointment['appointment_time']);
    $start_time = trim($time_parts[0]) . ':00';
    if (strlen($start_time) === 7) {
        $start_time = '0' . $start_time;
    }

    $archiveQuery->bind_param(
        "iissssss",
        $appointment['appoid'],
        $appointment['pid'],
        $appointment['docid'],
        $appointment['appodate'],
        $start_time,
        $appointment['procedure_id'],
        $appointment['event_name'],
        $newStatus,
        $full_reason
    );

    if ($archiveQuery->execute()) {
        // Delete the original appointment
        $deleteQuery = $con->prepare("DELETE FROM appointment WHERE appoid = ?");
        $deleteQuery->bind_param("i", $appoid);
        
        if ($deleteQuery->execute()) {
            // Create notification for patient
            $notificationTitle = ($newStatus === 'rejected') ? "Booking Rejected" : "Appointment Cancelled";
            $notificationMessage = ($newStatus === 'rejected') 
                ? "Your booking for " . $appointment['procedure_name'] . " on " . 
                  date('M j, Y', strtotime($appointment['appodate'])) . " at " . 
                  date('g:i A', strtotime($appointment['appointment_time'])) . 
                  " has been rejected. Reason: " . $full_reason
                : "Your appointment for " . $appointment['procedure_name'] . " on " . 
                  date('M j, Y', strtotime($appointment['appodate'])) . " at " . 
                  date('g:i A', strtotime($appointment['appointment_time'])) . 
                  " has been cancelled. Reason: " . $full_reason;
            
            $notificationQuery = $con->prepare("
                INSERT INTO notifications (user_id, user_type, title, message, related_id, related_type, created_at, is_read)
                VALUES (?, 'p', ?, ?, ?, 'appointment', NOW(), 0)
            ");
            $notificationQuery->bind_param("issi", 
                $appointment['pid'], 
                $notificationTitle, 
                $notificationMessage, 
                $appoid
            );
            $notificationQuery->execute();

            // Send cancellation email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'songcodent@gmail.com';
                $mail->Password = 'gzdr afos onqq ppnv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('songcodent@gmail.com', 'ToothTrackr');
                $mail->addAddress($appointment['pemail'], $appointment['pname']);
                $mail->addCC($appointment['docemail'], $appointment['docname']);
                $mail->addCC('songcodent@gmail.com');
                
                $mail->isHTML(true);
                $mail->Subject = 'Appointment ' . ucfirst($newStatus) . ' Notification';
                $mail->Body = "
                    <h3>Appointment " . ucfirst($newStatus) . "</h3>
                    <p>We regret to inform you that your appointment has been $newStatus.</p>
                    
                    <h4>Original Appointment Details:</h4>
                    <p><strong>Patient:</strong> {$appointment['pname']}</p>
                    <p><strong>Dentist:</strong> Dr. {$appointment['docname']}</p>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointment['appodate'])) . "</p>
                    <p><strong>Time:</strong> {$appointment['appointment_time']}</p>
                    <p><strong>Procedure:</strong> {$appointment['procedure_name']}</p>
                    
                    <h4>Cancellation Details:</h4>
                    <p><strong>Reason:</strong> $full_reason</p>
                    
                    <p>We apologize for any inconvenience this may cause. Please contact us to reschedule.</p>
                    <p>Thank you for your understanding.</p>
                ";
                
                $mail->send();
                echo json_encode([
                    'status' => true,
                    'msg' => "Appointment has been $newStatus successfully. Notification sent to patient."
                ]);
            } catch (Exception $e) {
                error_log("Mailer Error: " . $e->getMessage());
                echo json_encode([
                    'status' => true,
                    'msg' => "Appointment has been $newStatus successfully. Notification created but email failed to send."
                ]);
            }
        } else {
            echo json_encode(['status' => false, 'msg' => 'Failed to delete the appointment.']);
        }
    } else {
        echo json_encode(['status' => false, 'msg' => 'Failed to archive the appointment.']);
    }
    exit;
}

// If neither creation nor cancellation request
echo json_encode(['status' => false, 'msg' => 'Invalid request.']);
?>