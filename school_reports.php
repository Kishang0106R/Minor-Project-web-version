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

// Get comprehensive school statistics
$stats = [];

// Basic counts
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM teachers WHERE school_name = ?");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_teachers'] = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM teams WHERE school_name = ?");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_teams'] = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT tm.user_id) as count
    FROM team_members tm
    JOIN teams t ON tm.team_id = t.id
    WHERE t.school_name = ?
");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_students'] = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(u.points), 0) as total
    FROM team_members tm
    JOIN teams t ON tm.team_id = t.id
    JOIN users u ON tm.user_id = u.id
    WHERE t.school_name = ?
");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_points'] = $result->fetch_assoc()['total'];
$stmt->close();

// Teacher performance report
$stmt = $conn->prepare("
    SELECT
        tl.id,
        tl.name as teacher_name,
        tl.email,
        tl.subject,
        tl.designation,
        COUNT(DISTINCT t.id) as teams_count,
        COUNT(DISTINCT tm.user_id) as students_count,
        COALESCE(SUM(u.points), 0) as total_points
    FROM teachers tl
    LEFT JOIN teams t ON tl.id = t.teacher_id AND t.school_name = ?
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE tl.school_name = ?
    GROUP BY tl.id, tl.name, tl.email, tl.subject, tl.designation
    ORDER BY total_points DESC, teams_count DESC
");
$stmt->bind_param("ss", $school_name, $school_name);
$stmt->execute();
$teacher_report = $stmt->get_result();

$teachers_data = [];
while ($row = $teacher_report->fetch_assoc()) {
    $teachers_data[] = $row;
}
$stmt->close();

// Team performance report
$stmt = $conn->prepare("
    SELECT
        t.id,
        t.team_name,
        tl.name as teacher_name,
        COUNT(tm.user_id) as member_count,
        COALESCE(SUM(u.points), 0) as total_points,
        COALESCE(AVG(u.points), 0) as avg_points,
        MAX(u.points) as max_points,
        MIN(u.points) as min_points,
        t.created_date
    FROM teams t
    LEFT JOIN teachers tl ON t.teacher_id = tl.id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE t.school_name = ?
    GROUP BY t.id, t.team_name, tl.name, t.created_date
    ORDER BY total_points DESC
");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$team_report = $stmt->get_result();

$teams_data = [];
while ($row = $team_report->fetch_assoc()) {
    $teams_data[] = $row;
}
$stmt->close();

// Points distribution (for charts)
$stmt = $conn->prepare("
    SELECT
        CASE
            WHEN u.points = 0 THEN '0 Points'
            WHEN u.points BETWEEN 1 AND 10 THEN '1-10 Points'
            WHEN u.points BETWEEN 11 AND 25 THEN '11-25 Points'
            WHEN u.points BETWEEN 26 AND 50 THEN '26-50 Points'
            WHEN u.points BETWEEN 51 AND 100 THEN '51-100 Points'
            ELSE '100+ Points'
        END as points_range,
        COUNT(*) as count
    FROM users u
    JOIN team_members tm ON u.id = tm.user_id
    JOIN teams t ON tm.team_id = t.id
    WHERE t.school_name = ?
    GROUP BY
        CASE
            WHEN u.points = 0 THEN '0 Points'
            WHEN u.points BETWEEN 1 AND 10 THEN '1-10 Points'
            WHEN u.points BETWEEN 11 AND 25 THEN '11-25 Points'
            WHEN u.points BETWEEN 26 AND 50 THEN '26-50 Points'
            WHEN u.points BETWEEN 51 AND 100 THEN '51-100 Points'
            ELSE '100+ Points'
        END
    ORDER BY MIN(u.points)
");
$stmt->bind_param("s", $school_name);
$stmt->execute();
$points_distribution = $stmt->get_result();

$distribution_data = [];
while ($row = $points_distribution->fetch_assoc()) {
    $distribution_data[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Reports - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .report-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }
        .report-section h2 {
            color: #c60000;
            margin-bottom: 20px;
            border-bottom: 2px solid #c60000;
            padding-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #c60000, #900000);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 2em;
            font-weight: bold;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .report-table th,
        .report-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .report-table th {
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
        }
        .report-table tr:hover {
            background: #f9f9f9;
        }
        .rank-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .rank-1 { background: #ffd700; color: #333; }
        .rank-2 { background: #c0c0c0; color: #333; }
        .rank-3 { background: #cd7f32; color: #333; }
        .rank-other { background: #c60000; }
        .export-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        .export-btn:hover {
            background: #218838;
        }
        .chart-container {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .distribution-bar {
            display: flex;
            align-items: center;
            margin: 8px 0;
        }
        .distribution-label {
            width: 120px;
            font-size: 14px;
        }
        .distribution-bar-fill {
            flex: 1;
            height: 20px;
            background: #c60000;
            border-radius: 10px;
            margin: 0 10px;
            position: relative;
        }
        .distribution-count {
            font-weight: bold;
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
        <p>School Reports & Analytics - <?php echo htmlspecialchars($principal_name); ?> (Principal)</p>
        <div class="nav-links">
            <a href="PrincipalAdmin.php" class="btn">Home</a>
        </div>
    </div>

    <div class="container">
        <!-- Overview Statistics -->
        <div class="report-section">
            <h2>School Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $stats['total_teachers']; ?></h3>
                    <p>Total Teachers</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['total_teams']; ?></h3>
                    <p>Total Teams</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['total_students']; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['total_points']; ?></h3>
                    <p>Total Points Earned</p>
                </div>
            </div>
        </div>

        <!-- Points Distribution Chart -->
        <div class="report-section">
            <h2>Student Points Distribution</h2>
            <div class="chart-container">
                <?php foreach ($distribution_data as $dist): ?>
                    <div class="distribution-bar">
                        <div class="distribution-label"><?php echo htmlspecialchars($dist['points_range']); ?></div>
                        <div class="distribution-bar-fill" style="width: <?php echo min(100, ($dist['count'] / max($stats['total_students'], 1)) * 100); ?>%"></div>
                        <div class="distribution-count"><?php echo $dist['count']; ?> students</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Teacher Performance Report -->
        <div class="report-section">
            <h2>Teacher Performance Report</h2>
            <button class="export-btn" onclick="exportTable('teacher-table')">Export to CSV</button>
            <table class="report-table" id="teacher-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Teacher Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Designation</th>
                        <th>Teams</th>
                        <th>Students</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers_data as $index => $teacher): ?>
                        <tr>
                            <td>
                                <?php if ($index < 3): ?>
                                    <span class="rank-badge rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></span>
                                <?php else: ?>
                                    <span class="rank-badge rank-other"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['subject'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['designation'] ?? 'N/A'); ?></td>
                            <td><?php echo $teacher['teams_count']; ?></td>
                            <td><?php echo $teacher['students_count']; ?></td>
                            <td><strong><?php echo $teacher['total_points']; ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Team Performance Report -->
        <div class="report-section">
            <h2>Team Performance Report</h2>
            <button class="export-btn" onclick="exportTable('team-table')">Export to CSV</button>
            <table class="report-table" id="team-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Team Name</th>
                        <th>Team Leader</th>
                        <th>Members</th>
                        <th>Total Points</th>
                        <th>Avg Points</th>
                        <th>Max Points</th>
                        <th>Min Points</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams_data as $index => $team): ?>
                        <tr>
                            <td>
                                <?php if ($index < 3): ?>
                                    <span class="rank-badge rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></span>
                                <?php else: ?>
                                    <span class="rank-badge rank-other"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                            <td><?php echo htmlspecialchars($team['teacher_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo $team['member_count']; ?></td>
                            <td><strong><?php echo $team['total_points']; ?></strong></td>
                            <td><?php echo number_format($team['avg_points'], 1); ?></td>
                            <td><?php echo $team['max_points'] ?? 0; ?></td>
                            <td><?php echo $team['min_points'] ?? 0; ?></td>
                            <td><?php echo date('d M Y', strtotime($team['created_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        function exportTable(tableId) {
            const table = document.getElementById(tableId);
            let csv = [];

            // Get headers
            const headers = [];
            for (let i = 0; i < table.rows[0].cells.length; i++) {
                headers.push(table.rows[0].cells[i].textContent.trim());
            }
            csv.push(headers.join(','));

            // Get data rows
            for (let i = 1; i < table.rows.length; i++) {
                const row = [];
                for (let j = 0; j < table.rows[i].cells.length; j++) {
                    row.push('"' + table.rows[i].cells[j].textContent.trim() + '"');
                }
                csv.push(row.join(','));
            }

            // Download CSV
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = tableId + '_report.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
