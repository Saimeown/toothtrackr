<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" href="../Media/Icon/ToothTrackr/ToothTrackr-white.png" type="image/png">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/Toothtrackr.css">
    <link rel="stylesheet" href="../css/loading.css">
    <script>
        // Prevent going back to dashboard after logout
        function preventBackAfterLogout() {
            window.history.forward();
        }
        
        // Execute when page loads
        window.onload = function() {
            preventBackAfterLogout();
        }
        
        // Execute when back/forward buttons are pressed
        window.onpageshow = function(event) {
            if (event.persisted) {
                // Page was loaded from cache (back button)
                window.location.reload();
            }
        };
    </script>

    <title>Log in - Toothtrackr</title>
</head>

<body>

<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

// Check if user is already logged in as admin
if (isset($_SESSION["user"]) && $_SESSION['usertype'] == 'p') {
    header("location: dashboard.php");
    exit();
}

$_SESSION["user"] = "";
$_SESSION["usertype"] = "";

// Set the new timezone
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d');
$_SESSION["date"] = $date;

// Import database connection
include("../connection.php");

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['useremail'];
    $password = $_POST['userpassword'];

    // Check the email in the webuser table
    $result = $database->query("SELECT * FROM webuser WHERE email='$email'");
    if ($result->num_rows == 1) {
        $utype = $result->fetch_assoc()['usertype'];

        // Only allow patients to log in
        if ($utype == 'p') {
            // Check patient credentials
            $checker = $database->query("SELECT * FROM patient WHERE pemail='$email'");
            if ($checker->num_rows == 1) {
                $patient = $checker->fetch_assoc();

                // Verify the hashed password
                if (password_verify($password, $patient['ppassword'])) {
                    // Check if the email is verified
                    if ($patient['is_verified'] == 1) {
                        // Set session and redirect to patient dashboard
                        $_SESSION['user'] = $email;
                        $_SESSION['usertype'] = 'p';
                        $_SESSION['username'] = $patient['pname'];
                        header('location: dashboard.php');
                        exit();
                    } else {
                        $error = "Please verify your email before logging in.";
                    }
                } else {
                    $error = "Wrong credentials: Invalid email or password.";
                }
            } else {
                $error = "Wrong credentials: Invalid email or password.";
            }
        } else {
            // Deny access for admin or doctor
            $error = "Access denied. Only patients can log in.";
        }
    } else {
        $error = "We can't find any account with this email.";
    }
}
?>

    <nav>
        <ul class="sidebar">
            <li onclick=hideSidebar()><a href="#"><img src="../Media/Icon/Black/navbar.png" class="navbar-logo" alt="Navigation Bar"></a></li>
            <li><a href="../ToothTrackr.php"><img src="../Media/Icon/ToothTrackr/name-blue.png" class="logo-name" alt="ToothTrackr"></a></li>
            <li><a href="../ToothTrackr.php">Home</a></li>
            <!--<li><a href="#">About</a></li>-->
            <li><a href="../ToothTrackr.php#services">Services</a></li>
            <li><a href="../ToothTrackr.php#contact">Contact</a></li>
            <li><a href="signup.php">Sign up</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
        <ul>
            <li><a href="../ToothTrackr.php"><img src="../Media/Icon/ToothTrackr/name-blue.png" class="logo-name" alt="ToothTrackr"></a>
            </li>
            <li class="hideOnMobile"><a href="../ToothTrackr.php">Home</a></li>
            <!--<li class="hideOnMobile"><a href="#">About</a></li> -->
            <li class="hideOnMobile"><a href="../ToothTrackr.php#services">Services</a></li>
            <li class="hideOnMobile"><a href="../ToothTrackr.php#contact">Contact</a></li>
            <li class="hideOnMobile"><a href="signup.php" class="reg-btn">Sign up</a></li>
            <li class="hideOnMobile"><a href="login.php" class="log-btn">Login</a></li>
            <li class="menu-button" onclick=showSidebar()><a href="#"><img src="../Media/Icon/Black/navbar.png"
                        class="navbar-logo" alt="Navigation Bar"></a></li>
        </ul>
    </nav>
    <script>
            function showSidebar() {
                const sidebar = document.querySelector('.sidebar')
                sidebar.style.display = 'flex'
            }
            function hideSidebar() {
                const sidebar = document.querySelector('.sidebar')
                sidebar.style.display = 'none'
            }
        </script>

    <div class="login-container">
        <div class="inside-container">
            <span class="login-logo"><img src="../Media/Icon/Blue/care.png"></span>
            <span class="login-header">Log in</span>
            <span class="login-header-admin">Songco Dental and Medical Clinic</span>
            <form action="" method="POST">
                <label for="email">Email</label>
                <input type="email" id="useremail" name="useremail" placeholder="Enter your email" required>
                <label for="password">Password</label>
                <input type="password" id="userpassword" name="userpassword" placeholder="Enter your password" required>
                <div class="error-message" style="<?php echo empty($error) ? 'display:none;' : ''; ?>">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($error)) {
                        echo '<p style="error-message">' . htmlspecialchars($error) . '</p>';
                    }
                    ?>
                </div>
                <input type="submit" value="Log in" class="login-btn">
                    <label for="" class="bottom-text">Don't have an account&#63; <a href="signup.php" class="signup-link">Sign up</a></label>
                    <label for="" class="bottom-text" style="margin-top: 10px;"><a href="forgot-password.php" class="signup-link">Forgot password?</a></label>
            </form>
        </div>
    </div>
    
</body>

</html>