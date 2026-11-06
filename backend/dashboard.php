<?php
session_start();

// Protect the dashboard
if (!isset($_SESSION["nurse_id"])) {
    header("Location: login.php");
    exit;
}

$nurse_name = $_SESSION["nurse_name"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard | Baby Growth System</title>
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>

<div class="sidebar">
    <h2>♥ Baby Growth</h2>

    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="add_baby.php">Add Baby</a>
    <a href="view_babies.php">View Babies</a>
    <a href="add_vaccination.php">Add Vaccination</a>
    <a href="add_weight.php">Add Weight</a>
    <a href="settings.php">Settings</a>

    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="main-content">

    <header>
        <h2>Nurse Dashboard</h2>
        <div class="profile">
            Welcome, <strong>
                <?= htmlspecialchars($nurse_name) ?>
            </strong>
        </div>
    </header>

    <section class="welcome">
        <h1>Welcome to Baby Growth Recording System</h1>
        <p>
            Manage baby registration, vaccinations and growth records
            easily and efficiently.
        </p>
    </section>

    <h2 class="section-title">System Management</h2>

    <div class="cards">

        <div class="card">
            <div class="card-icon">👶</div>
            <h3>Register Baby</h3>
            <p>Add new babies and their parent information.</p>
            <a href="add_baby.php">Add Baby</a>
        </div>

        <div class="card">
            <div class="card-icon">💉</div>
            <h3>Vaccination Records</h3>
            <p>Record vaccinations given to registered babies.</p>
            <a href="add_vaccination.php">Add Vaccination</a>
        </div>

        <div class="card">
            <div class="card-icon">⚖️</div>
            <h3>Baby Weight</h3>
            <p>Record and monitor babies' weight measurements.</p>
            <a href="add_weight.php">Add Weight</a>
        </div>

        <div class="card">
            <div class="card-icon">📋</div>
            <h3>View Babies</h3>
            <p>View the babies registered in the system.</p>
            <a href="view_babies.php">View Babies</a>
        </div>

    </div>

</div>

</body>
</html>
