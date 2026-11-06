<?php
session_start();

if (!isset($_SESSION["nurse_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$message = "";
$message_type = "";
$baby = null;
$weights = [];

$baby_id = "";

// Search for a baby
if (isset($_GET["baby_id"])) {
    $baby_id = trim($_GET["baby_id"]);

    if ($baby_id !== "" && ctype_digit($baby_id)) {
        $stmt = $conn->prepare(
            "SELECT * FROM babies WHERE baby_id = ?"
        );
        $stmt->execute([$baby_id]);
        $baby = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$baby) {
            $message = "No baby found with this ID.";
            $message_type = "error";
        }
    } elseif ($baby_id !== "") {
        $message = "Please enter a valid Baby ID.";
        $message_type = "error";
    }
}

// Save new weight
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $baby_id = trim($_POST["baby_id"] ?? "");
    $weight = trim($_POST["weight"] ?? "");

    if (
        $baby_id === "" ||
        !ctype_digit($baby_id) ||
        !is_numeric($weight) ||
        (float)$weight <= 0
    ) {
        $message = "Please enter a valid Baby ID and weight.";
        $message_type = "error";
    } else {

        // Check whether baby exists
        $stmt = $conn->prepare(
            "SELECT * FROM babies WHERE baby_id = ?"
        );
        $stmt->execute([$baby_id]);
        $baby = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$baby) {
            $message = "Baby not found. Please check the Baby ID.";
            $message_type = "error";
        } else {

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO baby_weights (baby_id, weight)
                     VALUES (?, ?)"
                );

                $stmt->execute([
                    $baby_id,
                    $weight
                ]);

                $message = "Baby weight recorded successfully!";
                $message_type = "success";

            } catch (PDOException $e) {
                $message = "Unable to save weight. Please try again.";
                $message_type = "error";
            }
        }
    }
}

// Load previous weight records
if ($baby) {
    $stmt = $conn->prepare(
        "SELECT weight, recorded_date
         FROM baby_weights
         WHERE baby_id = ?
         ORDER BY recorded_date DESC"
    );

    $stmt->execute([$baby["baby_id"]]);
    $weights = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Record Baby Weight</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f4f7fb;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .top a {
            background: #64748b;
            color: white;
            padding: 11px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .box {
            background: white;
            padding: 25px;
            margin-top: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        h2 {
            color: #243b53;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin: 10px 0 15px;
            font-size: 15px;
        }

        button {
            background: #16865b;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #116c48;
        }

        .message {
            padding: 13px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .success {
            background: #d9fbe7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .baby-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 7px;
        }

        .info-item strong {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #eef2f7;
        }

        .weight-form {
            margin-top: 20px;
        }

        @media(max-width: 600px) {
            body {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="top">
        <h2>⚖️ Baby Weight Recording</h2>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Search Baby -->
    <div class="box">

        <h2>Search Baby</h2>

        <form method="GET" action="add_weight.php">

            <label>Enter Baby ID</label>

            <input
                type="number"
                name="baby_id"
                placeholder="Enter Baby ID"
                min="1"
                value="<?= htmlspecialchars($baby_id) ?>"
                required
            >

            <button type="submit">Search Baby</button>

        </form>

    </div>

    <?php if ($baby): ?>

        <!-- Baby Information -->
        <div class="box">

            <h2>Baby Information</h2>

            <div class="baby-info">

                <div class="info-item">
                    <strong>Baby ID</strong>
                    <?= htmlspecialchars($baby["baby_id"]) ?>
                </div>

                <div class="info-item">
                    <strong>Baby Name</strong>
                    <?= htmlspecialchars($baby["baby_name"]) ?>
                </div>

                <div class="info-item">
                    <strong>Gender</strong>
                    <?= htmlspecialchars($baby["gender"]) ?>
                </div>

                <div class="info-item">
                    <strong>Date of Birth</strong>
                    <?= htmlspecialchars($baby["date_of_birth"]) ?>
                </div>

                <div class="info-item">
                    <strong>Mother's Name</strong>
                    <?= htmlspecialchars($baby["mother_name"]) ?>
                </div>

            </div>

        </div>

        <!-- Record Weight -->
        <div class="box">

            <h2>Record New Weight</h2>

            <form method="POST" action="add_weight.php" class="weight-form">

                <input
                    type="hidden"
                    name="baby_id"
                    value="<?= htmlspecialchars($baby["baby_id"]) ?>"
                >

                <label>Current Baby Weight (Kilograms)</label>

                <input
                    type="number"
                    name="weight"
                    placeholder="Example: 5.50"
                    min="0.01"
                    max="999.99"
                    step="0.01"
                    required
                >

                <button type="submit">Save Weight Record</button>

            </form>

        </div>

        <!-- Weight History -->
        <div class="box">

            <h2>Previous Weight Records</h2>

            <?php if (count($weights) > 0): ?>

                <table>

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Weight (kg)</th>
                            <th>Recording Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($weights as $index => $record): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($record["weight"]) ?> kg
                                </td>

                                <td>
                                    <?= htmlspecialchars($record["recorded_date"]) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <p>No weight records found for this baby yet.</p>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
