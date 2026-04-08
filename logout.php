<?php

session_start();
session_unset();    // RESET YARN? ginagamit to clear all session variables, like user_id (yung column sa db). pag nag-logout, minsan may natitirang info sa input fields
session_destroy();  // redirect lang sa login page pagkatapos mag-logout, para di na nila makita yung dashboard kapag nag-logout na sila. at saka para ma-clear yung session info nila.

header("Location: login.php");
exit();
?>
