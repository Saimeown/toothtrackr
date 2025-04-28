<?php
// Set the timezone
date_default_timezone_set('Asia/Singapore');
session_start();

// Check if user is logged in and is a patient
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
        header("location: login.php");
        exit;
    }
} else {
    header("location: ../login.php");
    exit;
}

// Include database connection
include("../connection.php");

// Get the email parameter and validate it
if (!isset($_GET['email']) || $_GET['email'] !== $_SESSION["user"]) {
    // Security check: Only allow users to download their own records
    header("Location: profile.php");
    exit;
}

$useremail = $_GET['email'];
$userrow = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

// Get medical history data
$medicalData = $database->query("SELECT * FROM medical_history WHERE email = '$useremail'");
$hasMedicalHistory = $medicalData->num_rows > 0;

if (!$hasMedicalHistory) {
    // If no medical history, redirect back to profile
    header("Location: profile.php");
    exit;
}

$medical = $medicalData->fetch_assoc();

// Include TCPDF library
require_once('../tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ToothTrackr');
$pdf->SetAuthor('ToothTrackr');
$pdf->SetTitle('Medical Record - ' . $username);
$pdf->SetSubject('Medical Record');

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set some language-dependent strings
$pdf->setLanguageArray(array('a_meta_charset' => 'UTF-8'));

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add a page
$pdf->AddPage();

// Create the medical record content
$html = '
<style>
    h1 {
        font-size: 22pt;
        text-align: center;
        color: #2b4c7e;
        font-weight: bold;
        border-bottom: 2px solid #2b4c7e;
        padding-bottom: 5px;
        margin-bottom: 20px;
    }
    h2 {
        font-size: 14pt;
        color: #2b4c7e;
        border-bottom: 1px solid #cccccc;
        padding-bottom: 3px;
        margin-top: 20px;
    }
    .info-section {
        margin-bottom: 15px;
    }
    .field-label {
        font-weight: bold;
        color: #555555;
    }
    .field-value {
        margin-bottom: 8px;
    }
    .section-header {
        background-color: #f2f6fc;
        padding: 5px;
        font-weight: bold;
        margin-top: 15px;
    }
    .signature-section {
        margin-top: 50px;
        border-top: 1px solid #cccccc;
        padding-top: 10px;
    }
    .signature-line {
        border-top: 1px solid #000000;
        width: 200px;
        margin-top: 50px;
        display: inline-block;
    }
    .signature-name {
        text-align: center;
        width: 200px;
        display: inline-block;
    }
    table {
        width: 100%;
        margin-top: 30px;
        border-collapse: collapse;
    }
    td {
        padding: 5px;
        vertical-align: top;
    }
    .col-left {
        width: 50%;
    }
    .col-right {
        width: 50%;
    }
</style>

<h1>MEDICAL RECORD</h1>

<h2>Patient Information</h2>
<table>
    <tr>
        <td class="col-left"><span class="field-label">Patient Name:</span><br/>' . $username . '</td>
        <td class="col-right"><span class="field-label">Patient ID:</span><br/>' . $userid . '</td>
    </tr>
    <tr>
        <td class="col-left"><span class="field-label">Email:</span><br/>' . $useremail . '</td>
        <td class="col-right"><span class="field-label">Phone:</span><br/>' . $userfetch['ptel'] . '</td>
    </tr>
    <tr>
        <td class="col-left"><span class="field-label">Date of Birth:</span><br/>' . $userfetch['pdob'] . '</td>
        <td class="col-right"><span class="field-label">Address:</span><br/>' . $userfetch['paddress'] . '</td>
    </tr>
</table>

<h2>Health Status</h2>
<div class="info-section">
    <div class="section-header">General Health Status</div>
    <div class="field-label">Are you in good health?</div>
    <div class="field-value">' . $medical['good_health'] . '</div>
    
    <div class="field-label">Are you under treatment for any medical condition?</div>
    <div class="field-value">' . $medical['under_treatment'] . '</div>
    
    <div class="field-label">Have you had any serious illness or operation?</div>
    <div class="field-value">' . $medical['serious_illness'] . '</div>
    
    <div class="field-label">Have you ever been hospitalized?</div>
    <div class="field-value">' . $medical['hospitalized'] . '</div>
</div>

<div class="info-section">
    <div class="section-header">Medications & Habits</div>
    <div class="field-label">Are you taking any medications, drugs, or pills?</div>
    <div class="field-value">' . $medical['medication'] . '</div>
    
    <div class="field-label">Do you use tobacco products?</div>
    <div class="field-value">' . $medical['tobacco'] . '</div>
    
    <div class="field-label">Do you use controlled substances (drugs)?</div>
    <div class="field-value">' . $medical['drugs'] . '</div>
</div>

<div class="info-section">
    <div class="section-header">Allergies & Conditions</div>
    <div class="field-label">Do you have any allergies?</div>
    <div class="field-value">' . (!empty($medical['allergies']) ? $medical['allergies'] : 'None reported') . '</div>
    
    <div class="field-label">Health Conditions:</div>
    <div class="field-value">' . (!empty($medical['health_conditions']) ? $medical['health_conditions'] : 'None reported') . '</div>
    
    <div class="field-label">Blood Pressure Issues:</div>
    <div class="field-value">' . (!empty($medical['blood_pressure']) ? $medical['blood_pressure'] : 'None reported') . '</div>
    
    <div class="field-label">Bleeding Time Issues:</div>
    <div class="field-value">' . (!empty($medical['bleeding_time']) ? $medical['bleeding_time'] : 'None reported') . '</div>
</div>

<div class="signature-section">
    <table>
        <tr>
            <td width="33%">
                <div class="signature-line"></div>
                <div class="signature-name">Patient Signature</div>
            </td>
            <td width="33%">
                <div class="signature-line"></div>
                <div class="signature-name">Doctor Signature</div>
            </td>
            <td width="33%">
                <div class="signature-line"></div>
                <div class="signature-name">Date</div>
            </td>
        </tr>
    </table>
</div>

<div style="text-align: center; margin-top: 30px; font-size: 10pt; color: #777777;">
    This medical record was generated on ' . date('F j, Y') . ' by ToothTrackr Dental System.<br>
    Confidential medical information - for authorized use only.
</div>
';

// Print HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('medical_record_' . $userid . '.pdf', 'D');
exit;
?>