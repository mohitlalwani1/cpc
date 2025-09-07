<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: browse-polls.php');
    exit();
}

$poll_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Get poll details
$query = "SELECT p.*, u.username as creator_name, u.full_name as creator_full_name 
          FROM polls p 
          JOIN users u ON p.creator_id = u.id 
          WHERE p.id = ? AND p.is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute([$poll_id]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$poll) {
    header('Location: browse-polls.php');
    exit();
}

// Get poll options with vote counts
$query = "SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute([$poll_id]);
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total votes
$total_votes = array_sum(array_column($options, 'votes_count'));

// Check if user has already voted
$user_voted = false;
$user_vote_option = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $query = "SELECT option_id FROM votes WHERE poll_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$poll_id, $user['id']]);
    $vote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($vote) {
        $user_voted = true;
        $user_vote_option = $vote['option_id'];
    }
} else {
    // Check by IP address for non-logged users
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $query = "SELECT option_id FROM votes WHERE poll_id = ? AND ip_address = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$poll_id, $ip_address]);
    $vote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($vote) {
        $user_voted = true;
        $user_vote_option = $vote['option_id'];
    }
}

// Handle vote submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['option_id']) && !$user_voted) {
    $option_id = (int)$_POST['option_id'];
    
    // Verify option belongs to this poll
    $query = "SELECT id FROM poll_options WHERE id = ? AND poll_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$option_id, $poll_id]);
    
    if ($stmt->fetch()) {
        try {
            $db->beginTransaction();
            
            // Insert vote
            $user_id = isLoggedIn() ? getCurrentUser()['id'] : null;
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            $query = "INSERT INTO votes (poll_id, option_id, user_id, ip_address) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$poll_id, $option_id, $user_id, $ip_address]);
            
            // Update vote count
            $query = "UPDATE poll_options SET votes_count = votes_count + 1 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$option_id]);
            
            $db->commit();
            
            $message = 'Your vote has been recorded successfully!';
            $message_type = 'success';
            $user_voted = true;
            $user_vote_option = $option_id;
            
            // Refresh options data
            $query = "SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id";
            $stmt = $db->prepare($query);
            $stmt->execute([$poll_id]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_votes = array_sum(array_column($options, 'votes_count'));
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error recording your vote. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($poll['title']); ?> - CampusPoll</title>
    <link rel="stylesheet" href="assets/poll-details.css">
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
            <a href="browse-polls.php">Browse Polls</a>
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
        <div class="poll-container">
            <div class="poll-header">
                <div class="poll-meta">
                    <h1><?php echo htmlspecialchars($poll['title']); ?></h1>
                    <div class="poll-info">
                        <span class="creator">By <?php echo htmlspecialchars($poll['creator_full_name']); ?></span>
                        <span class="date"><?php echo date('M j, Y', strtotime($poll['created_at'])); ?></span>
                        <span class="total-votes"><?php echo $total_votes; ?> total votes</span>
                    </div>
                </div>
                <div class="poll-status">
                    <span class="status-badge active">
                        <i class="fas fa-circle"></i> Active
                    </span>
                </div>
            </div>

            <?php if ($poll['description']): ?>
                <div class="poll-description">
                    <p><?php echo nl2br(htmlspecialchars($poll['description'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="poll-content">
                <?php if (!$user_voted): ?>
                    <!-- Voting Form -->
                    <div class="voting-section">
                        <h3>Cast Your Vote</h3>
                        <form method="POST" class="vote-form">
                            <div class="options-list">
                                <?php foreach ($options as $option): ?>
                                    <label class="option-item">
                                        <input type="radio" name="option_id" value="<?php echo $option['id']; ?>" required>
                                        <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        <span class="option-radio"></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="vote-button">
                                <i class="fas fa-vote-yea"></i>
                                Submit Vote
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Results View -->
                    <div class="results-section">
                        <h3>Poll Results</h3>
                        <div class="results-list">
                            <?php foreach ($options as $option): 
                                $percentage = $total_votes > 0 ? round(($option['votes_count'] / $total_votes) * 100, 1) : 0;
                                $is_user_choice = ($option['id'] == $user_vote_option);
                            ?>
                                <div class="result-item <?php echo $is_user_choice ? 'user-choice' : ''; ?>">
                                    <div class="result-header">
                                        <span class="result-text">
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                            <?php if ($is_user_choice): ?>
                                                <i class="fas fa-check-circle user-vote-icon"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="result-stats">
                                            <span class="percentage"><?php echo $percentage; ?>%</span>
                                            <span class="vote-count">(<?php echo $option['votes_count']; ?> votes)</span>
                                        </span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="vote-confirmation">
                            <i class="fas fa-check-circle"></i>
                            <span>Thank you for voting! Your response has been recorded.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="poll-actions">
                <a href="browse-polls.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Polls
                </a>
                
                <div class="share-buttons">
                    <button class="share-button" onclick="shareUrl()">
                        <i class="fas fa-share"></i>
                        Share Poll
                    </button>
                    
                    <?php if (isLoggedIn() && getCurrentUser()['id'] == $poll['creator_id']): ?>
                        <a href="manage-poll.php?id=<?php echo $poll['id']; ?>" class="manage-button">
                            <i class="fas fa-cog"></i>
                            Manage
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="poll-sidebar">
            <div class="poll-stats">
                <h4>Poll Statistics</h4>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <span class="stat-number"><?php echo $total_votes; ?></span>
                        <span class="stat-label">Total Votes</span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <span class="stat-number"><?php echo date('M j', strtotime($poll['created_at'])); ?></span>
                        <span class="stat-label">Created</span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-user"></i>
                    <div>
                        <span class="stat-number"><?php echo htmlspecialchars($poll['creator_name']); ?></span>
                        <span class="stat-label">Creator</span>
                    </div>
                </div>
            </div>

            <?php if (!isLoggedIn()): ?>
                <div class="login-prompt">
                    <h4>Join CampusPoll</h4>
                    <p>Create an account to create your own polls and track your voting history.</p>
                    <a href="auth/register.php" class="register-button">Sign Up Free</a>
                    <a href="auth/login.php" class="login-link">Already have an account?</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function shareUrl() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($poll['title']); ?>',
                    text: 'Check out this poll on CampusPoll',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Poll URL copied to clipboard!');
                });
            }
        }

        // Auto-refresh results every 30 seconds if user has voted
        <?php if ($user_voted): ?>
        setInterval(function() {
            // This would typically make an AJAX call to refresh results
            // For now, we'll just add a subtle animation
            document.querySelectorAll('.progress-fill').forEach(bar => {
                bar.style.opacity = '0.7';
                setTimeout(() => bar.style.opacity = '1', 200);
            });
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>