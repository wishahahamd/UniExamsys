<?php
require_once 'includes/auth.php';

if(isLoggedIn()) {
    header("Location: " . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $res = login($username, $password);
    if($res['status']) {
        if($res['role'] == 'admin') {
            // Force admin to use admin portal
            session_destroy();
            $error = "Administrators must use the dedicated Admin Portal.";
        } else {
            header("Location: " . $res['role'] . "/dashboard.php");
            exit();
        }
    } else {
        $error = $res['message'];
    }
}

// Role-based personalization
$role_param = isset($_GET['role']) ? trim($_GET['role']) : '';
$login_title = "Student & Teacher Login";
$username_label = "Roll Number, Employee ID, or Email";
$username_placeholder = "Enter Roll Number, Employee ID, or Email";
$accent_color = "var(--primary)";
$sub_text = "Enter your university credentials to access the examination portal.";

if ($role_param === 'student') {
    $login_title = "Student Portal Login";
    $username_label = "Roll Number or Email";
    $username_placeholder = "Enter your Roll Number or Email";
    $sub_text = "Access your digital hall tickets, academic records, and SGPA/CGPA transcripts.";
} elseif ($role_param === 'teacher') {
    $login_title = "Teacher Portal Login";
    $username_label = "Employee ID or Email";
    $username_placeholder = "Enter your Employee ID or Email";
    $accent_color = "#10B981";
    $sub_text = "Access your assigned course catalogs and input student midterm/final examination grades.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $login_title ?> - Examsys</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <style>
        .auth-nav {
            position: absolute;
            top: 20px;
            right: 30px;
            display: flex;
            gap: 15px;
        }
        .auth-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 4px;
            background: rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .auth-nav a:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper" style="position:relative; background: linear-gradient(135deg, <?= $accent_color ?> 0%, #1e293b 100%);">
        <div class="auth-nav">
            <a href="index.php">← Home</a>
            <a href="register.php">Register</a>
            <a href="admin_login.php" style="background: rgba(0,0,0,0.5);">Admin Login</a>
        </div>
        <div class="auth-card">
            <div style="margin-bottom: 20px; text-align: center;">
                <img src="assets/images/logo.png" alt="University of Sahiwal Logo" style="height: 70px; object-fit: contain;">
            </div>
            <h1><?= $login_title ?></h1>
            <p style="color: var(--gray); margin-bottom: 24px; font-size: 14px; line-height: 1.5;"><?= $sub_text ?></p>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php?role=<?= htmlspecialchars($role_param) ?>">
                <div class="form-group">
                    <label><?= $username_label ?></label>
                    <input type="text" name="username" class="form-control" required placeholder="<?= $username_placeholder ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-block" style="background: <?= $accent_color ?>;">Secure Sign In</button>
            </form>
            <div style="margin-top: 20px; font-size: 14px;">
                Don't have an account? <a href="register.php" style="color:<?= $accent_color ?>; text-decoration:none; font-weight:600;">Register Here</a>
            </div>
        </div>
    </div>
</body>
</html>
