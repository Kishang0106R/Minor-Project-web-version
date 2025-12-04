<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$dbname = "school_management_system"; // Database name

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

    // Prepare and bind - include password in initial select
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $stored_password);
        $stmt->fetch();

        $login_ok = false;

        if ($password === $stored_password) {
            $login_ok = true;
        }

        if ($login_ok) {
            // Start session and set user data
            session_start();
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Redirect to shop page
            header("Location: Shop.html");
            exit();
        } else {
            // Invalid password
            header("Location: UserLogin.html?error=Invalid password");
            exit();
        }
    } else {
        // User not found
        header("Location: UserLogin.html?error=User not found");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
