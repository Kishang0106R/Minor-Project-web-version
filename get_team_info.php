<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is in a team and get team info with total points
$stmt = $conn->prepare("
    SELECT t.team_name, t.school_name, tm.team_id, COALESCE(SUM(u.points), 0) as team_points
    FROM team_members tm
    JOIN teams t ON tm.team_id = t.id
    LEFT JOIN team_members tm2 ON t.id = tm2.team_id
    LEFT JOIN users u ON tm2.user_id = u.id
    WHERE tm.user_id = ?
    GROUP BY t.id, t.team_name, t.school_name, tm.team_id
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'team_name' => $row['team_name'],
        'school_name' => $row['school_name'],
        'team_points' => $row['team_points'],
        'team_id' => $row['team_id']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not in a team']);
}

$stmt->close();
$conn->close();
?>
