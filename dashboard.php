<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();
$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// Get user's polls
$query = "SELECT p.*, COUNT(v.id) as total_votes FROM polls p 
          LEFT JOIN votes v ON p.id = v.poll_id 
          WHERE p.creator_id = ? 
          GROUP BY p.id 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user['id']]);
$user_polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity
$query = "SELECT p.title, v.created_at FROM votes v 
          JOIN polls p ON v.poll_id = p.id 
          WHERE p.creator_id = ? 
          ORDER BY v.created_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$user['id']]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CampusPoll</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="assets/poll.png" alt="CampusPoll">
                <h2>CampusPoll</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="create-poll.php"><i class="fas fa-plus"></i> Create Poll</a></li>
                <li><a href="my-polls.php"><i class="fas fa-poll"></i> My Polls</a></li>
                <li><a href="browse-polls.php"><i class="fas fa-search"></i> Browse Polls</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                    <a href="auth/logout.php" class="logout-btn">Logout</a>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stats-card">
                    <div class="stat-item">
                        <i class="fas fa-poll"></i>
                        <div>
                            <h3><?php echo count($user_polls); ?></h3>
                            <p>Total Polls</p>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stat-item">
                        <i class="fas fa-vote-yea"></i>
                        <div>
                            <h3><?php echo array_sum(array_column($user_polls, 'total_votes')); ?></h3>
                            <p>Total Votes</p>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stat-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <h3><?php echo count(array_filter($user_polls, function($p) { return $p['is_active']; })); ?></h3>
                            <p>Active Polls</p>
                        </div>
                    </div>
                </div>

                <div class="recent-polls">
                    <h3>Your Recent Polls</h3>
                    <?php if (empty($user_polls)): ?>
                        <p class="no-data">No polls created yet. <a href="create-poll.php">Create your first poll!</a></p>
                    <?php else: ?>
                        <div class="polls-list">
                            <?php foreach (array_slice($user_polls, 0, 5) as $poll): ?>
                                <div class="poll-item">
                                    <h4><?php echo htmlspecialchars($poll['title']); ?></h4>
                                    <p><?php echo $poll['total_votes']; ?> votes</p>
                                    <span class="poll-status <?php echo $poll['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $poll['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="create-poll.php" class="action-btn primary">
                            <i class="fas fa-plus"></i>
                            Create New Poll
                        </a>
                        <a href="browse-polls.php" class="action-btn secondary">
                            <i class="fas fa-search"></i>
                            Browse Polls
                        </a>
                        <a href="my-polls.php" class="action-btn secondary">
                            <i class="fas fa-poll"></i>
                            Manage Polls
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>