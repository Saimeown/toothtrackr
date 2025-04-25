<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user"])) {
    header("location: login.php");
    exit();
}

include("../connection.php");

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $type = $_GET['type'] ?? 'appointments';
    $page = max(1, intval($_GET['page'] ?? 1));
    $rowsPerPage = intval($_GET['rows_per_page'] ?? 10);
    $statusFilter = $_GET['status'] ?? 'all';
    $sortOrder = in_array($_GET['sort'] ?? 'DESC', ['ASC', 'DESC']) ? $_GET['sort'] : 'DESC';

    // Query setup
    switch ($type) {
        case 'appointments':
            $baseQuery = "SELECT a.*, p.pname, d.docname, pr.procedure_name 
                    FROM (
                        SELECT appoid, pid, docid, appodate, appointment_time, status, procedure_id 
                        FROM appointment 
                        WHERE status = 'completed'
                        UNION ALL
                        SELECT appoid, pid, docid, appodate, appointment_time, status, procedure_id 
                        FROM appointment_archive 
                        WHERE status IN ('cancelled', 'rejected')
                    ) a
                    LEFT JOIN patient p ON a.pid = p.pid
                    LEFT JOIN doctor d ON a.docid = d.docid
                    LEFT JOIN procedures pr ON a.procedure_id = pr.procedure_id"; 
            
            if ($statusFilter !== 'all') {
                $baseQuery .= " WHERE a.status = '" . $database->real_escape_string($statusFilter) . "'";
            }
            
            // Corrected countQuery
            $countQuery = "SELECT COUNT(*) FROM (
                SELECT status FROM appointment WHERE status = 'completed'
                UNION ALL
                SELECT status FROM appointment_archive WHERE status IN ('cancelled', 'rejected')
            ) AS combined";
            
            if ($statusFilter !== 'all') {
                $countQuery .= " WHERE status = '" . $database->real_escape_string($statusFilter) . "'";
            }
            
            $orderColumn = 'a.appodate';
            break;
        
        case 'dentists':
            $baseQuery = "SELECT * FROM doctor" . ($statusFilter !== 'all' ? " WHERE status = '" . $database->real_escape_string($statusFilter) . "'" : "");
            $countQuery = "SELECT COUNT(*) FROM doctor";
            $orderColumn = 'docid';
            break;
        
        case 'patients':
            $baseQuery = "SELECT * FROM patient" . ($statusFilter !== 'all' ? " WHERE status = '" . $database->real_escape_string($statusFilter) . "'" : "");
            $countQuery = "SELECT COUNT(*) FROM patient";
            $orderColumn = 'pid';
            break;
    }

    // Execute queries
    $total = $database->query($countQuery)->fetch_row()[0];
    $totalPages = ceil($total / $rowsPerPage);
    $offset = ($page - 1) * $rowsPerPage;
    $result = $database->query("$baseQuery ORDER BY $orderColumn $sortOrder LIMIT $rowsPerPage OFFSET $offset");

    // Build HTML
    $html = '<table class="sub-table"><thead><tr><th class="checkbox-column"><input type="checkbox" class="select-all"></th>';
    switch ($type) {
        case 'appointments': $html .= '<th>Patient</th><th>Dentist</th><th>Procedure</th><th>Date & Time</th><th>Status</th>'; break;
        case 'dentists': $html .= '<th>Name</th><th>Email</th><th>Phone</th><th>Status</th>'; break;
        case 'patients': $html .= '<th>Name</th><th>Email</th><th>Address</th><th>Birthdate</th><th>Status</th>'; break;
    }
    $html .= '</tr></thead><tbody>';

    while ($row = $result->fetch_assoc()) {
        $html .= '<tr><td><input type="checkbox" class="row-checkbox" value="' 
               . ($type === 'appointments' ? $row['appoid'] : ($type === 'dentists' ? $row['docid'] : $row['pid'])) 
               . '"></td>';
        
        switch ($type) {
            case 'appointments':
                $html .= '<td>' . htmlspecialchars($row['pname'] ?? 'N/A') . '</td>'
                      . '<td>' . htmlspecialchars($row['docname'] ?? 'N/A') . '</td>'
                      . '<td>' . htmlspecialchars($row['procedure_name'] ?? 'N/A') . '</td>' // Add procedure name
                      . '<td>' . htmlspecialchars(($row['appodate'] ?? '') . ' @ ' . substr($row['appointment_time'] ?? '', 0, 5)) . '</td>'
                      . '<td>' . ucfirst($row['status']) . '</td>';
                break;
            case 'dentists':
                $html .= '<td>' . htmlspecialchars($row['docname']) . '</td>'
                      . '<td>' . htmlspecialchars($row['docemail']) . '</td>'
                      . '<td>' . htmlspecialchars($row['doctel']) . '</td>'
                      . '<td>' . ucfirst($row['status']) . '</td>';
                break;
            case 'patients':
                $html .= '<td>' . htmlspecialchars($row['pname']) . '</td>'
                      . '<td>' . htmlspecialchars($row['pemail']) . '</td>'
                      . '<td>' . htmlspecialchars(substr($row['paddress'] ?? '', 0, 30)) . '</td>'
                      . '<td>' . htmlspecialchars($row['pdob'] ?? 'N/A') . '</td>' 
                      . '<td>' . ucfirst($row['status']) . '</td>';
                break;
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    // In your PHP code where you generate the pagination HTML, replace with:
    $pagination = '<div class="pagination"><span class="pagination-label">' . 
    (($page - 1) * $rowsPerPage + 1) . ' - ' . min($page * $rowsPerPage, $total) . 
    ' of ' . $total . '</span><div class="pagination-buttons">' .
    '<button class="pagination-button prev" ' . ($page <= 1 ? 'disabled' : '') . 
    ' data-page="' . ($page - 1) . '"></button>' .
    '<button class="pagination-button next" ' . ($page >= $totalPages ? 'disabled' : '') . 
    ' data-page="' . ($page + 1) . '"></button>' .
    '</div></div>';

    echo json_encode(['html' => $html, 'pagination' => $pagination]);
    exit();
}
$appointment_count = $database->query(
    "SELECT COUNT(*) FROM (
        SELECT appoid FROM appointment WHERE status = 'completed'
        UNION ALL
        SELECT appoid FROM appointment_archive WHERE status IN ('cancelled', 'rejected')
    ) AS combined"
)->fetch_row()[0];

$dentist_count = $database->query("SELECT COUNT(*) FROM doctor")->fetch_row()[0];
$patient_count = $database->query("SELECT COUNT(*) FROM patient")->fetch_row()[0];
// Regular page rendering continues below...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Media/white-icon/white-ToothTrackr_Logo.png" type="image/png">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <title>Archive - ToothTrackr</title>
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <style>
        .tab-container { margin: 20px 0; }
        .tab-button { 
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 5px;
        }
        .tab-button.active { 
            background:rgb(24, 17, 128); 
            color: white;
        }
        .table-controls { 
            margin: 15px 0;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .dash-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            table-layout: fixed;
        }
        .dash-body table th,
        .dash-body table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            overflow: hidden; /* Prevents content from overflowing */
            text-overflow: ellipsis; /* Adds ellipsis for overflowed text */
            white-space: nowrap; /* Prevents text from wrapping */
        }
        /* Checkbox column width */
        .dash-body table th.checkbox-column,
        .dash-body table td.checkbox-column {
            width: 30px; /* Fixed width for the checkbox column */
        }

        /* Appointments table (6 columns) */
        .dash-body #appointments-table th:nth-child(2),
        .dash-body #appointments-table td:nth-child(2) {
            width: 20%; /* Width for the Patient column */
        }

        .dash-body #appointments-table th:nth-child(3),
        .dash-body #appointments-table td:nth-child(3) {
            width: 20%; /* Width for the Dentist column */
        }

        .dash-body #appointments-table th:nth-child(4),
        .dash-body #appointments-table td:nth-child(4) {
            width: 20%; /* Width for the Procedure column */
        }

        .dash-body #appointments-table th:nth-child(5),
        .dash-body #appointments-table td:nth-child(5) {
            width: 20%; /* Width for the Date & Time column */
        }

        .dash-body #appointments-table th:nth-child(6),
        .dash-body #appointments-table td:nth-child(6) {
            width: 20%; /* Width for the Status column */
        }

        /* Dentists table (5 columns) */
        .dash-body #dentists-table th:nth-child(2),
        .dash-body #dentists-table td:nth-child(2) {
            width: 25%; /* Width for the Name column */
        }

        .dash-body #dentists-table th:nth-child(3),
        .dash-body #dentists-table td:nth-child(3) {
            width: 25%; /* Width for the Email column */
        }

        .dash-body #dentists-table th:nth-child(4),
        .dash-body #dentists-table td:nth-child(4) {
            width: 25%; /* Width for the Phone column */
        }

        .dash-body #dentists-table th:nth-child(5),
        .dash-body #dentists-table td:nth-child(5) {
            width: 25%; /* Width for the Status column */
        }

        /* Patients table (6 columns) */
        .dash-body #patients-table th:nth-child(2),
        .dash-body #patients-table td:nth-child(2) {
            width: 20%; /* Width for the Name column */
        }

        .dash-body #patients-table th:nth-child(3),
        .dash-body #patients-table td:nth-child(3) {
            width: 20%; /* Width for the Email column */
        }

        .dash-body #patients-table th:nth-child(4),
        .dash-body #patients-table td:nth-child(4) {
            width: 20%; /* Width for the Address column */
        }

        .dash-body #patients-table th:nth-child(5),
        .dash-body #patients-table td:nth-child(5) {
            width: 20%; /* Width for the Birthdate column */
        }

        .dash-body #patients-table th:nth-child(6),
        .dash-body #patients-table td:nth-child(6) {
            width: 20%; /* Width for the Status column */
        }

        th {
            background-color: #f8f9fa;
            position: relative;
        }
        .status-filter {
            padding: 5px;
            margin-left: 10px;
        }
        .checkbox-column {
            width: 30px;
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-bottom: 50px;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .loading-message { padding: 20px; text-align: center; }
        .dash-body {
            padding-left: 40px;
            padding-right: 40px;
            padding-top: 35px;
        }
        .archive-header-text{
            font-size: 40px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color:rgb(14, 23, 78);
        }
        .pagination-label {
            padding-right: 20px;
            color: #666;
            font-size: 14px;
        }
        .prev-btn {
            margin-right: 10px;
        }
        .pagination-buttons {
    display: flex;
    gap: 8px;
}

/* Update your pagination button styles */
.pagination-button {
    background: none;
    border: none;
    color:rgb(28, 20, 106);
    font-size: 25px;
    font-weight: 500;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.pagination-button:hover:not(:disabled) {
    background-color: #f0f0f0;
}

.pagination-button:disabled {
    color: #ccc;
    cursor: not-allowed;
    opacity: 0.7;
}

.pagination-button.prev::before {
    content: "←";
    margin-right: 5px;
}

.pagination-button.next::after {
    content: "→";
    margin-left: 5px;
}

/* Add this to your existing styles */
.dash-body table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.dash-body table tr:hover td {
    background-color: #f8f9fa;
}

.tab-button {
    padding: 10px 20px;
    background: #f0f0f0;
    border: none;
    cursor: pointer;
    margin-right: 5px;
    border-radius: 5px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.tab-button:hover {
    background: #e0e0e0;
}

.tab-button.active {
    background:rgb(34, 16, 97);
    color: white;
}

.status-filter, .rows-per-page {
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ced4da;
    background-color: white;
}

.generate-pdf {
    background-color:rgb(36, 28, 107);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.generate-pdf:hover {
    background-color:rgb(40, 33, 136);
}
       
/* Add this for loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-message {
    padding: 20px;
    text-align: center;
    color: #666;
}

.loading-message::before {
    content: "";
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(0,0,0,0.1);
    border-radius: 50%;
    border-top-color: #007bff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 10px;
    vertical-align: middle;
}
/* Alternative minimalist style */
.sort-btn {
    background: none;
    border: none;
    color: #666;
    font-size: 14px;
    cursor: pointer;
    padding: 6px 10px;
    transition: all 0.2s ease;
    position: relative;
}

.sort-btn:after {
    content: "";
    display: inline-block;
    width: 0;
    height: 0;
    margin-left: 5px;
    vertical-align: middle;
}

.sort-btn[data-order="DESC"]:after {
    border-top: 4px solid;
    border-right: 4px solid transparent;
    border-left: 4px solid transparent;
}

.sort-btn[data-order="ASC"]:after {
    border-bottom: 4px solid;
    border-right: 4px solid transparent;
    border-left: 4px solid transparent;
}

.sort-btn.active {
    color: rgb(36, 28, 107);
    font-weight: 600;
}

.sort-btn.active:after {
    border-top-color: rgb(36, 28, 107);
    border-bottom-color: rgb(36, 28, 107);
}
/* Table Container */
.table-wrapper {
    overflow-x: auto;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin: 20px 0;
}

/* Table Styling */
.sub-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
    background: white;
}

/* Table Header */
.sub-table thead th {
    background-color: rgb(36, 28, 107);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 500;
    position: sticky;
    top: 0;
    z-index: 10;
}

.sub-table thead th:first-child {
    border-top-left-radius: 8px;
}

.sub-table thead th:last-child {
    border-top-right-radius: 8px;
}

/* Table Cells */
.sub-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

/* Zebra Striping */
.sub-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

/* Hover Effect */
.sub-table tbody tr:hover {
    background-color: #f0f4ff;
    transition: background-color 0.2s ease;
}

/* Status Badges */
.sub-table td:last-child {
    font-weight: 500;
}

.sub-table td:last-child[data-status="completed"] {
    color: #28a745;
}

.sub-table td:last-child[data-status="cancelled"],
.sub-table td:last-child[data-status="rejected"],
.sub-table td:last-child[data-status="inactive"] {
    color: #dc3545;
}

.sub-table td:last-child[data-status="active"] {
    color: #28a745;
}

/* Checkbox Styling */
.checkbox-column {
    width: 40px;
    text-align: center;
}

.select-all, .row-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .sub-table {
        font-size: 13px;
    }
    
    .sub-table th, 
    .sub-table td {
        padding: 10px 8px;
    }
}

/* Date & Time Column */
.sub-table td:nth-last-child(2) {
    white-space: nowrap;
}

/* Status Column */
.sub-table td:last-child {
    text-transform: capitalize;
}

/* Table Controls */
.table-controls {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

/* Loading Animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-message {
    padding: 40px;
    text-align: center;
    color: #666;
    font-size: 16px;
}

.loading-message::before {
    content: "";
    display: inline-block;
    width: 24px;
    height: 24px;
    border: 3px solid rgba(36, 28, 107, 0.1);
    border-radius: 50%;
    border-top-color: rgb(36, 28, 107);
    animation: spin 1s ease-in-out infinite;
    margin-right: 10px;
    vertical-align: middle;
}
/* Update these styles in your CSS */
.sub-table thead th {
    background-color: rgb(36, 28, 107);
    color: white;
    padding: 10px 15px; /* Reduced from 15px to 10px vertically */
    text-align: left;
    font-weight: 500;
    position: sticky;
    top: 0;
    z-index: 10;
}

.sub-table td {
    padding: 10px 15px; /* Reduced from 15px to 10px vertically */
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

/* Reduce space above table */
.table-wrapper {
    overflow-x: auto;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin: 10px 0; /* Reduced from 20px */
}

/* Reduce space below table */
.tab-content {
    margin-bottom: 10px; /* Add this to reduce space after table */
}
.pagination {
    margin-bottom: 20px;
    padding-bottom: 20px;
}
/* Replace your existing .table-controls and pagination styles with these */
.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    flex-wrap: wrap;
    gap: 15px;
}

.table-controls-left {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.table-controls-right {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-left: auto;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0; /* Remove any default margins */
}

.pagination-label {
    padding-right: 10px;
    color: #666;
    font-size: 14px;
    white-space: nowrap;
}

.pagination-buttons {
    display: flex;
    gap: 5px;
}

.generate-pdf {
    margin-left: 15px; /* Add space between pagination and button */
}
/* Update these styles */
.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    flex-wrap: wrap;
    gap: 15px;
}

.table-controls-left {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.table-controls-right {
    display: flex;
    align-items: center;
    margin-left: auto;
}

.generate-pdf {
    margin-left: 15px;
    order: 1; /* Ensures it stays after the dropdown */
}

.pagination {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

/* Adjust the status filter and rows per page dropdowns */
.status-filter, .rows-per-page {
    margin-right: 0; /* Remove right margin since we're using gap */
}
/* Update these styles to reduce spacing */
.table-wrapper {
    margin: 5px 0; /* Reduced from 10px */
}

.tab-content {
    margin-bottom: 5px; /* Reduced from 10px */
}

.table-controls {
    margin: 10px 0; /* Reduced from 15px */
    padding: 10px; /* Reduced from 15px */
}

.pagination {
    margin: 5px 0; /* Reduced spacing */
    padding: 0; /* Remove padding */
}

/* Make the table controls more compact */
.table-controls-left {
    gap: 10px; /* Reduced from 15px */
}

.table-controls-right {
    gap: 8px; /* Reduced from 15px */
}

/* Adjust the generate PDF button */
.generate-pdf {
    margin-left: 10px; /* Reduced from 15px */
    padding: 6px 12px; /* Slightly smaller */
}

/* Make status filter and rows per page more compact */
.status-filter, .rows-per-page {
    padding: 6px 10px; /* Reduced from 8px 12px */
}

/* Reduce checkbox label spacing */
label > input[type="checkbox"] {
    margin-right: 5px;
}
/* Ultra-compact table controls */
.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 5px 0; /* Reduced from 10px */
    padding: 8px; /* Reduced from 10px */
    background: #f8f9fa;
    border-radius: 6px; /* Slightly smaller */
    flex-wrap: wrap;
    gap: 8px; /* Reduced from 10px */
}

.table-controls-left {
    display: flex;
    align-items: center;
    gap: 8px; /* Reduced from 10px */
    flex-wrap: wrap;
}

.table-controls-right {
    display: flex;
    align-items: center;
    margin-left: auto;
}

/* Super compact table spacing */
.table-wrapper {
    overflow-x: auto;
    border-radius: 6px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05); /* Lighter shadow */
    margin: 3px 0; /* Ultra tight */
}

/* Minimal pagination */
.pagination {
    display: flex;
    align-items: center;
    gap: 5px; /* Tight spacing */
    margin: 0;
    padding: 0;
}

.pagination-label {
    padding-right: 8px; /* Reduced */
    font-size: 13px; /* Slightly smaller */
}

/* Tiny buttons and inputs */
.generate-pdf {
    padding: 5px 10px; /* Very compact */
    font-size: 13px; /* Smaller text */
    margin-left: 8px; /* Tight spacing */
}

.status-filter, .rows-per-page {
    padding: 5px 8px; /* Very tight */
    font-size: 13px; /* Smaller */
}

.sort-btn {
    padding: 4px 8px; /* Minimal */
    font-size: 13px; /* Smaller */
}

/* Compact table cells */
.sub-table thead th {
    padding: 8px 12px; /* Tight */
}

.sub-table td {
    padding: 8px 12px; /* Tight */
}

/* Tiny checkboxes */
.checkbox-column {
    width: 25px; /* Smaller */
}

.select-all, .row-checkbox {
    width: 14px; /* Smaller */
    height: 14px; /* Smaller */
}

/* Minimal loading message */
.loading-message {
    padding: 10px; /* Reduced */
    font-size: 14px; /* Smaller */
}
.content-area {
    padding-left: 30px;
    margin-right: 30px;
}
    </style>
</head>
<body>
    <div class="nav-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="../Media/Icon/ToothTrackr/ToothTrackr.png" alt="ToothTrackr Logo">
            </div>

            <div class="user-profile">
                <div class="profile-image">
                    <img src="../Media/Icon/SDMC Logo.png" alt="Profile" class="profile-img">
                </div>
                <h3 class="profile-name">Songco Dental and Medical Clinic</h3>
                <p style="color: #777; margin: 0; font-size: 14px; text-align: center;">
                    Administrator
                </p>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <img src="../Media/Icon/Blue/home.png" alt="Home" class="nav-icon">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="dentist.php" class="nav-item">
                    <img src="../Media/Icon/Blue/dentist.png" alt="Dentist" class="nav-icon">
                    <span class="nav-label">Dentist</span>
                </a>
                <a href="patient.php" class="nav-item">
                    <img src="../Media/Icon/Blue/care.png" alt="Patient" class="nav-icon">
                    <span class="nav-label">Patient</span>
                </a>
                <a href="records.php" class="nav-item">
                    <img src="../Media/Icon/Blue/edit.png" alt="Records" class="nav-icon">
                    <span class="nav-label">Patient Records</span>
                </a>
                <a href="calendar/calendar.php" class="nav-item">
                    <img src="../Media/Icon/Blue/calendar.png" alt="Calendar" class="nav-icon">
                    <span class="nav-label">Calendar</span>
                </a>
                <a href="booking.php" class="nav-item">
                    <img src="../Media/Icon/Blue/booking.png" alt="Booking" class="nav-icon">
                    <span class="nav-label">Booking</span>
                </a>
                <a href="appointment.php" class="nav-item">
                    <img src="../Media/Icon/Blue/appointment.png" alt="Appointment" class="nav-icon">
                    <span class="nav-label">Appointment</span>
                </a>
                <a href="history.php" class="nav-item active">
                    <img src="../Media/Icon/Blue/folder.png" alt="Archive" class="nav-icon">
                    <span class="nav-label">Archive</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <img src="../Media/Icon/Blue/settings.png" alt="Settings" class="nav-icon">
                    <span class="nav-label">Settings</span>
                </a>
            </div>

            <div class="log-out">
                <a href="logout.php" class="nav-item">
                    <img src="../Media/Icon/Blue/logout.png" alt="Log Out" class="nav-icon">
                    <span class="nav-label">Log Out</span>
                </a>
            </div>
        </div>

        <div class="content-area">
        <div class="tab-container">
        <p class="archive-header-text">Archive</p>
            <button class="tab-button active" data-type="appointments">Appointments (<?= $appointment_count ?>)</button>
            <button class="tab-button" data-type="dentists">Dentists (<?= $dentist_count ?>)</button>
            <button class="tab-button" data-type="patients">Patients (<?= $patient_count ?>)</button>
        </div>

        <div id="appointments-content" class="tab-content active">
    <div class="table-controls">
        <div class="table-controls-left">
            <label><input type="checkbox" class="select-all" data-type="appointments"> Select All</label>
            <select class="rows-per-page" data-type="appointments">
                <option value="10">10 rows</option>
                <option value="20">20 rows</option>
                <option value="50">50 rows</option>
                <option value="100">100 rows</option>
            </select>
            <div class="sort-controls">
                <button class="sort-btn active" data-type="appointments" data-order="DESC">Newest</button>
                <button class="sort-btn" data-type="appointments" data-order="ASC">Oldest</button>
            </div>
            <select class="status-filter" data-type="appointments">
                <option value="all">All</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>
            <button class="generate-pdf btn-primary" data-type="appointments">Generate PDF</button>
        </div>
        
        <div class="table-controls-right">
            <div class="pagination" id="appointments-pagination"></div>
        </div>
    </div>
    
    <div class="table-wrapper" id="appointments-table"><div class="loading-message"></div></div>
</div>

        <!-- Dentists Tab -->
        <div id="dentists-content" class="tab-content">
            <div class="table-controls">
                <label><input type="checkbox" class="select-all" data-type="dentists"> Select All</label>
                <select class="rows-per-page" data-type="dentists">
                    <option value="10">10 rows</option>
                    <option value="20">20 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>
                <div class="sort-controls">
                    <button class="sort-btn active" data-type="dentists" data-order="DESC">↑ Newest</button>
                    <button class="sort-btn" data-type="dentists" data-order="ASC">↓ Oldest</button>
                </div>
                <select class="status-filter" data-type="dentists">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="generate-pdf btn-primary" data-type="dentists">Generate PDF</button>
            </div>
            <div class="table-wrapper" id="dentists-table"><div class="loading-message">Loading dentists...</div></div>
            <div class="pagination" id="dentists-pagination"></div>
        </div>

        <!-- Patients Tab -->
        <div id="patients-content" class="tab-content">
            <div class="table-controls">
                <label><input type="checkbox" class="select-all" data-type="patients"> Select All</label>
                <select class="rows-per-page" data-type="patients">
                    <option value="10">10 rows</option>
                    <option value="20">20 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>
                <div class="sort-controls">
                    <button class="sort-btn active" data-type="patients" data-order="DESC">↑ Newest</button>
                    <button class="sort-btn" data-type="patients" data-order="ASC">↓ Oldest</button>
                </div>
                <select class="status-filter" data-type="patients">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="generate-pdf btn-primary" data-type="patients">Generate PDF</button>
            </div>
            <div class="table-wrapper" id="patients-table"><div class="loading-message">Loading patients...</div></div>
            <div class="pagination" id="patients-pagination"></div>
        </div>
    </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        let currentTab = 'appointments';
        
        function loadTableData(type, page = 1) {
            const rowsPerPage = $(`#${type}-content .rows-per-page`).val();
            const statusFilter = $(`#${type}-content .status-filter`).val();
            const sortOrder = $(`#${type}-content .sort-btn.active`).data('order') || 'DESC';

            $.ajax({
            url: 'history.php',  // Changed from 'history_handler.php'
            method: 'GET',       // Changed from POST to GET
            data: { 
                ajax: 1,         // Add this parameter
                type: type,
                page: page,
                rows_per_page: rowsPerPage,
                status: statusFilter,
                sort: sortOrder 
            },
                beforeSend: () => $(`#${type}-table`).html('<div class="loading-message">Loading...</div>'),
                success: (response) => {
                    $(`#${type}-table`).html(response.html);
                    $(`#${type}-pagination`).html(response.pagination);
                    
                    // Update pagination buttons
                    $(`#${type}-pagination button`).off('click').on('click', function() {
                        loadTableData(type, $(this).data('page'));
                    });
                },
                error: () => $(`#${type}-table`).html('<div class="error">Error loading data</div>')
            });
        }

        // Initial load
        loadTableData(currentTab);

        // Tab switching
        $('.tab-button').click(function() {
            $('.tab-button, .tab-content').removeClass('active');
            $(this).addClass('active');
            currentTab = $(this).data('type');
            $(`#${currentTab}-content`).addClass('active');
            loadTableData(currentTab);
        });

        // Control handlers
        $(document).on('change', '.rows-per-page, .status-filter', function() {
            loadTableData($(this).data('type'));
        });

        $(document).on('click', '.sort-btn', function() {
            const type = $(this).data('type');
            $(`#${type}-content .sort-btn`).removeClass('active');
            $(this).addClass('active');
            loadTableData(type);
        });

        $(document).on('click', '.select-all', function() {
            const type = $(this).data('type');
            const checked = $(this).prop('checked');
            $(`#${type}-table .row-checkbox`).prop('checked', checked);
        });

        $(document).on('click', '.generate-pdf', function () {
            const type = $(this).data('type');
            const selected = $(`#${type}-table .row-checkbox:checked`).map((i, el) => el.value).get();

            console.log("Selected IDs:", selected); 
            console.log("Report Type:", type); 

            if (selected.length === 0) {
                alert('Please select items to generate PDF');
                return;
            }

            const form = $('<form>', { action: 'generate_pdf.php', method: 'POST' })
                .append($('<input>', { type: 'hidden', name: 'report_type', value: type }))
                .append($('<input>', { type: 'hidden', name: 'selected_ids', value: JSON.stringify(selected) }));

            console.log("Form Data:", form.serialize()); 

            $('body').append(form).submit();
            form.submit();
        });
    });
    </script>
</body>
</html>