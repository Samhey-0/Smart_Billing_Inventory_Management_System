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
header('Location: /Smart_Billing_Inventory_Management_System/auth/login.php');
exit();
