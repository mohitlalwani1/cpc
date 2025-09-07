<?php
require_once 'config/database.php';
require_once 'includes/session.php';

$database = new Database();
$db = $database->getConnection();

// Get all active polls with vote counts
$query = "SELECT p.*, u.username as creator_name, COUNT(v.id) as total_votes 
          FROM polls p 
          JOIN users u ON p.creator_id = u.id 
          LEFT JOIN votes v ON p.id = v.poll_id 
          WHERE p.is_active =   
          GROUP BY p.id 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Polls - CampusPoll</title>
    <link rel="stylesheet" href="assets/browse-polls.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <nav class="top-nav">
        <div class="nav-brand">
            <img src="assets/poll.png" alt="CampusPoll">
            <h2>CampusPoll</h2>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="create-poll.php">Create Poll</a>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <header class="page-header">
            <h1>Browse Active Polls</h1>
            <p>Discover and participate in campus polls</p>
        </header>

        <div class="polls-grid">
            <?php if (empty($polls)): ?>
                <div class="no-polls">
                    <i class="fas fa-poll"></i>
                    <h3>No active polls found</h3>
                    <p>Be the first to create a poll!</p>
                    <a href="create-poll.php" class="create-btn">Create Poll</a>
                </div>
            <?php else: ?>
                <?php foreach ($polls as $poll): ?>
                    <div class="poll-card">
                        <div class="poll-header">
                            <h3><?php echo htmlspecialchars($poll['title']); ?></h3>
                            <span class="poll-votes"><?php echo $poll['total_votes']; ?> votes</span>
                        </div>
                        
                        <?php if ($poll['description']): ?>
                            <p class="poll-description"><?php echo htmlspecialchars($poll['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="poll-meta">
                            <span class="creator">By <?php echo htmlspecialchars($poll['creator_name']); ?></span>
                            <span class="date"><?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                        </div>
                        
                        <div class="poll-actions">
                            <a href="poll-details.php?id=<?php echo $poll['id']; ?>" class="vote-btn">
                                <i class="fas fa-vote-yea"></i> Vote Now
                            </a>
                            <a href="poll-results.php?id=<?php echo $poll['id']; ?>" class="results-btn">
                                <i class="fas fa-chart-bar"></i> View Results
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>