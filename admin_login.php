<?php
require_once 'includes/auth.php';

if(isLoggedIn()) {
    if($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: " . $_SESSION['role'] . "/dashboard.php");
        exit();
    }
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $res = login($username, $password);
    if($res['status']) {
        if($res['role'] == 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        } else {
            session_destroy();
            $error = "Unauthorized attempt. Only administrators allowed.";
        }
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Examsys</title>
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
        .auth-wrapper {
            background: linear-gradient(135deg, #1F2937 0%, #000000 100%);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper" style="position:relative;">
        <div class="auth-nav">
            <a href="index.php">← Home</a>
            <a href="login.php">User Login</a>
        </div>
        <div class="auth-card" style="border-top: 5px solid var(--danger);">
            <div style="margin-bottom: 20px; text-align: center;">
                <img src="assets/images/logo.png" alt="University of Sahiwal Logo" style="height: 70px; object-fit: contain;">
            </div>
            <h1>Administrator Access</h1>
            <p style="color: var(--gray); margin-bottom: 24px;">Secure management portal</p>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="admin">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="admin123">
                </div>
                <button type="submit" class="btn btn-block btn-danger">Admin Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
