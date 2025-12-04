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

    if (!empty($name) && !empty($email) && !empty($password)) {
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
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}

// Handle editing teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $designation = trim($_POST['designation']);
    $class_assigned = trim($_POST['class_assigned']);
    $mobile = trim($_POST['mobile']);

    if (!empty($name) && !empty($email)) {
        // Check if email already exists for another teacher
        $stmt_check = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $teacher_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "Another teacher with this email already exists.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE teachers SET name = ?, email = ?, subject = ?, designation = ?, class_assigned = ?, mobile = ? WHERE id = ? AND school_name = ?");
            $stmt->bind_param("ssssssis", $name, $email, $subject, $designation, $class_assigned, $mobile, $teacher_id, $school_name);

            if ($stmt->execute()) {
                $message = "Teacher updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating teacher: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}

// Handle deleting teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];

    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ? AND school_name = ?");
    $stmt->bind_param("is", $teacher_id, $school_name);

    if ($stmt->execute()) {
        $message = "Teacher deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting teacher: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

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
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-section input, .form-section select {
            width: 100%;
        }
        .form-section button {
            grid-column: span 2;
            justify-self: start;
        }
        .edit-form {
            display: none;
        }
        .edit-form.show {
            display: block;
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
            <div class="message <?php echo $message_type; ?>" style="display: block;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add Teacher Form -->
        <div class="form-section">
            <h2>Add New Teacher</h2>
            <form method="POST" action="">
                <input type="text" name="name" placeholder="Full Name" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <input type="text" name="subject" placeholder="Subject" />
                <input type="text" name="designation" placeholder="Designation" />
                <input type="text" name="class_assigned" placeholder="Class Assigned" />
                <input type="text" name="mobile" placeholder="Mobile Number" />
                <button type="submit" name="add_teacher" class="btn">Add Teacher</button>
            </form>
        </div>

        <!-- Teachers List -->
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
    </script>
</body>
</html>
