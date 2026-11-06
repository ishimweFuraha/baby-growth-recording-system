<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM nurses WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);

    $nurse = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($nurse && password_verify($password, $nurse["password"])) {

        session_regenerate_id(true);

        $_SESSION["nurse_id"] = $nurse["nurse_id"];
        $_SESSION["nurse_name"] = $nurse["full_name"];

        header("Location: dashboard.php");
        exit;

    } else {
        $message = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Login | Baby Growth System</title>
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>

<div class="login-container">

    <div class="logo">
        <div class="logo-icon">♥</div>
        <h2>Baby Growth System</h2>
        <p>Nurse Login Portal</p>
    </div>

    <h3>Welcome Back!</h3>
    <p class="subtitle">Login to manage baby health records</p>

    <?php if (isset($_GET["registered"])): ?>
        <div class="success">
            Registration successful! Please login.
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="error">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Email Address</label>
        <input type="email" name="email"
               placeholder="Enter your email" required>

        <label>Password</label>
        <input type="password" name="password"
               placeholder="Enter your password" required>

        <button type="submit">Login</button>

    </form>

    <p class="register-link">
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

</body>
</html>
