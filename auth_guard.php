<?php
// handles session management and access control for both admin and client pages

session_start();

// SESSION EXPIRY
// Why this exists: If a student logs in on a shared school computer and forgets
// to log out, their account stays open. After 24 hours, the session automatically
// clears so no one else can access it.
// 1800 seconds = 30 minutes
// works on brave not on edge
// ayoko na
define('SESSION_LIFETIME', 1800);

function checkSessionExpiry($loginPath = '../login.php') {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];

        if ($elapsed > SESSION_LIFETIME) {
            // session joever - logging out the user
            // LOGIC: Clear session data and redirect to login with an "expired" message
            session_unset();
            session_destroy();
            // pass a message so login.php can tell the user why they were logged out
            header("Location: {$loginPath}?expired=1");
            exit();
        }
    }
    // update the last activity time on every page load
    $_SESSION['last_activity'] = time();
} 

// redirect to login if not logged in at all
function requireLogin($loginPath = '../login.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: $loginPath");
        exit();
    }
    checkSessionExpiry($loginPath);
}

// Only allow admins — redirect others to client dashboard
function guardAdmin() {
    requireLogin('../login.php');
    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../client/dashboard.php");
        exit();
    }
}

// Only allow non-admin users — redirect admins to admin dashboard
function guardClient() {
    requireLogin('../login.php');
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}
?>
