<?php
require_once 'config/database.php';
require_once 'includes/session.php';

$database = new Database();
$db = $database->getConnection();

// Get trending polls for homepage
$query = "SELECT p.*, u.username as creator_name, COUNT(v.id) as total_votes 
          FROM polls p 
          JOIN users u ON p.creator_id = u.id 
          LEFT JOIN votes v ON p.id = v.poll_id 
          WHERE p.is_active = 1 
          GROUP BY p.id 
          ORDER BY total_votes DESC 
          LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$trending_polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get poll options for trending polls
$trending_with_options = [];
foreach ($trending_polls as $poll) {
    $options_query = "SELECT * FROM poll_options WHERE poll_id = ? ORDER BY votes_count DESC";
    $options_stmt = $db->prepare($options_query);
    $options_stmt->execute([$poll['id']]);
    $poll['options'] = $options_stmt->fetchAll(PDO::FETCH_ASSOC);
    $trending_with_options[] = $poll;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusPoll - Voice of the Campus</title>
    <link rel="stylesheet" href="assets/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div id="main">
        <header>
            <nav class="nav-bar">
                <div class="nav-brand">
                    <img src="assets/poll.png" alt="CampusPoll Logo">
                    <h2>CampusPoll</h2>
                </div>
                
                <div class="nav-section">
                    <a href="#home" class="nav-link active">Home</a>
                    <a href="#trending" class="nav-link">Trending</a>
                    <a href="#create" class="nav-link">Create Poll</a>
                    <a href="#about" class="nav-link">About</a>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="nav-btn dashboard-btn">Dashboard</a>
                        <a href="auth/logout.php" class="nav-btn logout-btn">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="nav-btn login-btn">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section id="home" class="hero-section">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Voice of the <span class="highlight">Campus</span></h1>
                    <p class="hero-subtitle">Create, share, and discover polls about campus life, academics, and student opinions.</p>
                    
                    <div class="hero-actions">
                        <?php if (isLoggedIn()): ?>
                            <a href="create-poll.php" class="cta-button primary">Create a Poll</a>
                            <a href="browse-polls.php" class="cta-button secondary">Browse Polls</a>
                        <?php else: ?>
                            <a href="auth/register.php" class="cta-button primary">Get Started</a>
                            <a href="browse-polls.php" class="cta-button secondary">Browse Polls</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="hero-image">
                    <img src="assets/poll1.png" alt="Campus Polling Illustration">
                </div>
            </div>
        </section>

        <!-- Trending Polls Section -->
        <section id="trending" class="trending-section">
            <div class="section-header">
                <h2>Trending Polls</h2>
                <p>What's buzzing on campus right now</p>
            </div>
            
            <div class="polls-grid">
                <?php foreach ($trending_with_options as $poll): ?>
                    <div class="poll-card">
                        <div class="poll-header">
                            <h3><?php echo htmlspecialchars($poll['title']); ?></h3>
                            <span class="vote-count"><?php echo $poll['total_votes']; ?> votes</span>
                        </div>
                        
                        <p class="poll-description"><?php echo htmlspecialchars($poll['description']); ?></p>
                        
                        <div class="poll-options">
                            <?php 
                            $total_votes = max(1, $poll['total_votes']); // Prevent division by zero
                            foreach ($poll['options'] as $option): 
                                $percentage = round(($option['votes_count'] / $total_votes) * 100);
                            ?>
                                <div class="option-item">
                                    <div class="option-header">
                                        <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        <span class="option-percentage"><?php echo $percentage; ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="poll-actions">
                            <a href="poll-details.php?id=<?php echo $poll['id']; ?>" class="vote-btn">Vote Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section-footer">
                <a href="browse-polls.php" class="view-all-btn">View All Polls <i class="fas fa-arrow-right"></i></a>
            </div>
        </section>

        <!-- Create Poll Section -->
        <section id="create" class="create-section">
            <div class="create-content">
                <div class="create-text">
                    <h2>Create Powerful Polls.</h2>
                    <h3 class="create-subtitle">Gather Real Insights.</h3>
                    <p>Build interactive polls, collect votes in real-time, and visualize results with beautiful analytics. Make data-driven decisions with confidence.</p>
                    
                    <div class="create-actions">
                        <?php if (isLoggedIn()): ?>
                            <a href="create-poll.php" class="create-btn primary">Create your first Poll</a>
                        <?php else: ?>
                            <a href="auth/register.php" class="create-btn primary">Create your first Poll</a>
                        <?php endif; ?>
                        <a href="#demo" class="create-btn secondary">View Demo</a>
                    </div>
                </div>
                
                <div class="demo-poll">
                    <div class="demo-poll-container">
                        <div class="demo-header">
                            <span class="demo-title">Sample Poll</span>
                            <span class="live-indicator">🔴 Live</span>
                        </div>
                        
                        <div class="demo-options">
                            <div class="demo-option">
                                <div class="demo-option-header">
                                    <span>Option A</span>
                                    <span class="demo-percentage">42%</span>
                                </div>
                                <div class="demo-progress">
                                    <div class="demo-fill" style="width: 42%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
                                </div>
                            </div>
                            
                            <div class="demo-option">
                                <div class="demo-option-header">
                                    <span>Option B</span>
                                    <span class="demo-percentage">31%</span>
                                </div>
                                <div class="demo-progress">
                                    <div class="demo-fill" style="width: 31%; background: linear-gradient(90deg, #10b981, #059669);"></div>
                                </div>
                            </div>
                            
                            <div class="demo-option">
                                <div class="demo-option-header">
                                    <span>Option C</span>
                                    <span class="demo-percentage">27%</span>
                                </div>
                                <div class="demo-progress">
                                    <div class="demo-fill" style="width: 27%; background: linear-gradient(90deg, #3b82f6, #1d4ed8);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="about" class="features-section">
            <div class="section-header">
                <h2>Everything You Need for Effective Polling</h2>
                <p>From creation to analysis, our platform provides all the tools to run successful polls and gather meaningful insights.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3>Easy Poll Creation</h3>
                    <p>Create polls in minutes with our intuitive interface. Add multiple choice options, set expiry dates, and customize visibility settings.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Real-Time Voting</h3>
                    <p>Watch votes come in live with instant updates. No page refreshes needed - results update automatically as people vote.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Smart Analytics</h3>
                    <p>Visualize results with beautiful charts and graphs. Track voting patterns, demographics, and engagement metrics.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure & Private</h3>
                    <p>Advanced security measures protect your polls. Control who can vote with privacy settings and authentication options.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Optimized</h3>
                    <p>Perfect experience across all devices. Your polls look great and work seamlessly on desktop, tablet, and mobile.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Team Collaboration</h3>
                    <p>Share poll management with team members. Collaborate on poll creation and analysis with role-based permissions.</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <img src="assets/poll.png" alt="CampusPoll">
                        <h3>CampusPoll</h3>
                    </div>
                    <p>The intelligent polling platform that helps teams make better decisions through real-time voting and analytics.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#about">About</a></li>
                        <li><a href="#blog">Blog</a></li>
                        <li><a href="#careers">Careers</a></li>
                        <li><a href="#press">Press Kit</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#help">Help Center</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                        <li><a href="#status">Status</a></li>
                        <li><a href="#community">Community</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <hr>
                <div class="footer-bottom-content">
                    <p>&copy; 2025 CampusPoll. All rights reserved.</p>
                    <div class="footer-links">
                        <a href="#privacy">Privacy Policy</a>
                        <a href="#terms">Terms of Service</a>
                        <a href="#cookies">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="assets/enhanced-script.js"></script>
</body>
</html>