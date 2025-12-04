<?php
session_start();
if (isset($_SESSION['teacher_id'])) {
    $redirect = 'TeacherLogin.html';
} else {
    $redirect = 'UserLogin.html';
}
session_destroy();
header("Location: $redirect");
exit();
?>
