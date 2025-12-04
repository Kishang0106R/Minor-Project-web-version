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

// Get current school details
$stmt = $conn->prepare("SELECT * FROM principals WHERE id = ?");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $principal_name_update = trim($_POST['principal_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $district = trim($_POST['district']);
    $zone = trim($_POST['zone']);
    $school_type = trim($_POST['school_type']);

    // Validate required fields
    if (empty($principal_name_update) || empty($email) || empty($mobile) || empty($address)) {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $message = "Please enter a valid 10-digit mobile number.";
        $message_type = "error";
    } else {
        // Check if email is already used by another school
        $stmt_check = $conn->prepare("SELECT id FROM principals WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $school_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "This email is already registered with another school.";
            $message_type = "error";
        } else {
            // Update school profile
            $stmt = $conn->prepare("UPDATE principals SET principal_name = ?, email = ?, mobile = ?, address = ?, district = ?, zone = ?, school_type = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $principal_name_update, $email, $mobile, $address, $district, $zone, $school_type, $school_id);

            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['principal_name'] = $principal_name_update;
                $_SESSION['school_name'] = $school['school_name']; // Keep school name same

                $message = "Profile updated successfully!";
                $message_type = "success";

                // Refresh school data
                $stmt_refresh = $conn->prepare("SELECT * FROM principals WHERE id = ?");
                $stmt_refresh->bind_param("i", $school_id);
                $stmt_refresh->execute();
                $school = $stmt_refresh->get_result()->fetch_assoc();
                $stmt_refresh->close();
            } else {
                $message = "Error updating profile: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update School Profile - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .form-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .form-section h2 {
            color: #c60000;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
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
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c60000;
        }
        .form-group input[readonly] {
            background: #f9f9f9;
            cursor: not-allowed;
        }
        .required {
            color: #c60000;
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
        .school-info-display {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .school-info-display h3 {
            color: #c60000;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p>Update School Profile - <?php echo htmlspecialchars($principal_name); ?> (Principal)</p>
        <div class="nav-links">
            <a href="PrincipalAdmin.php" class="btn">Home</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Current School Information -->
        <div class="school-info-display">
            <h3>Current School Information</h3>
            <div class="info-row">
                <span class="info-label">School Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['school_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">School Code:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['school_code'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Principal Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['principal_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['email'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Mobile:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['mobile'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">District:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['district'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Zone:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['zone'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">School Type:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['school_type'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Address:</span>
                <span class="info-value"><?php echo htmlspecialchars($school['address'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <!-- Update Form -->
        <div class="form-section">
            <h2>Update Profile Information</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="school_name">School Name <span class="required">*</span></label>
                        <input type="text" id="school_name" name="school_name" value="<?php echo htmlspecialchars($school['school_name'] ?? ''); ?>" readonly />
                        <small style="color: #666; font-size: 12px;">School name cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="school_code">School Code <span class="required">*</span></label>
                        <input type="text" id="school_code" name="school_code" value="<?php echo htmlspecialchars($school['school_code'] ?? ''); ?>" readonly />
                        <small style="color: #666; font-size: 12px;">School code cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="principal_name">Principal Name <span class="required">*</span></label>
                        <input type="text" id="principal_name" name="principal_name" value="<?php echo htmlspecialchars($school['principal_name'] ?? ''); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number <span class="required">*</span></label>
                        <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($school['mobile'] ?? ''); ?>" pattern="[0-9]{10}" required />
                        <small style="color: #666; font-size: 12px;">Enter 10-digit mobile number</small>
                    </div>

                    <div class="form-group">
                        <label for="district">District</label>
                        <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($school['district'] ?? ''); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="zone">Zone</label>
                        <input type="text" id="zone" name="zone" value="<?php echo htmlspecialchars($school['zone'] ?? ''); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="school_type">School Type</label>
                        <select id="school_type" name="school_type">
                            <option value="">Select School Type</option>
                            <option value="Government" <?php echo ($school['school_type'] ?? '') == 'Government' ? 'selected' : ''; ?>>Government</option>
                            <option value="Private" <?php echo ($school['school_type'] ?? '') == 'Private' ? 'selected' : ''; ?>>Private</option>
                            <option value="Aided" <?php echo ($school['school_type'] ?? '') == 'Aided' ? 'selected' : ''; ?>>Aided</option>
                            <option value="International" <?php echo ($school['school_type'] ?? '') == 'International' ? 'selected' : ''; ?>>International</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address <span class="required">*</span></label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($school['address'] ?? ''); ?>" required />
                </div>

                <div class="form-actions">
                    <a href="PrincipalAdmin.php" class="btn secondary">Cancel</a>
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
