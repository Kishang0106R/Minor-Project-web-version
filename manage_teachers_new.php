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

// Handle adding teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $subject = trim($_POST['subject']);
    $designation = trim($_POST['designation']);
    $class_assigned = trim($_POST['class_assigned']);
    $mobile = trim($_POST['mobile']);

    // Enhanced validation
    $errors = [];
    
    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters long.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Password requirements: 8+ chars, uppercase, lowercase, number, special char
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.";
    }
    
    if ($mobile && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    if (empty($errors)) {
        // Check if email already exists
        $stmt_check = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "A teacher with this email already exists.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO teachers (name, email, password, subject, school_name, designation, class_assigned, mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $name, $email, $password, $subject, $school_name, $designation, $class_assigned, $mobile);

            if ($stmt->execute()) {
                $message = "Teacher added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding teacher: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $message = "Please correct the following errors:<br>" . implode("<br>", $errors);
        $message_type = "error";
    }
}

// Rest of the existing code for edit/delete handlers...

// Fetch teachers for this school
$teachers = [];
$stmt = $conn->prepare("SELECT id, name, email, subject, designation, class_assigned, mobile, reg_date FROM teachers WHERE school_name = ? ORDER BY name");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .form-section form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #4b6cb7;
            outline: none;
            box-shadow: 0 0 0 2px rgba(75,108,183,0.1);
        }
        
        .form-group .hint {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .form-group.required label:after {
            content: " *";
            color: #dc3545;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
        
        .password-requirements li {
            margin-bottom: 3px;
        }
        
        .invalid-feedback {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
        
        .edit-form {
            display: none;
        }
        
        .edit-form.show {
            display: block;
        }
        
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p>Manage Teachers - <?php echo htmlspecialchars($principal_name); ?> (Principal)</p>
        <div class="nav-links">
            <a href="PrincipalAdmin.php" class="btn">Home</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>" style="display: block;"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Add Teacher Form -->
        <div class="form-section">
            <h2>Add New Teacher</h2>
            <form method="POST" action="" id="addTeacherForm" onsubmit="return validateTeacherForm()">
                <div class="form-row">
                    <div class="form-group required">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required minlength="3" />
                        <div class="hint">Enter full name as per records</div>
                        <div class="invalid-feedback">Name must be at least 3 characters long</div>
                    </div>
                    <div class="form-group required">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required />
                        <div class="hint">School or personal email address</div>
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group required">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="8" />
                        <div class="password-requirements">
                            Password must contain:
                            <ul>
                                <li>At least 8 characters</li>
                                <li>One uppercase letter</li>
                                <li>One lowercase letter</li>
                                <li>One number</li>
                                <li>One special character</li>
                            </ul>
                        </div>
                        <div class="invalid-feedback">Password does not meet requirements</div>
                    </div>
                    <div class="form-group required">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required />
                        <div class="hint">Re-enter password to confirm</div>
                        <div class="invalid-feedback">Passwords do not match</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" />
                        <div class="hint">Main subject taught by the teacher</div>
                    </div>
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <input type="text" id="designation" name="designation" />
                        <div class="hint">Current role or position</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="class_assigned">Class Assigned</label>
                        <input type="text" id="class_assigned" name="class_assigned" />
                        <div class="hint">Primary class or grade assigned</div>
                    </div>
                    <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" />
                        <div class="hint">10-digit mobile number</div>
                        <div class="invalid-feedback">Please enter a valid 10-digit mobile number</div>
                    </div>
                </div>
                
                <button type="submit" name="add_teacher" class="btn" style="margin-top: 10px;">Add Teacher</button>
            </form>
        </div>

        <!-- Teachers List (existing code) -->
        <div class="teams-section">
            <h2>Teachers (<?php echo count($teachers); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Designation</th>
                        <th>Class</th>
                        <th>Mobile</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666;">No teachers added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['subject'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['designation'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['class_assigned'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['mobile'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="btn green" onclick="editTeacher(<?php echo $teacher['id']; ?>)">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this teacher?')">
                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>" />
                                        <button type="submit" name="delete_teacher" class="delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Edit Form (hidden by default) -->
                            <tr class="edit-form" id="edit-form-<?php echo $teacher['id']; ?>">
                                <td colspan="7">
                                    <form method="POST" action="">
                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>" />
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($teacher['name']); ?>" required />
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required />
                                            <input type="text" name="subject" value="<?php echo htmlspecialchars($teacher['subject'] ?? ''); ?>" />
                                            <input type="text" name="designation" value="<?php echo htmlspecialchars($teacher['designation'] ?? ''); ?>" />
                                            <input type="text" name="class_assigned" value="<?php echo htmlspecialchars($teacher['class_assigned'] ?? ''); ?>" />
                                            <input type="text" name="mobile" value="<?php echo htmlspecialchars($teacher['mobile'] ?? ''); ?>" />
                                        </div>
                                        <button type="submit" name="edit_teacher" class="btn">Update Teacher</button>
                                        <button type="button" onclick="cancelEdit(<?php echo $teacher['id']; ?>)" class="delete-btn">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editTeacher(teacherId) {
            document.getElementById('edit-form-' + teacherId).classList.add('show');
        }

        function cancelEdit(teacherId) {
            document.getElementById('edit-form-' + teacherId).classList.remove('show');
        }

        function validateTeacherForm() {
            const form = document.getElementById('addTeacherForm');
            let isValid = true;
            
            // Reset previous error states
            form.querySelectorAll('.invalid-feedback').forEach(el => el.style.display = 'none');
            
            // Validate name
            const name = form.querySelector('#name');
            if (name.value.length < 3) {
                name.nextElementSibling.nextElementSibling.style.display = 'block';
                isValid = false;
            }
            
            // Validate email
            const email = form.querySelector('#email');
            if (!email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                email.nextElementSibling.nextElementSibling.style.display = 'block';
                isValid = false;
            }
            
            // Validate password
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            
            if (!password.value.match(passwordRegex)) {
                password.nextElementSibling.nextElementSibling.style.display = 'block';
                isValid = false;
            }
            
            if (password.value !== confirmPassword.value) {
                confirmPassword.nextElementSibling.nextElementSibling.style.display = 'block';
                isValid = false;
            }
            
            // Validate mobile if provided
            const mobile = form.querySelector('#mobile');
            if (mobile.value && !mobile.value.match(/^[0-9]{10}$/)) {
                mobile.nextElementSibling.nextElementSibling.style.display = 'block';
                isValid = false;
            }
            
            return isValid;
        }
        
        // Show/hide password requirements
        document.getElementById('password').addEventListener('focus', function() {
            this.nextElementSibling.style.display = 'block';
        });
        
        document.getElementById('password').addEventListener('blur', function() {
            this.nextElementSibling.style.display = 'none';
        });
    </script>
</body>
</html>