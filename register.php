<?php
// register.php
require_once 'includes/auth.php';

if(isLoggedIn()) {
    header("Location: " . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $role = $_POST['role']; // 'student' or 'teacher'
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    // Use email as the username for login purposes
    $username = $email; 
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($role !== 'student' && $role !== 'teacher') {
        $error = "Invalid role selected.";
    } else {
        // Check if username exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if($check->get_result()->num_rows > 0) {
            $error = "This Email is already registered.";
        } else {
            // Create User
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed, $email, $name, $role);
            
            $conn->begin_transaction();
            try {
                if($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    if($role === 'student') {
                        // Generate a random roll number for the student
                        $generated_roll = 'STU' . rand(1000, 9999);
                        $s_stmt = $conn->prepare("INSERT INTO students (user_id, roll_number) VALUES (?, ?)");
                        if($s_stmt) {
                            $s_stmt->bind_param("is", $user_id, $generated_roll);
                            $s_stmt->execute();
                        }
                    } else if ($role === 'teacher') {
                        // Generate a random employee ID for the teacher
                        $generated_emp = 'TCH' . rand(1000, 9999);
                        $t_stmt = $conn->prepare("INSERT INTO teachers (user_id, employee_id) VALUES (?, ?)");
                        if($t_stmt) {
                            $t_stmt->bind_param("is", $user_id, $generated_emp);
                            $t_stmt->execute();
                        }
                    }
                    
                    $conn->commit();
                    $success = "Registration successful! You can now <a href='login.php' style='color:var(--dark); text-decoration:underline;'>login here</a> using your email.";
                } else {
                    $conn->rollback();
                    $error = "Database error during registration.";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Examsys</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-card {
            max-width: 500px;
        }
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
    <div class="auth-wrapper" style="position:relative; min-height:100vh; padding: 50px 0;">
        <div class="auth-nav">
            <a href="index.php">← Home</a>
            <a href="login.php">Login</a>
        </div>
        <div class="auth-card">
            <div style="margin-bottom: 20px; text-align: center;">
                <img src="assets/images/logo.png" alt="University of Sahiwal Logo" style="height: 70px; object-fit: contain;">
            </div>
            <h1>Create Account</h1>
            <p style="color: var(--gray); margin-bottom: 24px;">Register as a Student or Teacher</p>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if(!$success): ?>
            <form method="POST" action="register.php">
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:0;">
                    <div style="grid-column: 1 / -1;">
                        <label>Register As</label>
                        <select name="role" class="form-control" required style="cursor:pointer;">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Min 6 chars" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Retype password">
                    </div>
                </div>
                <button type="submit" class="btn btn-block" style="background:var(--secondary);">Complete Registration</button>
            </form>
            <div style="margin-top: 20px; font-size: 14px; text-align:center;">
                Already have an account? <a href="login.php" style="color:var(--primary); text-decoration:none; font-weight:600;">Sign In</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
