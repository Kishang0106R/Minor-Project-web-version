<?php
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

// Get top 10 schools by average points (sum of team points / number of teams)
$schools_query = "
    SELECT
        school_name,
        (SUM(team_points) / COUNT(*)) as average_points,
        COUNT(*) as team_count
    FROM (
        SELECT
            t.school_name,
            t.id,
            COALESCE(SUM(u.points), 0) as team_points
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        LEFT JOIN users u ON tm.user_id = u.id
        GROUP BY t.id, t.school_name
    ) sub
    GROUP BY school_name
    ORDER BY average_points DESC
    LIMIT 10
";
$schools_result = $conn->query($schools_query);
$top_schools = [];
while ($row = $schools_result->fetch_assoc()) {
    $top_schools[] = $row;
}

// Get top 10 teams by points (sum of user points in team)
$teams_query = "
    SELECT
        t.team_name,
        t.school_name,
        COALESCE(SUM(u.points), 0) as points,
        t.teacher_id
    FROM teams t
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.id
    GROUP BY t.id, t.team_name, t.school_name, t.teacher_id
    ORDER BY points DESC
    LIMIT 10
";
$teams_result = $conn->query($teams_query);
$top_teams = [];
while ($row = $teams_result->fetch_assoc()) {
    // Get teacher name
    $teacher_stmt = $conn->prepare("SELECT name FROM teachers WHERE id = ?");
    $teacher_stmt->bind_param("i", $row['teacher_id']);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher_name = $teacher_result->fetch_assoc()['name'] ?? 'Unknown';
    $teacher_stmt->close();

    $row['teacher_name'] = $teacher_name;
    $top_teams[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System - Leaderboard</title>
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
            line-height: 1.6;
        }
        .header {
            background: #c60000;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .leaderboard-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .leaderboard-section h2 {
            color: #c60000;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #c60000;
            padding-bottom: 10px;
        }
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .leaderboard-table th,
        .leaderboard-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .leaderboard-table th {
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }
        .leaderboard-table tr:hover {
            background: #f9f9f9;
        }
        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: white;
            margin-right: 10px;
        }
        .rank-1 { background: #ffd700; color: #333; }
        .rank-2 { background: #c0c0c0; color: #333; }
        .rank-3 { background: #cd7f32; color: #333; }
        .rank-other { background: #c60000; }
        .points-highlight {
            font-weight: bold;
            color: #c60000;
            font-size: 18px;
        }
        .nav-links {
            text-align: center;
            margin-top: 30px;
        }
        .nav-links a {
            background: #c60000;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            display: inline-block;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: #a50000;
        }
        .trophy-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .school-rank, .team-rank {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏆 Leaderboard</h1>
        <p>Top Performing Schools and Teams</p>
    </div>

    <div class="container">
        <!-- Top Schools Section -->
        <div class="leaderboard-section">
            <h2>🏫 Top 10 Schools</h2>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>School Name</th>
                        <th>Average Points</th>
                        <th>Teams Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_schools as $index => $school): ?>
                        <tr>
                            <td>
                                <div class="school-rank">
                                    <?php if ($index < 3): ?>
                                        <span class="rank-badge rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></span>
                                    <?php else: ?>
                                        <span class="rank-badge rank-other"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                            <td><span class="points-highlight"><?php echo number_format($school['average_points'], 2); ?></span></td>
                            <td><?php echo $school['team_count']; ?> teams</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Teams Section -->
        <div class="leaderboard-section">
            <h2>👥 Top 10 Teams</h2>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Team Name</th>
                        <th>School</th>
                        <th>Team Leader</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_teams as $index => $team): ?>
                        <tr>
                            <td>
                                <div class="team-rank">
                                    <?php if ($index < 3): ?>
                                        <span class="rank-badge rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></span>
                                    <?php else: ?>
                                        <span class="rank-badge rank-other"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                            <td><?php echo htmlspecialchars($team['school_name']); ?></td>
                            <td><?php echo htmlspecialchars($team['teacher_name']); ?></td>
                            <td><span class="points-highlight"><?php echo number_format($team['points']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="nav-links">
            <a href="UserLogin.html">Student Login</a>
            <a href="TeacherLogin.html">Teacher Login</a>
            <a href="PrincipalLogin.html">Principal Login</a>
        </div>
    </div>
</body>
</html>
