<?php
// includes/auth.php
session_start();
require_once __DIR__ . '/../config.php';

function login($username, $password) {
    global $conn;
    
    // Check if the input is a student's roll number
    $stmt = $conn->prepare("SELECT user_id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['user_id'];
        $stmt = $conn->prepare("SELECT user_id, username, password, role, is_active FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        // Check if the input is a teacher's employee ID
        $stmt = $conn->prepare("SELECT user_id FROM teachers WHERE employee_id = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user_id = $row['user_id'];
            $stmt = $conn->prepare("SELECT user_id, username, password, role, is_active FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
        } else {
            // Fallback to username or email direct lookup in users table
            $stmt = $conn->prepare("SELECT user_id, username, password, role, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($user = $result->fetch_assoc()) {
        if(!$user['is_active']) {
            return ["status" => false, "message" => "Account disabled. Please contact administrator."];
        }
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return ["status" => true, "role" => $user['role']];
        } else {
            return ["status" => false, "message" => "Invalid username or password."];
        }
    }
    return ["status" => false, "message" => "Invalid username or password."];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($role) {
    if(!isLoggedIn() || $_SESSION['role'] !== $role) {
        header("Location: ../index.php");
        exit();
    }
}

function requireLogin() {
    if(!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}
?>
