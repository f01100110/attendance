<?php
// automatic detection ng role ng user pag login. walang role selector sa login form, kasi ang role ay naka-assign na sa database at hindi pwedeng baguhin ng user.

session_start();
require_once 'config.php';

$error = "";

// if login na ang user, redirect sa client dashboard or admin dashboard depending on role. para di na nila makita login page pag naka-login na sila
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: client/dashboard.php");
    }
    exit();
}

// submit form muna bago mag-check ng credentials
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_number = trim(htmlspecialchars($_POST["id_number"]));
    $password  = $_POST["password"];

    if (empty($id_number) || empty($password)) {
        $error = "Please enter your ID number and password.";

    } else {
        // hanap id_number
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_number = ?");
        $stmt->execute([$id_number]);
        $user = $stmt->fetch();

        if (!$user) {
            // not found - either wrong ID or not registered by admin yet
            $error = "ID number not found. Please check your ID or register first.";

        } elseif ($user["is_registered"] == 0) {
            $error = "This ID has not been registered yet. Please register first.";

        } elseif (!password_verify($password, $user["password"])) {
            $error = "Incorrect password. Please try again.";

        } else {
            // --- Login successful ---
            // storage of user info
            $_SESSION["user_id"]   = $user["id"];
            $_SESSION["id_number"] = $user["id_number"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"]      = $user["role"];

            // REDIRECTION admin to admin dashboard, student to client dashboard
            if ($user["role"] === "admin") {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: client/dashboard.php");
            }
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Attendance System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page"> <!-- AUTH PAGE for both login and register, para consistent ang design and hindi ma-inherit yung general styles (our problem last project)-->

    <div class="auth-container">

        <!-- branding -->
        <div class="auth-panel auth-panel--brand">
            <div class="brand-content">
                <div class="brand-logo"><img src="assets/img/icas1.jpg" alt="ICAS-MACOY Logo"></div>
                <h1 class="brand-title">ICAS-MACOY</h1>
                <h2 class="brand-subtitle">QR ATTENDANCE SYSTEM</h2>
                <p class="brand-sub">Tap your ID. That's all it takes.</p>
            </div>
        </div>

        <!-- login form -->
        <div class="auth-panel auth-panel--form">
            <div class="form-box">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Log in with your school ID number and password.</p>

                <!-- Error message -->
                <?php if ($error): ?>
                    <div class="alert alert--error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" id="loginForm" novalidate>

                    <div class="form-group">
                        <label for="id_number">ID Number</label>
                        <input
                            type="text"
                            id="id_number"
                            name="id_number"
                            placeholder="e.g. 2021-00123"
                            value="<?= isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : '' ?>"
                            required
                            autofocus
                        >
                    </div><!-- para ma-retain yung value ng id_number input pag may error, para di na nila kailangan i-type ulit-->

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="toggle-pw" data-target="password">&#128065;</button> <!-- mahiwagang mata -->
                        </div>
                    </div>

                    <button type="submit" class="btn btn--primary btn--full">Log In</button>

                </form>

                <p class="form-footer">No account yet? <a href="register.php">Register here</a></p>
            </div>
        </div>

    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>