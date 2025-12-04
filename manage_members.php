<?php
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: TeacherLogin.html");
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
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// Get team ID from URL
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;

// Verify team belongs to this teacher
$stmt = $conn->prepare("SELECT team_name FROM teams WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $team_id, $_SESSION['teacher_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: TeacherAdmin.php");
    exit();
}

$team = $result->fetch_assoc();
$stmt->close();

// Handle adding member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
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

            // Check if already a member
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

// Handle removing member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_member'])) {
    $member_id = (int)$_POST['member_id'];

    $stmt = $conn->prepare("DELETE FROM team_members WHERE id = ? AND team_id = ?");
    $stmt->bind_param("ii", $member_id, $team_id);

    if ($stmt->execute()) {
        $message = "Member removed successfully!";
        $message_type = "success";
    } else {
        $message = "Error removing member: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch current members
$members = [];
$stmt = $conn->prepare("
    SELECT tm.id, u.name, u.email, tm.joined_date
    FROM team_members tm
    JOIN users u ON tm.user_id = u.id
    WHERE tm.team_id = ?
    ORDER BY tm.joined_date DESC
");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Team Members - <?php echo htmlspecialchars($team['team_name']); ?></title>
  <link rel="stylesheet" href="teacher_admin.css" />
</head>
<body>
  <div class="logout">
    <a href="logout.php" class="btn">Logout</a>
  </div>

  <div class="header">
    <h1><?php echo htmlspecialchars($_SESSION['school_name']); ?></h1>
    <p>Manage Members: <?php echo htmlspecialchars($team['team_name']); ?></p>
    <div class="nav-links">
      <a href="TeacherAdmin.php" class="btn">Home</a>
    </div>
  </div>

  <div class="container">
    <?php if (!empty($message)): ?>
      <div class="message <?php echo $message_type; ?>" style="display: block;"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Add Member Form -->
    <div class="form-section">
      <h2>Add New Member</h2>
      <form method="POST" action="">
        <input type="email" name="user_email" placeholder="Enter user email" required />
        <button type="submit" name="add_member" class="btn">Add Member</button>
      </form>
    </div>

    <!-- Current Members -->
    <div class="teams-section">
      <h2>Current Members (<?php echo count($members); ?>)</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Joined Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($members)): ?>
            <tr>
              <td colspan="4" style="text-align: center; color: #666;">No members yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($members as $member): ?>
              <tr>
                <td><?php echo htmlspecialchars($member['name']); ?></td>
                <td><?php echo htmlspecialchars($member['email']); ?></td>
                <td><?php echo date('d M Y', strtotime($member['joined_date'])); ?></td>
                <td>
                  <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Remove this member?')">
                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>" />
                    <button type="submit" name="remove_member" class="delete-btn">Remove</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
