<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and bind - fetch password with initial query to avoid a second round trip
    $stmt = $conn->prepare("SELECT id, name, school_name, password FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $school_name, $stored_password);
        $stmt->fetch();

        $login_ok = false;

        if ($password === $stored_password) {
            $login_ok = true;
        }

        if ($login_ok) {
            // Login successful
            $_SESSION['teacher_id'] = $id;
            $_SESSION['teacher_name'] = $name;
            $_SESSION['school_name'] = $school_name;
            header("Location: TeacherAdmin.php");
            exit();
        } else {
            header("Location: TeacherLogin.html?error=Invalid password.");
            exit();
        }
    } else {
        header("Location: TeacherLogin.html?error=Teacher not found.");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
