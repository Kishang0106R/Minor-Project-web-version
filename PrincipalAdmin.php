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

// Get school details
$stmt = $conn->prepare("SELECT * FROM principals WHERE id = ?");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get total teachers count
$teachers_count = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE school_name = '$school_name'")->fetch_assoc()['count'];

// Get teams and their members
$teams_query = "
    SELECT
        t.id as team_id,
        t.team_name,
        t.teacher_id as team_leader_id,
        tl.name as team_leader_name,
        COUNT(tm.user_id) as member_count,
        COALESCE(SUM(u.points), 0) as team_score
    FROM teams t
    LEFT JOIN teachers tl ON t.teacher_id = tl.id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE t.school_name = '$school_name'
    GROUP BY t.id, t.team_name, t.teacher_id, tl.name
    ORDER BY t.team_name
";
$teams_result = $conn->query($teams_query);
$teams = [];
while ($row = $teams_result->fetch_assoc()) {
    $teams[] = $row;
}

// Get teachers for the school
$teachers_query = "SELECT id, name, email, subject FROM teachers WHERE school_name = '$school_name' ORDER BY name";
$teachers_result = $conn->query($teachers_query);
$teachers = [];
while ($row = $teachers_result->fetch_assoc()) {
    $teachers[] = $row;
}

// Get total team points (sum of all user points in teams)
$total_team_points = $conn->query("SELECT COALESCE(SUM(u.points), 0) as total FROM team_members tm JOIN users u ON tm.user_id = u.id JOIN teams t ON tm.team_id = t.id WHERE t.school_name = '$school_name'")->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Admin Panel - <?php echo htmlspecialchars($school_name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
        }
        .header {
            background: #c60000;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin-bottom: 10px;
        }
        .header p {
            font-size: 18px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        .card h3 {
            color: #c60000;
            margin-bottom: 15px;
        }
        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .teachers-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .teachers-section h2 {
            color: #c60000;
            margin-bottom: 15px;
        }
        .teacher-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .teacher-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #c60000;
        }
        .teacher-card h4 {
            color: #c60000;
            margin-bottom: 8px;
        }
        .teacher-card p {
            margin: 4px 0;
            font-size: 14px;
            color: #666;
        }
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
        }
        .action-card:hover {
            transform: translateY(-5px);
        }
        .action-card h4 {
            color: #c60000;
            margin-bottom: 10px;
        }
        .action-card p {
            color: #666;
            margin-bottom: 15px;
        }
        .btn {
            background: #c60000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #a50000;
        }
        .logout {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .school-info {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .school-info h2 {
            color: #c60000;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
        }
        .info-item strong {
            color: #c60000;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p>Welcome, <?php echo htmlspecialchars($principal_name); ?> (Principal)</p>
    </div>

    <div class="container">
        <div class="school-info">
            <h2>School Information</h2>
            <div class="info-grid">
                <div class="info-item"><strong>School Code:</strong> <?php echo htmlspecialchars($school['school_code'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>District:</strong> <?php echo htmlspecialchars($school['district'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Zone:</strong> <?php echo htmlspecialchars($school['district'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Type:</strong> <?php echo htmlspecialchars($school['school_type'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Address:</strong> <?php echo htmlspecialchars($school['address'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Email:</strong> <?php echo htmlspecialchars($school['email'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Mobile:</strong> <?php echo htmlspecialchars($school['mobile'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Status:</strong> Verified</div>
            </div>
        </div>

        <div class="dashboard">
            <div class="card">
                <h3>Total Teachers</h3>
                <p><?php echo $teachers_count; ?></p>
            </div>
            <div class="card">
                <h3>Total Teams</h3>
                <p><?php echo count($teams); ?></p>
            </div>
            <div class="card">
                <h3>Total Team Points</h3>
                <p><?php echo $total_team_points; ?></p>
            </div>
            <div class="card">
                <h3>School Status</h3>
                <p>Verified</p>
            </div>
        </div>

        <div class="teachers-section">
            <h2>School Teachers</h2>
            <?php if (count($teachers) > 0): ?>
                <div class="teacher-list">
                    <?php foreach ($teachers as $teacher): ?>
                        <div class="teacher-card">
                            <h4><?php echo htmlspecialchars($teacher['name']); ?></h4>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($teacher['subject'] ?? 'Not specified'); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No teachers registered yet. <a href="manage_teachers.php">Manage teachers</a> to add teachers to your school.</p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <div class="action-card">
                <h4>Manage Teachers</h4>
                <p>Add, edit, or remove teachers from your school.</p>
                <a href="manage_teachers.php" class="btn">Manage Teachers</a>
            </div>
            <div class="action-card">
                <h4>Manage Teams</h4>
                <p>Create and manage student teams and their members.</p>
                <a href="manage_team_members.php" class="btn">Manage Teams</a>
            </div>
            <div class="action-card">
                <h4>View Reports</h4>
                <p>Generate and view school reports and analytics.</p>
                <a href="school_reports.php" class="btn">View Reports</a>
            </div>
            <div class="action-card">
                <h4>Update Profile</h4>
                <p>Update school information and principal details.</p>
                <a href="update_school_profile.php" class="btn">Update Profile</a>
            </div>
            <div class="action-card">
                <h4>Settings</h4>
                <p>Change password and account settings.</p>
                <a href="school_settings.php" class="btn">Settings</a>
            </div>
        </div>
    </div>
</body>
</html>
