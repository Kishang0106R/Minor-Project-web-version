<?php
session_start();
include 'check_session_timeout.php';

// Check if school is logged in
if (!isset($_SESSION['school_id'])) {
    header("Location: SchoolLogin.php");
    exit();
}

// Check session timeout
check_session_timeout('school_login.php');

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'];
$principal_name = $_SESSION['principal_name'];

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

$message = "";
$message_type = "";

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all password fields.";
        $message_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirmation do not match.";
        $message_type = "error";
    } else {
        // Get current password hash from database
        $stmt = $conn->prepare("SELECT password FROM principals WHERE id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $school_data = $result->fetch_assoc();
        $stmt->close();

        if ($school_data && $current_password === $school_data['password']) {
            // Store new password in plain text

            // Update password
            $stmt_update = $conn->prepare("UPDATE principals SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_password, $school_id);

            if ($stmt_update->execute()) {
                $message = "Password changed successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating password: " . $conn->error;
                $message_type = "error";
            }
            $stmt_update->close();
        } else {
            $message = "Current password is incorrect.";
            $message_type = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Settings - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .settings-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        .settings-section h2 {
            color: #c60000;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #c60000;
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .btn {
            background: #c60000;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            margin: 0 10px;
        }
        .btn:hover {
            background: #a50000;
        }
        .btn.secondary {
            background: #6c757d;
        }
        .btn.secondary:hover {
            background: #545b62;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c60000;
        }
        .password-requirements h4 {
            color: #c60000;
            margin-bottom: 10px;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p>Account Settings - <?php echo htmlspecialchars($principal_name); ?> (Principal)</p>
        <div class="nav-links">
            <a href="PrincipalAdmin.php" class="btn">Home</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="settings-section">
            <h2>Change Password</h2>

            <div class="password-requirements">
                <h4>Password Requirements</h4>
                <ul>
                    <li>Minimum 6 characters long</li>
                    <li>Include a mix of letters, numbers, and special characters for better security</li>
                    <li>Avoid using easily guessable passwords</li>
                </ul>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required />
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required />
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required />
                </div>

                <div class="form-actions">
                    <a href="PrincipalAdmin.php" class="btn secondary">Cancel</a>
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Password strength indicator (optional enhancement)
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strength = password.length >= 6 ? (password.length >= 8 ? 'strong' : 'medium') : 'weak';

            // Remove existing classes
            this.classList.remove('password-weak', 'password-medium', 'password-strong');

            // Add appropriate class
            if (strength === 'weak') {
                this.classList.add('password-weak');
            } else if (strength === 'medium') {
                this.classList.add('password-medium');
            } else {
                this.classList.add('password-strong');
            }
        });
    </script>
</body>
</html>
