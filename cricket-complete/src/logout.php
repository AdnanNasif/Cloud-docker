<?php
session_start();         // Start the session (if not already started)
session_unset();         // Unset all session variables
session_destroy();       // Destroy the session

// Redirect to homepage
header("Location: index.html");
exit();
?>
