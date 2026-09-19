<?php
session_start();
session_unset();    // Remove all session variables
session_destroy();  // Destroy the session entirely

// Redirect back to the login page
header("Location: signin.html");
exit();
?>