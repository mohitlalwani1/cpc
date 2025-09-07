<?php
require_once '../config/database.php';
require_once '../includes/session.php';

if (isLoggedIn()) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusPoll</title>
    <link rel="stylesheet" href="../assets/enhanced-login.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <div class="left-box">
                <h2>Welcome Back!</h2>
                <p>Don't have an account?</p>
                <a href="register.php"><button>Register</button></a>
            </div>
            <div class="right-box">
                <h2>Login</h2>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="input-box">
                        <input type="text" name="username" placeholder="Username or Email" required>
                        <i class='bx bx-user'></i>
                    </div>
                    <div class="input-box">
                        <input type="password" name="password" placeholder="Password" required>
                        <i class='bx bx-lock-alt'></i>
                    </div>
                    <a href="forgot.php" class="forgot">Forgot Password?</a>
                    <button type="submit" class="login-btn">Login</button>
                </form>
                <div class="back-home">
                    <a href="../index.php">← Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>