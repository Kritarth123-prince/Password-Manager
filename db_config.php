<?php
$db = new mysqli('localhost', 'root', '', 'password_manager');
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

$email_sender = 'your-email@gmail.com';
$email_password = 'your-mail-app-password';
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
?>
