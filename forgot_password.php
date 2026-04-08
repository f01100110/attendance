<?php
// forgot_password.php
// Password reset for school LAN systems (no email needed).
//
// HOW IT WORKS:
//   Step 1 — User enters their ID number and requests a reset.
//   Step 2 — Admin sees the request in manage_users.php and clicks "Allow Reset".
//   Step 3 — User comes back here, enters ID + new password, and resets it.
//
// WHY NO EMAIL: This system runs on localhost/LAN. Email servers are complex
// to set up. This admin-approval method is just as secure for a school setting.

session_start();
require_once 'config.php';

$error   = "";
$success = "";
$step    = "request"; // Two steps: "request" (ask for reset) or "reset" (set new password)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- STEP 1: User submits their ID number to request a reset ---
    if (isset($_POST['action']) && $_POST['action'] === 'request_reset') {
        $id_number = trim(htmlspecialchars($_POST['id_number']));

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_number = ? AND is_registered = 1");
        $stmt->execute([$id_number]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "ID number not found or not yet registered.";
        } else {
            // Mark account as "reset requested" — admin will see this in manage_users.php
            $pdo->prepare("UPDATE users SET reset_requested = 1 WHERE id_number = ?")
                ->execute([$id_number]);
            $success = "Reset request submitted. Please wait for your administrator to approve it, then come back here to set your new password.";
        }
    }

    // --- STEP 2: User sets a new password (only if admin approved the reset) ---
    if (isset($_POST['action']) && $_POST['action'] === 'do_reset') {
        $step      = "reset";
        $id_number = trim(htmlspecialchars($_POST['id_number']));
        $password  = $_POST['password'];
        $confirm   = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_number = ? AND is_registered = 1");
        $stmt->execute([$id_number]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "ID number not found.";

        } elseif (!$user['reset_allowed']) {
            // Admin has not approved the reset yet
            $error = "Your reset has not been approved yet. Please contact your administrator.";

        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";

        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";

        } else {
            // Save new password and clear the reset flags
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("
                UPDATE users
                SET password = ?, reset_requested = 0, reset_allowed = 0
                WHERE id_number = ?
            ")->execute([$hashed, $id_number]);

            header("Location: login.php?reset=1");
            exit();
        }
    }
}

// Determine which step to show based on URL or POST
if (isset($_GET['step']) && $_GET['step'] === 'reset') {
    $step = "reset";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — ICAS-MACOY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icas1.jpg">
</head>
<body class="auth-page">
    <div class="auth-container">

        <div class="auth-panel auth-panel--brand">
            <div class="brand-content">
                <div class="brand-logo">
                    <img src="assets/img/icas1.jpg" alt="ICAS-MACOY Logo">
                </div>
                <h1 class="brand-title">ICAS-MACOY</h1>
                <h2 class="brand-subtitle">QR ATTENDANCE SYSTEM</h2>
                <p class="brand-sub">Tap your ID. That's all it takes.</p>
            </div>
        </div>

        <div class="auth-panel auth-panel--form">
            <div class="form-box">

                <?php if ($step === "request"): ?>
                <!-- STEP 1: Request a reset -->
                <h2 class="form-title">Forgot Password?</h2>
                <p class="form-subtitle">
                    Enter your ID number to request a password reset.
                    Your administrator must approve the request before you can set a new password.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert--error"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert--success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST" action="forgot_password.php">
                    <input type="hidden" name="action" value="request_reset">
                    <div class="form-group">
                        <label for="id_number">ID Number</label>
                        <input type="text" id="id_number" name="id_number"
                            placeholder="e.g. 2021-00123" required>
                    </div>
                    <button type="submit" class="btn btn--primary btn--full">Request Reset</button>
                </form>

                <p class="form-footer">
                    Already approved? <a href="forgot_password.php?step=reset">Set new password</a>
                </p>

                <?php else: ?>
                <!-- STEP 2: Set new password (admin must have approved first) -->
                <h2 class="form-title">Set New Password</h2>
                <p class="form-subtitle">
                    Only proceed if your administrator has already approved your reset request.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert--error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="forgot_password.php" id="registerForm" novalidate>
                    <input type="hidden" name="action" value="do_reset">

                    <div class="form-group">
                        <label for="id_number">ID Number</label>
                        <input type="text" id="id_number" name="id_number"
                            placeholder="e.g. 2021-00123" required>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password"
                                placeholder="Minimum 8 characters" required>
                            <button type="button" class="toggle-pw" data-target="password">&#128065;</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Repeat new password" required>
                            <button type="button" class="toggle-pw" data-target="confirm_password">&#128065;</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn--primary btn--full">Reset Password</button>
                </form>

                <p class="form-footer">
                    <a href="forgot_password.php">Back to request form</a>
                </p>
                <?php endif; ?>

                <p class="form-footer"><a href="login.php">Back to Login</a></p>
            </div>
        </div>

    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
