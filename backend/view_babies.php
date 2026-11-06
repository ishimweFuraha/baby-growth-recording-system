<?php
session_start();
require_once "db.php";

// Check nurse login
if (!isset($_SESSION["nurse_id"])) {
    header("Location: login.php");
    exit;
}

$nurse_id = $_SESSION["nurse_id"];

// Search functionality
$search = trim($_GET["search"] ?? "");

$sql = "SELECT * FROM babies
        WHERE nurse_id = ?
        AND (
            baby_name LIKE ?
            OR mother_name LIKE ?
            OR father_name LIKE ?
        )
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

$searchTerm = "%" . $search . "%";

$stmt->execute([
    $nurse_id,
    $searchTerm,
    $searchTerm,
    $searchTerm
]);

$babies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Babies | Baby Growth System</title>

    <link rel="stylesheet" href="assets/view_babies.css">
</head>

<body>

<div class="container">

    <header>
        <div>
            <h2>Registered Babies</h2>
            <p>View and search for registered babies.</p>
        </div>

        <a href="dashboard.php" class="back-btn">
            Back to Dashboard
        </a>
    </header>

    <div class="top-section">

        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                placeholder="Search baby or parent name..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <button type="submit">Search</button>

            <?php if ($search !== ""): ?>
                <a href="view_babies.php" class="reset-btn">Clear</a>
            <?php endif; ?>
        </form>

        <a href="add_baby.php" class="add-btn">
            + Register New Baby
        </a>

    </div>

    <div class="count">
        Total babies found: <strong><?= count($babies) ?></strong>
    </div>

    <?php if (count($babies) > 0): ?>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Baby Name</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Mother's Name</th>
                        <th>Mother's Phone</th>
                        <th>Father's Name</th>
                        <th>Father's Phone</th>
                        <th>Health Center</th>
                        <th>Location</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($babies as $index => $baby): ?>

                        <tr>
                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= htmlspecialchars($baby["baby_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["date_of_birth"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["gender"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["mother_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["mother_phone"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["father_name"] ?? "") ?: "Not provided" ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["father_phone"] ?? "") ?: "Not provided" ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($baby["health_center"] ?? "") ?: "Not provided" ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    implode(", ", array_filter([
                                        $baby["province"],
                                        $baby["district"],
                                        $baby["sector"],
                                        $baby["cell"],
                                        $baby["village"]
                                    ]))
                                ) ?: "Not provided" ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty">
            <h3>No Babies Found</h3>
            <p>
                No registered babies match your search.
                You can register a new baby.
            </p>

            <a href="add_baby.php" class="add-btn">
                Register Baby
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
