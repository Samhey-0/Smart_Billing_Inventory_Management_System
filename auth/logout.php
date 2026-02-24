<?php
/**
 * Logout Page
 * 
 * Destroys session and redirects to login
 * 
 * @package InspireShoes
 * @version 1.0
 */

session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login
header('Location: /inspire-shoes/auth/login.php');
exit();
