<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    if (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO nurses
                    (full_name, email, phone, password)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $hashed_password
            ]);

            header("Location: login.php?registered=1");
            exit;

        } catch (PDOException $e) {
            $message = "Registration failed. Email may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nurse Registration</title>
    <link rel="stylesheet" href="assets/register.css">
</head>
<body>

<div class="container">
    <h2>Nurse Registration</h2>
    <p>Create your nurse account</p>

    <?php if ($message): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="full_name"
               placeholder="Full Name" required>

        <input type="email" name="email"
               placeholder="Email Address" required>

        <input type="health center" name="health center"
               placeholder="Health Center" required>

        <input type="tel" name="phone"
               placeholder="Phone Number" required>

        <input type="password" name="password"
               placeholder="Password (minimum 8 characters)"
               minlength="8" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account?
        <a href="login.php">Login here</a>
    </p>
</div>

</body>
</html>
