<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["nurse_id"])) {
    header("Location: login.php");
    exit;
}

$nurse_id = $_SESSION["nurse_id"];
$baby = null;
$history = [];
$message = "";
$error = "";

/*
  Vaccine choices.
  A dose is disabled after it has been recorded for this baby.
*/
$vaccines = [
    "BCG" => ["Dose 1"],
    "OPV" => ["Dose 1", "Dose 2", "Dose 3", "Booster"],
    "IPV" => ["Dose 1", "Dose 2"],
    "Pentavalent (DTP-HepB-Hib)" => [
        "Dose 1", "Dose 2", "Dose 3"
    ],
    "PCV" => ["Dose 1", "Dose 2", "Dose 3"],
    "Rotavirus" => ["Dose 1", "Dose 2", "Dose 3"],
    "Measles-Rubella (MR)" => ["Dose 1", "Dose 2"],
    "HPV (Human Papillomavirus)" => ["Dose 1", "Dose 2"]
];

$baby_id = "";

/* Search baby by ID */
if (isset($_GET["baby_id"])) {
    $baby_id = trim($_GET["baby_id"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $baby_id = trim($_POST["baby_id"] ?? "");
}

/* Find the baby belonging to this nurse */
if ($baby_id !== "") {
    $stmt = $conn->prepare(
        "SELECT * FROM babies
         WHERE baby_id = ? AND nurse_id = ?"
    );
    $stmt->execute([$baby_id, $nurse_id]);
    $baby = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$baby) {
        $error = "Baby not found. Please check the Baby ID.";
    }
}

/* Save vaccination */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $baby) {
    $vaccine_name = $_POST["vaccine_name"] ?? "";
    $dose = $_POST["dose"] ?? "";
    $vaccination_date = $_POST["vaccination_date"] ?? "";
    $next_date = $_POST["next_vaccination_date"] ?: null;
    $notes = trim($_POST["notes"] ?? "");

    if (
        !isset($vaccines[$vaccine_name]) ||
        !in_array($dose, $vaccines[$vaccine_name], true)
    ) {
        $error = "Please select a valid vaccine and dose.";
    } elseif (
        !$vaccination_date ||
        $vaccination_date > date("Y-m-d")
    ) {
        $error = "Please enter a valid vaccination date.";
    } elseif (
        $next_date &&
        $next_date < $vaccination_date
    ) {
        $error = "The next vaccination date cannot be before the vaccination date.";
    } else {
        try {
            $check = $conn->prepare(
                "SELECT vaccination_id FROM vaccinations
                 WHERE baby_id = ?
                 AND vaccine_name = ?
                 AND dose = ?"
            );
            $check->execute([
                $baby["baby_id"],
                $vaccine_name,
                $dose
            ]);

            if ($check->fetch()) {
                $error = "This vaccine dose has already been recorded for this baby.";
            } else {
                $insert = $conn->prepare(
                    "INSERT INTO vaccinations
                    (baby_id, vaccine_name, dose,
                     vaccination_date, next_vaccination_date,
                     notes, nurse_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $insert->execute([
                    $baby["baby_id"],
                    $vaccine_name,
                    $dose,
                    $vaccination_date,
                    $next_date,
                    $notes,
                    $nurse_id
                ]);

                header("Location: add_vaccination.php?baby_id=" .
                    urlencode($baby["baby_id"]) . "&saved=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Could not save the vaccination. Please check the details.";
        }
    }
}

/* Show vaccination history */
if ($baby) {
    $stmt = $conn->prepare(
        "SELECT * FROM vaccinations
         WHERE baby_id = ?
         ORDER BY vaccination_date DESC, vaccination_id DESC"
    );
    $stmt->execute([$baby["baby_id"]]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_GET["saved"])) {
    $message = "Vaccination record saved successfully!";
}

/* Identify already recorded vaccine-dose combinations */
$taken = [];

foreach ($history as $record) {
    $taken[$record["vaccine_name"] . "|" . $record["dose"]] = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baby Vaccination</title>
    <link rel="stylesheet" href="assets/add_vaccination.css">
</head>

<body>

<div class="container">

    <h1>Baby Vaccination Management</h1>
    <p class="subtitle">Search for a baby and manage vaccination records.</p>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Search by Baby ID -->
    <div class="search-card">
        <h2>Search Baby</h2>

        <form method="GET" action="add_vaccination.php">
            <label>Enter Baby ID</label>
            <div class="search-row">
                <input
                    type="number"
                    name="baby_id"
                    min="1"
                    required
                    placeholder="Example: 1"
                    value="<?= htmlspecialchars($baby_id) ?>"
                >
                <button type="submit">Search Baby</button>
            </div>
        </form>
    </div>

    <?php if ($baby): ?>

        <!-- Baby details -->
        <div class="baby-card">
            <h2>Baby Information</h2>

            <div class="baby-details">
                <p><strong>Baby ID:</strong>
                    <?= htmlspecialchars($baby["baby_id"]) ?></p>

                <p><strong>Name:</strong>
                    <?= htmlspecialchars($baby["baby_name"]) ?></p>

                <p><strong>Date of Birth:</strong>
                    <?= htmlspecialchars($baby["date_of_birth"]) ?></p>

                <p><strong>Gender:</strong>
                    <?= htmlspecialchars($baby["gender"]) ?></p>

                <p><strong>Mother:</strong>
                    <?= htmlspecialchars($baby["mother_name"]) ?></p>
            </div>
        </div>

        <!-- Last vaccine -->
        <div class="history-card">
            <h2>Last Vaccine Taken</h2>

            <?php if (count($history) > 0): ?>
                <p>
                    <strong>Vaccine:</strong>
                    <?= htmlspecialchars($history[0]["vaccine_name"]) ?>
                </p>
                <p>
                    <strong>Dose:</strong>
                    <?= htmlspecialchars($history[0]["dose"]) ?>
                </p>
                <p>
                    <strong>Date:</strong>
                    <?= htmlspecialchars($history[0]["vaccination_date"]) ?>
                </p>
            <?php else: ?>
                <p>This baby has no vaccination records yet.</p>
            <?php endif; ?>
        </div>

        <!-- Full history -->
        <div class="history-card">
            <h2>Vaccination History</h2>

            <?php if ($history): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Dose</th>
                                <th>Date Given</th>
                                <th>Next Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars($record["vaccine_name"]) ?></td>
                                    <td><?= htmlspecialchars($record["dose"]) ?></td>
                                    <td><?= htmlspecialchars($record["vaccination_date"]) ?></td>
                                    <td><?= htmlspecialchars($record["next_vaccination_date"] ?? "Not set") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No vaccination history available.</p>
            <?php endif; ?>
        </div>

        <!-- Add vaccination -->
        <div class="form-card">
            <h2>Record a New Vaccination</h2>

            <form method="POST" action="add_vaccination.php">

                <input type="hidden" name="baby_id"
                    value="<?= htmlspecialchars($baby["baby_id"]) ?>">

                <label>Select Vaccine</label>
                <select name="vaccine_name" id="vaccine_name" required>
                    <option value="">Choose a vaccine</option>

                    <?php foreach ($vaccines as $vaccine => $doses): ?>
                        <option value="<?= htmlspecialchars($vaccine) ?>">
                            <?= htmlspecialchars($vaccine) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Select Dose</label>
                <select name="dose" id="dose" required disabled>
                    <option value="">First select a vaccine</option>
                </select>

                <label>Vaccination Date</label>
                <input type="date" name="vaccination_date"
                    max="<?= date("Y-m-d") ?>" required>

                <label>Next Vaccination Date (if applicable)</label>
                <input type="date" name="next_vaccination_date">

                <label>Notes</label>
                <textarea name="notes"
                    placeholder="Optional notes about this vaccination"></textarea>

                <button type="submit">Save Vaccination</button>
            </form>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

</div>

<script>
const vaccineDoses = <?= json_encode($vaccines) ?>;
const alreadyTaken = <?= json_encode(array_keys($taken)) ?>;

const vaccineSelect = document.getElementById("vaccine_name");
const doseSelect = document.getElementById("dose");

if (vaccineSelect && doseSelect) {
    vaccineSelect.addEventListener("change", function () {
        const selectedVaccine = this.value;

        doseSelect.innerHTML = "";
        doseSelect.disabled = !selectedVaccine;

        if (!selectedVaccine) {
            doseSelect.add(new Option("First select a vaccine", ""));
            return;
        }

        doseSelect.add(new Option("Choose a dose", ""));

        vaccineDoses[selectedVaccine].forEach(function (dose) {
            const key = selectedVaccine + "|" + dose;
            const isTaken = alreadyTaken.includes(key);

            const option = new Option(
                isTaken ? dose + " - Already given" : dose,
                dose
            );

            option.disabled = isTaken;
            doseSelect.add(option);
        });
    });
}
</script>

</body>
</html>