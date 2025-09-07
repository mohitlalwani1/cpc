<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();
$user = getCurrentUser();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $options = array_filter(array_map('trim', $_POST['options']));
    
    if (empty($title)) {
        $error = 'Poll title is required';
    } elseif (count($options) < 2) {
        $error = 'At least 2 options are required';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Insert poll
            $query = "INSERT INTO polls (title, description, creator_id) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$title, $description, $user['id']]);
            $poll_id = $db->lastInsertId();
            
            // Insert options
            $query = "INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            
            foreach ($options as $option) {
                if (!empty($option)) {
                    $stmt->execute([$poll_id, $option]);
                }
            }
            
            $db->commit();
            $success = 'Poll created successfully!';
            
            // Reset form
            $_POST = array();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to create poll. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Poll - CampusPoll</title>
    <link rel="stylesheet" href="assets/create-poll.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="top-nav">
            <div class="nav-brand">
                <img src="assets/poll.png" alt="CampusPoll">
                <h2>CampusPoll</h2>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="browse-polls.php">Browse</a>
                <a href="auth/logout.php">Logout</a>
            </div>
        </nav>

        <div class="create-poll-container">
            <div class="left-panel">
                <h1>Create Your Poll</h1>
                <p>Craft questions that matter to your campus community.</p>
                <ul class="features-list">
                    <li><i class="fas fa-bolt"></i> <strong>Quick & Easy</strong><br>Create a poll in less than a minute</li>
                    <li><i class="fas fa-share"></i> <strong>Share Instantly</strong><br>Share your poll via link</li>
                    <li><i class="fas fa-chart-bar"></i> <strong>Real-time Results</strong><br>See visualized results instantly</li>
                </ul>
            </div>

            <div class="right-panel">
                <h2>New Poll Form</h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form id="pollForm" method="POST">
                    <div class="form-group">
                        <label for="title">Poll Question *</label>
                        <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Options *</label>
                        <div id="optionsContainer">
                            <input type="text" name="options[]" placeholder="Option 1" required>
                            <input type="text" name="options[]" placeholder="Option 2" required>
                        </div>
                        <button type="button" id="addOption" class="add-option-btn">
                            <i class="fas fa-plus"></i> Add Option
                        </button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="create-btn">
                            <i class="fas fa-plus"></i> Create Poll
                        </button>
                        <button type="reset" class="reset-btn">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let optionCount = 2;

        document.getElementById("addOption").addEventListener("click", function () {
            optionCount++;
            const input = document.createElement("input");
            input.type = "text";
            input.name = "options[]";
            input.placeholder = "Option " + optionCount;
            input.required = true;

            document.getElementById("optionsContainer").appendChild(input);
        });

        document.getElementById("pollForm").addEventListener("reset", function () {
            // Remove extra option inputs
            const container = document.getElementById("optionsContainer");
            const inputs = container.querySelectorAll('input');
            for (let i = 2; i < inputs.length; i++) {
                inputs[i].remove();
            }
            optionCount = 2;
        });
    </script>
</body>
</html>