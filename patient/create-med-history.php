<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Media/white-icon/white-ToothTrackr_Logo.png" type="image/png">
    <title>Create Medical History - Toothtrackr</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 60%;
            margin: 2rem auto;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-text {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .sub-text {
            text-align: center;
            font-size: 1rem;
            color: #555;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 1rem;
        }

        input[type="radio"],
        input[type="checkbox"] {
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            margin-top: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .question {
            margin-bottom: 2rem;
        }

        .allergies {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}


        .allergies label {
            margin-right: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php
    session_start();

    if (!isset($_SESSION["user"]) || $_SESSION["usertype"] !== "p") {
        header('Location: login.php');
        exit();
    }

    include("../connection.php");

    $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Retrieve form data
        $good_health = $_POST['good_health'] ?? '';
        $under_treatment = $_POST['under_treatment'] ?? '';
        $condition_treated = $_POST['condition_treated'] ?? '';
        $serious_illness = $_POST['serious_illness'] ?? '';
        $hospitalized = $_POST['hospitalized'] ?? '';
        $medication = $_POST['medication'] ?? '';
        $medication_specify = $_POST['medication_specify'] ?? '';
        $tobacco = $_POST['tobacco'] ?? '';
        $drugs = $_POST['drugs'] ?? '';
        $allergies = $_POST['allergies'] ?? [];
        $blood_pressure = $_POST['blood_pressure'] ?? '';
        $bleeding_time = $_POST['bleeding_time'] ?? '';
        $health_conditions = $_POST['health_conditions'] ?? [];

        // Check if the form is filled out correctly
        if ($good_health === '' || $under_treatment === '') {
            $error = "Please answer all questions.";
        } else {
            // Insert medical history into database
            $email = $_SESSION["user"];

            $query = "INSERT INTO medical_history (email, good_health, under_treatment, condition_treated, serious_illness, hospitalized, medication, medication_specify, tobacco, drugs, allergies, blood_pressure, bleeding_time, health_conditions) 
                      VALUES ('$email', '$good_health', '$under_treatment', '$condition_treated', '$serious_illness', '$hospitalized', '$medication', '$medication_specify', '$tobacco', '$drugs', '" . implode(",", $allergies) . "', '$blood_pressure', '$bleeding_time', '" . implode(",", $health_conditions) . "')";
            $database->query($query);

            // Redirect to a confirmation page or another step if needed
            header('Location: informed-consent.php');
            exit();
        }
    }
    ?>

    <div class="container">
        <p class="header-text">Medical History</p>
        <p class="sub-text">Please fill out your medical history form below.</p>

        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- Question 1 -->
            <div class="form-group">
                <label for="good_health">Are you in good health? <span style="color: red">*</span></label>
                <input type="radio" name="good_health" value="Yes" required> Yes
                <input type="radio" name="good_health" value="No" required> No
            </div>

            <!-- Question 2 -->
            <div class="form-group">
                <label for="under_treatment">Are you under medical treatment now? <span style="color: red">*</span></label>
                <input type="radio" name="under_treatment" value="Yes" required> Yes
                <input type="radio" name="under_treatment" value="No" required> No
            </div>

            <!-- Condition if under treatment -->
            <div class="form-group" id="condition_treated_tr" style="display:none;">
                <label for="condition_treated">If yes, what condition is being treated? <span style="color: red">*</span></label>
                <input type="text" name="condition_treated" placeholder="Enter condition">
            </div>

            <!-- Question 3 -->
            <div class="form-group">
                <label for="serious_illness">Have you ever had a serious illness/surgical operation? <span style="color: red">*</span></label>
                <input type="radio" name="serious_illness" value="Yes" required> Yes
                <input type="radio" name="serious_illness" value="No" required> No
            </div>

            <!-- Question 4 -->
            <div class="form-group">
                <label for="hospitalized">Have you ever been hospitalized? <span style="color: red">*</span></label>
                <input type="radio" name="hospitalized" value="Yes" required> Yes
                <input type="radio" name="hospitalized" value="No" required> No
            </div>

            <!-- Question 5 -->
            <div class="form-group">
                <label for="medication">Are you taking any prescription/non-prescription medication? <span style="color: red">*</span></label>
                <input type="radio" name="medication" value="Yes" required> Yes
                <input type="radio" name="medication" value="No" required> No
            </div>

            <!-- Medication Specify -->
            <div class="form-group" id="medication_specify_tr" style="display:none;">
                <label for="medication_specify">If yes, please specify: <span style="color: red">*</span></label>
                <input type="text" name="medication_specify">
            </div>

            <!-- Question 6 -->
            <div class="form-group">
                <label for="tobacco">Do you use tobacco products? <span style="color: red">*</span></label>
                <input type="radio" name="tobacco" value="Yes" required> Yes
                <input type="radio" name="tobacco" value="No" required> No
            </div>

            <!-- Question 7 -->
            <div class="form-group">
                <label for="drugs">Do you use alcohol, cocaine, or other dangerous drugs? <span style="color: red">*</span></label>
                <input type="radio" name="drugs" value="Yes" required> Yes
                <input type="radio" name="drugs" value="No" required> No
            </div>

            <!-- Question 8 -->
            <div class="form-group">
                <label for="allergies">Are you allergic to the following? (Optional): </label>
                <div class="allergies">
                    <label><input type="checkbox" name="allergies[]" value="Local Anesthetics"> Local Anesthetics</label>
                    <label><input type="checkbox" name="allergies[]" value="Penicillin Products"> Penicillin Products</label>
                    <label><input type="checkbox" name="allergies[]" value="Sulfa Drugs"> Sulfa Drugs</label>
                    <label><input type="checkbox" name="allergies[]" value="Aspirin"> Aspirin</label>
                    <label><input type="checkbox" name="allergies[]" value="Latex"> Latex</label>
                    <label><input type="checkbox" name="allergies[]" value="Other"> Other: <input type="text" name="allergies_other"></label>
                </div>
            </div>

            <!-- Question 9 -->
            <div class="form-group">
                <label for="blood_pressure">Blood Pressure (Optional):</label>
                <input type="text" name="blood_pressure">

                <label for="bleeding_time">Bleeding Time (Optional):</label>
                <input type="text" name="bleeding_time">
            </div>

            <!-- Question 10 -->
            <div class="form-group">
                <label for="health_conditions">Do you have any of the following?</label>
                <div class="allergies">
                    <?php
                    $conditions = [
                        'High Blood Pressure', 'Low Blood Pressure', 'Epilepsy/Convulsions', 'AIDS/HIV Infection',
                        'Sexually Transmitted Disease', 'Stomach Ulcers', 'Fainting or Seizures', 'Radiation Therapy',
                        'Joint Replacement/Implant', 'Heart Surgery', 'Heart Attack', 'Thyroid Problem', 'Heart Disease',
                        'Heart Murmur', 'Hepatitis/Liver Disease', 'Rheumatic Fever', 'Hay Fever/Allergies',
                        'Respiratory Problems', 'Hepatitis/Jaundice', 'Tuberculosis', 'Kidney Disease', 'Diabetes',
                        'Chest Pain', 'Stroke', 'Cancer/Tumors', 'Anemia', 'Angina', 'Asthma', 'Emphysema', 'Bleeding Problem'
                    ];
                    foreach ($conditions as $condition) {
                        echo "<label><input type='checkbox' name='health_conditions[]' value='$condition'> $condition</label>";
                    }
                    ?>
                </div>
            </div>

            <button type="submit">Submit</button>
        </form>
    </div>

    <script>
        // Show/Hide additional input fields based on answers
        document.querySelector('input[name="under_treatment"]').addEventListener('change', function () {
            document.getElementById('condition_treated_tr').style.display = this.value === 'Yes' ? 'block' : 'none';
        });

        document.querySelector('input[name="medication"]').addEventListener('change', function () {
            document.getElementById('medication_specify_tr').style.display = this.value === 'Yes' ? 'block' : 'none';
        });
    </script>
</body>

</html>
