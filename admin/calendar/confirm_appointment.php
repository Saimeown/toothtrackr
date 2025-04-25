<?php
require 'database_connection.php';
require '../../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


session_start();
if (!isset($_SESSION["user"]) || ($_SESSION["user"] == "" || $_SESSION['usertype'] != 'a')) {
    echo json_encode(['status' => false, 'msg' => 'Unauthorized access.']);
    exit;
}


function sendConfirmationEmail($patientEmail, $patientName, $appointmentDate, $appointmentTime, $dentistName, $procedureName) {
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
        $mail->Subject = 'Appointment Confirmed';
        $mail->Body = "Dear $patientName,<br><br>
                    Your appointment has been confirmed with <strong>$dentistName</strong> for:<br><br>
                    <strong>Procedure:</strong> $procedureName<br>
                    <strong>Date:</strong> $appointmentDate<br>
                    <strong>Time:</strong> $appointmentTime<br><br>
                    Please arrive 10 minutes before your scheduled time and bring any necessary documents.<br><br>
                    You can view your appointment details in your ToothTrackr account.<br><br>
                    Thank you for choosing Songco Dental and Medical Clinic.<br><br>
                    Sincerely,<br>
                    Songco Dental and Medical Clinic";


        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send confirmation email: " . $mail->ErrorInfo);
        return false;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appoid'])) {
    $appoid = intval($_POST['appoid']);


    // Get booking details with patient and dentist info
    $query = $con->prepare("
        SELECT a.*, p.pname, p.pemail, d.docname, pr.procedure_name
        FROM appointment a
        JOIN patient p ON a.pid = p.pid
        JOIN doctor d ON a.docid = d.docid
        JOIN procedures pr ON a.procedure_id = pr.procedure_id
        WHERE a.appoid = ? AND a.status = 'booking'
    ");
    $query->bind_param("i", $appoid);
    $query->execute();
    $result = $query->get_result();
    $booking = $result->fetch_assoc();


    if (!$booking) {
        echo json_encode(['status' => false, 'msg' => 'Booking not found or already processed.']);
        exit;
    }


    // Update status to 'appointment'
    $updateQuery = $con->prepare("UPDATE appointment SET status = 'appointment' WHERE appoid = ?");
    $updateQuery->bind_param("i", $appoid);
   
    if ($updateQuery->execute()) {
        // Send confirmation email
        $appodate = $booking['appodate'] !== null ? date('Y-m-d', strtotime($booking['appodate'])) : null;
        $emailSent = sendConfirmationEmail(
            $booking['pemail'],
            $booking['pname'],
            $appodate,
            $booking['appointment_time'],
            $booking['docname'],
            $booking['procedure_name']
        );
       
        $response = [
            'status' => true,
            'msg' => "Booking confirmed successfully.",
            'email_sent' => $emailSent
        ];
       
        if (!$emailSent) {
            $response['msg'] .= " (Failed to send confirmation email)";
        }
       
        echo json_encode($response);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Failed to confirm booking.']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'Invalid request.']);
}
?>
