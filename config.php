<?php

$host     = "localhost";
$dbname   = "attendance_db";
$username = "root";
$password = "";

try {
    // trying a new method/connection. more secure than mysqli. pag di kaya edi revert to mysqli
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
