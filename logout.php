<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Destroy session
session_destroy();

// Redirect to login page
setFlashMessage('success', 'You have been logged out successfully.');
redirect('login.php');
?>