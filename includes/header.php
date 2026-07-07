<?php
// includes/header.php
if(session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examsys - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
</head>
<body>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header" style="text-align: center; padding: 20px 10px;">
                <img src="../assets/images/logo.png" alt="University of Sahiwal Logo" style="max-width: 85%; max-height: 60px; object-fit: contain;">
            </div>
            <ul class="sidebar-menu">
                <?php if($role === 'admin'): ?>
                    <li><a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"><i>🏠</i> <span>Dashboard</span></a></li>
                    <li><a href="students.php" class="<?= $currentPage == 'students.php' ? 'active' : '' ?>"><i>👨‍🎓</i> <span>Students</span></a></li>
                    <li><a href="teachers.php" class="<?= $currentPage == 'teachers.php' ? 'active' : '' ?>"><i>👨‍🏫</i> <span>Teachers</span></a></li>
                    <li><a href="courses.php" class="<?= $currentPage == 'courses.php' ? 'active' : '' ?>"><i>📚</i> <span>Courses</span></a></li>
                    <li><a href="enrollments.php" class="<?= $currentPage == 'enrollments.php' ? 'active' : '' ?>"><i>✍️</i> <span>Enrollments</span></a></li>
                    <li><a href="results.php" class="<?= $currentPage == 'results.php' ? 'active' : '' ?>"><i>📊</i> <span>Results</span></a></li>
                <?php elseif($role === 'teacher'): ?>
                    <li><a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"><i>🏠</i> <span>Dashboard</span></a></li>
                    <li><a href="enrollments.php" class="<?= $currentPage == 'enrollments.php' ? 'active' : '' ?>"><i>✍️</i> <span>Enrollments</span></a></li>
                    <li><a href="marks_entry.php" class="<?= $currentPage == 'marks_entry.php' ? 'active' : '' ?>"><i>🖋️</i> <span>Marks Entry</span></a></li>
                <?php elseif($role === 'student'): ?>
                    <li><a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"><i>🏠</i> <span>Dashboard</span></a></li>
                    <li><a href="results.php" class="<?= $currentPage == 'results.php' ? 'active' : '' ?>"><i>📊</i> <span>Results</span></a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <div style="display: flex; align-items: center;">
                    <button class="toggle-sidebar" id="toggle-btn">☰</button>
                    <h2 style="font-size: 18px; font-weight: 600; color: var(--dark);">
                        <?php 
                        $titles = [
                            'dashboard.php' => 'Dashboard Overview',
                            'students.php' => 'Student Management',
                            'teachers.php' => 'Faculty Management',
                            'courses.php' => 'Course Catalog',
                            'assignments.php' => 'Teaching Assignments',
                            'exams.php' => 'Exam Sessions',
                            'enrollments.php' => 'Course Enrollments',
                            'results.php' => 'Examination Results',
                            'marks_entry.php' => 'Enter Student Marks',
                            'hall_ticket.php' => 'Exam Hall Ticket',
                            'transcript.php' => 'Official Academic Transcript',
                            'gazette.php' => 'Official Exam Gazette',
                            'tr.php' => 'Tabulation Register (TR)',
                            'gpa_calculator.php' => 'GPA & CGPA Simulation Tool'
                        ];
                        echo $titles[$currentPage] ?? 'Examination System';
                        ?>
                    </h2>
                </div>
                <div style="display: flex; align-items: center;">
                    <button id="theme-toggle" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 5px; margin-right: 15px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;" title="Toggle Theme">🌙</button>
                    <div class="user-profile">
                        <span class="role-badge"><?= htmlspecialchars($role) ?></span>
                        <span style="font-weight: 500;"><?= htmlspecialchars($username) ?></span>
                        <a href="../logout.php" class="btn btn-danger" style="padding: 8px 16px; font-size: 14px;">Logout</a>
                    </div>
                </div>
            </div>
            
            <script>
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('toggle-btn');
                const themeToggle = document.getElementById('theme-toggle');
                
                // Load sidebar state
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    sidebar.classList.add('collapsed');
                }
                
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                });

                // Function to set theme
                function setTheme(theme) {
                    if (theme === 'dark') {
                        document.body.classList.add('dark-mode');
                        themeToggle.textContent = '☀️';
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.body.classList.remove('dark-mode');
                        themeToggle.textContent = '🌙';
                        localStorage.setItem('theme', 'light');
                    }
                    // Dispatch custom event for charts or other visual components
                    const event = new CustomEvent('themeChanged', { detail: { theme: theme } });
                    document.dispatchEvent(event);
                }
                
                // Load theme state
                const currentTheme = localStorage.getItem('theme') || 'light';
                setTheme(currentTheme);
                
                themeToggle.addEventListener('click', () => {
                    const isDark = document.body.classList.contains('dark-mode');
                    setTheme(isDark ? 'light' : 'dark');
                });
            </script>
            <div class="content-wrapper">
