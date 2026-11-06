<?php
session_start();
require_once "db.php";

// Check nurse login
if (!isset($_SESSION["nurse_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$success = "";
$generated_baby_code = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Baby information
    $baby_name = trim($_POST["baby_name"] ?? "");
    $date_of_birth = $_POST["date_of_birth"] ?? "";
    $gender = $_POST["gender"] ?? "";

    // Mother information
    $mother_name = trim($_POST["mother_name"] ?? "");
    $mother_phone = trim($_POST["mother_phone"] ?? "");

    // Father information
    $father_name = trim($_POST["father_name"] ?? "");
    $father_phone = trim($_POST["father_phone"] ?? "");

    // Location information
    $health_center = trim($_POST["health_center"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $district = trim($_POST["district"] ?? "");
    $sector = trim($_POST["sector"] ?? "");
    $cell = trim($_POST["cell"] ?? "");
    $village = trim($_POST["village"] ?? "");

    // Validate date
    $date = DateTime::createFromFormat('!Y-m-d', $date_of_birth);
    $valid_date = $date && $date->format('Y-m-d') === $date_of_birth;

    if (!$valid_date || $date_of_birth > date('Y-m-d')) {

        $message = "Please enter a valid date of birth.";

    } elseif (
        $baby_name == "" ||
        $mother_name == "" ||
        $mother_phone == "" ||
        !in_array($gender, ["Male", "Female"], true)
    ) {

        $message = "Please complete the required information.";

    } else {

        try {

            // Start transaction
            $conn->beginTransaction();

            // Insert baby details
            $sql = "INSERT INTO babies (
                baby_name,
                date_of_birth,
                gender,
                mother_name,
                mother_phone,
                father_name,
                father_phone,
                health_center,
                province,
                district,
                sector,
                cell,
                village,
                nurse_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $baby_name,
                $date_of_birth,
                $gender,
                $mother_name,
                $mother_phone,
                $father_name,
                $father_phone,
                $health_center,
                $province,
                $district,
                $sector,
                $cell,
                $village,
                $_SESSION["nurse_id"]
            ]);

            // Get internal auto-increment baby ID
            $internal_id = $conn->lastInsertId();

            // Get baby's birth year
            $birth_year = (int)$date->format('Y');

            // Generate a unique sequence for each birth year
            $sequence_sql = "
                INSERT INTO baby_id_sequences
                    (birth_year, last_number)
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE
                    last_number = last_number + 1
            ";

            $sequence_stmt = $conn->prepare($sequence_sql);
            $sequence_stmt->execute([$birth_year]);

            // Retrieve the generated number
            $get_number = $conn->prepare("
                SELECT last_number
                FROM baby_id_sequences
                WHERE birth_year = ?
                FOR UPDATE
            ");

            $get_number->execute([$birth_year]);

            $baby_number = (int)$get_number->fetchColumn();

            // Generate public Baby Code
            // Example: 2026A0001
            $generated_baby_code = $birth_year . "A" .
                str_pad($baby_number, 4, "0", STR_PAD_LEFT);

            // Save public Baby Code
            $update = $conn->prepare("
                UPDATE babies
                SET baby_code = ?
                WHERE baby_id = ?
            ");

            $update->execute([
                $generated_baby_code,
                $internal_id
            ]);

            // Complete transaction
            $conn->commit();

            $success = "Baby registered successfully!";

        } catch (PDOException $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = "Registration failed. Please try again.";

            // Uncomment temporarily when debugging:
            // $message = $e->getMessage();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Baby | Baby Growth System</title>

    <link rel="stylesheet" href="assets/add_baby.css">
</head>

<body>

<div class="container">

    <div class="header">
        <h2>👶 Register New Baby</h2>
        <p>Enter the baby's and parents' information below.</p>
    </div>

    <!-- SUCCESS MESSAGE AND GENERATED BABY ID -->

    <?php if ($success): ?>

        <div class="success">

            <h3>✅ <?= htmlspecialchars($success) ?></h3>

            <p>The baby has been successfully registered.</p>

            <div style="
                background: #e8f5e9;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                margin-top: 15px;
            ">

                <p style="margin-bottom: 8px;">
                    Unique Baby Identification Number
                </p>

                <h2 style="
                    color: #16803c;
                    font-size: 28px;
                    letter-spacing: 3px;
                    margin: 10px 0;
                ">
                    <?= htmlspecialchars($generated_baby_code) ?>
                </h2>

                <p>
                    Give this Baby ID to the parent.
                    The nurse will use it to search for the baby's records.
                </p>

            </div>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($message): ?>

        <div class="error">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- BABY REGISTRATION FORM -->

    <form method="POST">

        <!-- BABY INFORMATION -->

        <h3>👶 Baby Information</h3>

        <div class="form-group">

            <label>Baby's Full Name</label>

            <input
                type="text"
                name="baby_name"
                placeholder="Enter baby's full name"
                required
            >

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Date of Birth</label>

                <input
                    type="date"
                    name="date_of_birth"
                    max="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Gender</label>

                <select name="gender" required>

                    <option value="">Select Gender</option>

                    <option value="Male">Male</option>

                    <option value="Female">Female</option>

                </select>

            </div>

        </div>


        <!-- MOTHER INFORMATION -->

        <h3>👩 Mother Information</h3>

        <div class="form-group">

            <label>Mother's Full Name</label>

            <input
                type="text"
                name="mother_name"
                placeholder="Enter mother's full name"
                required
            >

        </div>


        <div class="form-group">

            <label>Mother's Phone Number</label>

            <input
                type="tel"
                name="mother_phone"
                placeholder="Enter mother's phone number"
                required
            >

        </div>


        <!-- FATHER INFORMATION -->

        <h3>👨 Father Information</h3>

        <div class="form-group">

            <label>Father's Full Name</label>

            <input
                type="text"
                name="father_name"
                placeholder="Enter father's full name"
            >

        </div>


        <div class="form-group">

            <label>Father's Phone Number</label>

            <input
                type="tel"
                name="father_phone"
                placeholder="Enter father's phone number"
            >

        </div>


        <!-- HEALTH CENTER AND LOCATION -->

        <h3>🏥 Health Center and Location</h3>

        <div class="form-group">

            <label>Health Center</label>

            <input
                type="text"
                name="health_center"
                placeholder="Enter health center name"
            >

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Province</label>

                <input
                    type="text"
                    name="province"
                    placeholder="Enter province"
                >

            </div>


            <div class="form-group">

                <label>District</label>

                <input
                    type="text"
                    name="district"
                    placeholder="Enter district"
                >

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Sector</label>

                <input
                    type="text"
                    name="sector"
                    placeholder="Enter sector"
                >

            </div>


            <div class="form-group">

                <label>Cell</label>

                <input
                    type="text"
                    name="cell"
                    placeholder="Enter cell"
                >

            </div>

        </div>


        <div class="form-group">

            <label>Village</label>

            <input
                type="text"
                name="village"
                placeholder="Enter village"
            >

        </div>


        <!-- BUTTONS -->

        <div class="buttons">

            <a href="dashboard.php" class="back-btn">
                Back to Dashboard
            </a>

            <button type="submit">
                Register Baby
            </button>

        </div>

    </form>

</div>

</body>
</html>
