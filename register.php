<?php
// register.php
// think it's that easy to register as admin? nah uh. if registered ka na by admin by using your id number and you try to register, automatic na non-admin role yan.

session_start();
require_once 'config.php';

$error = "";

// new syntax:
// $stmt = $pdo->prepare("SELECT * FROM users WHERE id_number = ?"); sa query may ? meaning placeholder e.g: SELECT * FROM users WHERE id_number = ?. why do we need this? instead of directly putting the variable in the query e.g: SELECT * FROM users WHERE id_number = '$id_number'
// the difference between $stmt = $pdo -> prepare and older syntax like msqli query is
// (which is vulnerable to sql injection kasi pwede mag-inject ng malicious sql code sa id_number variable by putting something like 2021-00123 or '1'='1 na kapag in-inject sa query magiging SELECT * FROM users WHERE id_number = '2021-00123' OR '1'='1' na magre-return ng lahat ng users sa database, which is a security risk)
// This way, the database knows to treat the variable as data and not as part of the SQL code, making it much safer against SQL injection attacks.
// post request means nag-submit na yung form, so we can start validating the inputs and processing the registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // collect and clean inputs from the form
    // trim() removes extra spaces, htmlspecialchars() prevents XSS attacks //what is this for? for security, anti-sql injection, anti-xss
    $id_number = trim(htmlspecialchars($_POST["id_number"]));
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm_password"];
    $full_name = trim(htmlspecialchars($_POST["full_name"]));

    // VALIDATION lang pala hanap mo eh
    if (empty($id_number) || empty($password) || empty($confirm) || empty($full_name)) {
        $error = "Please fill in all fields.";

    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    
    //requires user to enter a password that is at least 8 characters
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";

    } else {
        // if id number exists, consult your doctor. kainis na to. de, hanapin sa db yung id number 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_number = ?");
        $stmt->execute([$id_number]);
        $user = $stmt->fetch();

        if (!$user) {
        // error kapag id number not found sa db - either wrong ID or not registered by admin yet
        $error = "Your ID number is not enrolled in the system. Please contact your administrator.";

        } elseif ($user["is_registered"] == 1) {
            // account already registered
            $error = "This ID number is already registered. Please log in instead.";
        
        } else {
            // create account if id number exists but not registered. meaning registered na by admin sa manage_users
            // but not registered by the user
            // hash usual wahahaha
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // update lang; if registered is_registered = 1 para hindi paulit-ulit -___-
            $update = $pdo->prepare("
                UPDATE users
                SET full_name = ?, password = ?, is_registered = 1
                WHERE id_number = ?
            ");
            $update->execute([$full_name, $hashed_password, $id_number]);
            
            // redirects to login page
            header("Location: login.php?registered=1");
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
    <title>Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icas1.jpg">
</head>
<body class="auth-page">
    <div class="auth-container">

    <!-- left side -->
        <div class="auth-panel auth-panel--brand">
            <div class="brand-content">
                <div class="brand-logo"><img src="assets/img/icas1.jpg" alt="ICAS-MACOY Logo"></div>
                <h1 class="brand-title">ICAS-MACOY</h1>
                <h2 class="brand-subtitle">QR ATTENDANCE SYSTEM</h2>
                <p class="brand-sub">Tap your ID. That's all it takes.</p>
            </div>
        </div>

        <!-- right side -->
        <div class="auth-panel auth-panel--form">
            <div class="form-box">
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Your ID number must be pre-enrolled by your administrator.</p>

                <!-- display error message if there is an error -->
                <?php if ($error): ?>
                    <div class="alert alert--error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php" id="registerForm" novalidate>

                    <div class="form-group">
                        <label for="id_number">ID Number</label>
                        <input type="text" id="id_number" name="id_number"
                            placeholder="e.g. 2021-00123"
                            value="<?= isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : '' ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                            placeholder="e.g. Juan Dela Cruz"
                            value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>"
                            required
                            autofocus>
                    </div><!-- para ma-retain yung value ng input kahit may error sa form, so hindi na kailangan i-retype lahat kapag may mali lang sa password or something -->

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password"
                                placeholder="Minimum 8 characters" required>
                            <!-- mata -->
                            <button type="button" class="toggle-pw" data-target="password">&#128065;</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Repeat your password" required>
                            
                            <!-- basta yung mahiwagang mata na laging tinatanong -->
                            <button type="button" class="toggle-pw" data-target="confirm_password">&#128065;</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn--primary btn--full">Create Account</button>
                </form>

                <p class="form-footer">Already have an account? <a href="login.php">Log in here</a></p>
            </div>
        </div>

    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
