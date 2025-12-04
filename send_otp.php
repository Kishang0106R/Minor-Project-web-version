<?php
session_start();

// Generate a random OTP
$otp = rand(100000, 999999);

// Store OTP in session with email as key
$_SESSION['otp'] = $otp;
$_SESSION['otp_email'] = $_POST['email'];

// For demo purposes, just echo the OTP. In production, send via email.
echo "OTP sent to " . $_POST['email'] . ". (For demo, OTP is: " . $otp . ")";

// In production, use a mail library like PHPMailer to send the email
// Example:
// require 'PHPMailer/PHPMailerAutoload.php';
// $mail = new PHPMailer;
// ... configure and send email with $otp
?>
