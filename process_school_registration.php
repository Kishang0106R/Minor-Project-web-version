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
    // Get principal details
    $school_name = $_POST['school_name'];
    $school_code = $_POST['school_code'];
    $district = $_POST['district'];
    $school_type = $_POST['school_type'];
    $address = $_POST['address'];
    $principal_name = $_POST['principal_name'];
    $principal_mobile = $_POST['principal_mobile'];
    $principal_email = $_POST['principal_email'];
    $password = $_POST['password'];

    // Check if principal email already exists
    $check_stmt = $conn->prepare("SELECT id FROM principals WHERE email = ?");
    $check_stmt->bind_param("s", $principal_email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Principal email already exists.']);
        exit();
    }
    $check_stmt->close();

    // Store principal password in plain text
    $hashed_principal_password = $password;

    // Insert principal data
    $stmt = $conn->prepare("INSERT INTO principals (name, email, password, school_name, school_code, district, school_type, address, mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $principal_name, $principal_email, $hashed_principal_password, $school_name, $school_code, $district, $school_type, $address, $principal_mobile);

    if ($stmt->execute()) {
        $principal_id = $stmt->insert_id;

        // Insert teachers data
        if (isset($_POST['teacher_name'])) {
            $teacher_names = $_POST['teacher_name'];
            $designations = $_POST['designation'];
            $subjects = $_POST['subject'];
            $class_assigneds = $_POST['class_assigned'];
            $teacher_emails = $_POST['teacher_email'];
            $teacher_mobiles = $_POST['teacher_mobile'];

            for ($i = 0; $i < count($teacher_names); $i++) {
                $teacher_name = $teacher_names[$i];
                $designation = $designations[$i];
                $subject = $subjects[$i] ?? '';
                $class_assigned = $class_assigneds[$i];
                $teacher_email = $teacher_emails[$i] ?? '';
                $teacher_mobile = $teacher_mobiles[$i];

                // Check if teacher email already exists (if provided)
                if (!empty($teacher_email)) {
                    $check_teacher_stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
                    $check_teacher_stmt->bind_param("s", $teacher_email);
                    $check_teacher_stmt->execute();
                    $check_teacher_stmt->store_result();
                    if ($check_teacher_stmt->num_rows > 0) {
                        echo json_encode(['status' => 'error', 'message' => 'Teacher email ' . $teacher_email . ' already exists.']);
                        exit();
                    }
                    $check_teacher_stmt->close();
                }

                // Insert teacher data
                $teacher_stmt = $conn->prepare("INSERT INTO teachers (name, email, password, subject, school_name, designation, class_assigned, mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $teacher_password = $hashed_principal_password; // Same hashed password as principal
                $teacher_stmt->bind_param("ssssssss", $teacher_name, $teacher_email, $teacher_password, $subject, $school_name, $designation, $class_assigned, $teacher_mobile);
                if (!$teacher_stmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => 'Error inserting teacher: ' . $teacher_stmt->error]);
                    exit();
                }
                $teacher_stmt->close();
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'School registered successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error registering school: ' . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
