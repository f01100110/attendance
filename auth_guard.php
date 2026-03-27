<?php
session_start();

// login 
function requireLogin($loginPath = '../login.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: $loginPath");
        exit();
    }
}

// admins only — redirect others to client dashboard
function guardAdmin() {
    requireLogin('../login.php');
    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../client/dashboard.php");
        exit();
    }
}

// user only b**** — redirect admins to admin dashboard
function guardClient() {
    requireLogin('../login.php');
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}
?>
