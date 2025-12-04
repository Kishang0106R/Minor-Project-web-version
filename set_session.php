<?php
session_start();
$_SESSION['teacher_id'] = 1;
$_SESSION['teacher_name'] = 'Test Teacher';
$_SESSION['school_name'] = 'Test School';
echo 'Session set for testing.';
?>
