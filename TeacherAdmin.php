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

// Handle team deletion
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_team_id'])) {
    $delete_team_id = (int)$_POST['delete_team_id'];

    // Verify the team belongs to this teacher
    $stmt_verify = $conn->prepare("SELECT id FROM teams WHERE id = ? AND teacher_id = ?");
    $stmt_verify->bind_param("ii", $delete_team_id, $_SESSION['teacher_id']);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();

    if ($result_verify->num_rows > 0) {
        // Delete team (cascade will handle team_members)
        $stmt_delete = $conn->prepare("DELETE FROM teams WHERE id = ?");
        $stmt_delete->bind_param("i", $delete_team_id);

        if ($stmt_delete->execute()) {
            $message = "Team deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting team: " . $conn->error;
            $message_type = "error";
        }
        $stmt_delete->close();
    } else {
        $message = "Team not found or access denied.";
        $message_type = "error";
    }
    $stmt_verify->close();
}

// Handle team creation (only if no message already set from deletion)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['team_name']) && empty($message)) {
    $team_name = trim($_POST['team_name']);
    $teacher_id = $_SESSION['teacher_id'];
    $school_name = $_SESSION['school_name'];

    if (!empty($team_name)) {
        // Check if team name already exists for this teacher
        $stmt_check = $conn->prepare("SELECT id FROM teams WHERE team_name = ? AND teacher_id = ?");
        $stmt_check->bind_param("si", $team_name, $teacher_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "A team with this name already exists.";
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
        $message = "Team name cannot be empty.";
        $message_type = "error";
    }
}

// Fetch teams for this teacher with total points calculated as (Price × 300) + (Rating × 5,000) per product
$teams = [];
$stmt = $conn->prepare("
    SELECT t.id, t.team_name, t.created_date, COALESCE(SUM( (p.product_price * 300) + (COALESCE(avg_ratings.avg_rating, 0) * 5000) ), 0) as total_points
    FROM teams t
    LEFT JOIN products p ON t.id = p.team_id
    LEFT JOIN (
        SELECT product_id, AVG(rating) as avg_rating
        FROM orders
        WHERE rating IS NOT NULL
        GROUP BY product_id
    ) avg_ratings ON p.product_id = avg_ratings.product_id
    WHERE t.teacher_id = ?
    GROUP BY t.id, t.team_name, t.created_date
    ORDER BY t.created_date DESC
");
$stmt->bind_param("i", $_SESSION['teacher_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($_SESSION['teacher_name']); ?> - Teacher Admin Panel</title>
  <link rel="stylesheet" href="teacher_admin.css" />
</head>
<body>
  <div class="nav-links">
    <a href="#">Approve Products</a>
  </div>

  <div class="header">
    <h1><?php echo htmlspecialchars($_SESSION['school_name']); ?></h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['teacher_name']); ?> (Teacher)</p>
    <div class="logout">
      <a href="logout.php" class="btn">Logout</a>
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
        <button type="submit" class="btn">Create Team</button>
      </form>
    </div>

    <!-- Teams List -->
    <div class="teams-section">
      <h2>My Teams</h2>
      <table>
        <thead>
          <tr>
            <th>Team Name</th>
            <th>Created Date</th>
            <th>Points</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($teams)): ?>
            <tr>
              <td colspan="4" style="text-align: center; color: #666;">No teams created yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($teams as $team): ?>
              <tr>
                <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                <td><?php echo date('d M Y', strtotime($team['created_date'])); ?></td>
                <td><?php echo htmlspecialchars($team['total_points']); ?></td>
                <td>
                  <a href="manage_members.php?team_id=<?php echo $team['id']; ?>" class="btn green">👥 Manage Members</a>
                  <button class="delete-btn" onclick="deleteTeam(<?php echo $team['id']; ?>)">Delete</button>
                  <a href="manage_products.php?team_id=<?php echo $team['id']; ?>" class="btn">Manage Products</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function deleteTeam(teamId) {
      if (confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
        // Create a form and submit it to handle the deletion
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_team_id';
        input.value = teamId;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>
