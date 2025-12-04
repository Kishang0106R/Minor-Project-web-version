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

// Handle team creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_team'])) {
    $team_name = trim($_POST['team_name']);
    $teacher_id = (int)$_POST['teacher_id'];

    if (!empty($team_name) && $teacher_id > 0) {
        // Check if team name already exists for this school
        $stmt_check = $conn->prepare("SELECT id FROM teams WHERE team_name = ? AND school_name = ?");
        $stmt_check->bind_param("ss", $team_name, $school_name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "A team with this name already exists in your school.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO teams (team_name, teacher_id, school_name) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $team_name, $teacher_id, $school_name);

            if ($stmt->execute()) {
                $message = "Team created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating team: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $message = "Please provide a team name and select a teacher.";
        $message_type = "error";
    }
}

// Handle team deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_team'])) {
    $team_id = (int)$_POST['team_id'];

    $stmt = $conn->prepare("DELETE FROM teams WHERE id = ? AND school_name = ?");
    $stmt->bind_param("is", $team_id, $school_name);

    if ($stmt->execute()) {
        $message = "Team deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting team: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Handle adding member to team
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $team_id = (int)$_POST['team_id'];
    $user_email = trim($_POST['user_email']);

    if (!empty($user_email)) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Check if already a member of this team
            $stmt_check = $conn->prepare("SELECT id FROM team_members WHERE team_id = ? AND user_id = ?");
            $stmt_check->bind_param("ii", $team_id, $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows == 0) {
                // Add member
                $stmt_add = $conn->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
                $stmt_add->bind_param("ii", $team_id, $user_id);

                if ($stmt_add->execute()) {
                    $message = "Member added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding member: " . $conn->error;
                    $message_type = "error";
                }
                $stmt_add->close();
            } else {
                $message = "User is already a member of this team.";
                $message_type = "error";
            }
            $stmt_check->close();
        } else {
            $message = "User not found with that email.";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Please enter a valid email.";
        $message_type = "error";
    }
}

// Handle removing member from team
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_member'])) {
    $member_id = (int)$_POST['member_id'];

    $stmt = $conn->prepare("DELETE FROM team_members WHERE id = ?");
    $stmt->bind_param("i", $member_id);

    if ($stmt->execute()) {
        $message = "Member removed successfully!";
        $message_type = "success";
    } else {
        $message = "Error removing member: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch teachers for dropdown
$teachers = [];
$stmt = $conn->prepare("SELECT id, name FROM teachers WHERE school_name = ? ORDER BY name");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}
$stmt->close();

// Fetch teams with their members and points
$teams = [];
$stmt = $conn->prepare("
    SELECT t.id, t.team_name, t.teacher_id, t.created_date, tl.name as teacher_name,
           COUNT(tm.user_id) as member_count, COALESCE(SUM(u.points), 0) as total_points
    FROM teams t
    LEFT JOIN teachers tl ON t.teacher_id = tl.id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE t.school_name = ?
    GROUP BY t.id, t.team_name, t.teacher_id, t.created_date, tl.name
    ORDER BY t.created_date DESC
");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}
$stmt->close();

// Get members for each team
foreach ($teams as &$team) {
    $members = [];
    $stmt = $conn->prepare("
        SELECT tm.id, u.name, u.email, tm.joined_date
        FROM team_members tm
        JOIN users u ON tm.user_id = u.id
        WHERE tm.team_id = ?
        ORDER BY tm.joined_date DESC
    ");
    $stmt->bind_param("i", $team['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($member = $result->fetch_assoc()) {
        $members[] = $member;
    }
    $team['members'] = $members;
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .form-section form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .form-section input, .form-section select {
            width: 100%;
        }
        .team-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .team-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .team-header h3 {
            color: #c60000;
            margin: 0;
        }
        .team-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        .team-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .members-section {
            margin-top: 15px;
        }
        .members-section h4 {
            color: #c60000;
            margin-bottom: 10px;
        }
        .member-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .member-item:last-child {
            border-bottom: none;
        }
        .add-member-form {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .add-member-form form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .add-member-form input {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p>Manage Teams - <?php echo htmlspecialchars($principal_name); ?> (Principal)</p>
        <div class="nav-links">
            <a href="PrincipalAdmin.php" class="btn">Home</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>" style="display: block;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Create Team Form -->
        <div class="form-section">
            <h2>Create New Team</h2>
            <form method="POST" action="">
                <input type="text" name="team_name" placeholder="Team Name" required />
                <select name="teacher_id" required>
                    <option value="">Select Team Leader</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="create_team" class="btn">Create Team</button>
            </form>
        </div>

        <!-- Teams List -->
        <div class="teams-section">
            <h2>School Teams (<?php echo count($teams); ?>)</h2>
            <?php if (empty($teams)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No teams created yet. Create your first team above.</p>
            <?php else: ?>
                <?php foreach ($teams as $team): ?>
                    <div class="team-card">
                        <div class="team-header">
                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this team and all its members?')">
                                <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>" />
                                <button type="submit" name="delete_team" class="delete-btn">Delete Team</button>
                            </form>
                        </div>

                        <div class="team-info">
                            <p><strong>Team Leader:</strong> <?php echo htmlspecialchars($team['teacher_name'] ?? 'Not assigned'); ?></p>
                            <p><strong>Created:</strong> <?php echo date('d M Y', strtotime($team['created_date'])); ?></p>
                            <p><strong>Members:</strong> <?php echo $team['member_count']; ?></p>
                            <p><strong>Total Points:</strong> <?php echo $team['total_points']; ?></p>
                        </div>

                        <div class="members-section">
                            <h4>Members</h4>
                            <?php if (empty($team['members'])): ?>
                                <p style="color: #666; font-style: italic;">No members yet.</p>
                            <?php else: ?>
                                <?php foreach ($team['members'] as $member): ?>
                                    <div class="member-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                                            <br><small><?php echo htmlspecialchars($member['email']); ?> • Joined <?php echo date('d M Y', strtotime($member['joined_date'])); ?></small>
                                        </div>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Remove this member?')">
                                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>" />
                                            <button type="submit" name="remove_member" class="delete-btn">Remove</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="add-member-form">
                            <form method="POST" action="">
                                <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>" />
                                <input type="email" name="user_email" placeholder="Enter student email to add" required />
                                <button type="submit" name="add_member" class="btn green">Add Member</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
