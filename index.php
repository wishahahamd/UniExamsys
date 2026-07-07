<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Examination System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <style>
        /* Landing Page Specific Styles */
        body { 
            background: #fff; 
            transition: background 0.3s, color 0.3s;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background 0.3s, box-shadow 0.3s;
        }
        .navbar .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-links a {
            margin: 0 15px;
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: var(--primary); }
        .nav-buttons .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
            margin-right: 10px;
        }
        .nav-buttons .btn-outline:hover { background: var(--primary); color: white; }

        .hero {
            display: flex;
            align-items: center;
            padding: 80px 5%;
            background: linear-gradient(135deg, rgba(79,70,229,0.05) 0%, rgba(16,185,129,0.05) 100%);
            min-height: 80vh;
            transition: background 0.3s;
        }
        .hero-text { flex: 1; padding-right: 50px; }
        .hero-text h1 { font-size: 56px; font-weight: 800; line-height: 1.2; margin-bottom: 20px; color: var(--dark); transition: color 0.3s; }
        .hero-text h1 span { color: var(--primary); }
        .hero-text p { font-size: 20px; color: var(--gray); margin-bottom: 30px; line-height: 1.8; }
        .hero-image { flex: 1; display: flex; justify-content: center; }
        .hero-image img { width: 100%; max-width: 600px; border-radius: 20px; box-shadow: var(--shadow-lg); }

        .section-title { text-align: center; margin-bottom: 50px; }
        .section-title h2 { font-size: 36px; color: var(--dark); font-weight: 700; transition: color 0.3s; }
        .section-title p { color: var(--gray); margin-top: 10px; font-size: 18px; }

        .features { padding: 80px 5%; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .feature-card { padding: 40px 30px; background: white; border-radius: 16px; box-shadow: var(--shadow); text-align: center; transition: 0.3s; border: 1px solid var(--gray-light); }
        .feature-card:hover { transform: translateY(-10px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
        .feature-icon { width: 70px; height: 70px; background: rgba(79,70,229,0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px; }
        .feature-card h3 { font-size: 22px; margin-bottom: 15px; color: var(--dark); transition: color 0.3s; }
        .feature-card p { color: var(--gray); line-height: 1.6; }

        .how-it-works { padding: 80px 5%; background: #F8FAFC; transition: background 0.3s; }
        .steps { display: flex; flex-wrap: wrap; justify-content: space-between; position: relative; }
        .step { text-align: center; flex: 1; min-width: 250px; padding: 20px; position: relative; z-index: 2; }
        .step-num { width: 60px; height: 60px; background: var(--primary); color: white; font-size: 24px; font-weight: bold; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 20px; border: 5px solid white; box-shadow: var(--shadow); transition: border-color 0.3s; }
        .step h3 { margin-bottom: 10px; font-size: 20px; transition: color 0.3s; }
        
        .stats-section { padding: 80px 5%; background: var(--primary); color: white; text-align: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 50px; }
        .stat-item h4 { font-size: 48px; font-weight: 800; margin-bottom: 10px; }
        .stat-item p { font-size: 18px; opacity: 0.9; }

        .cta-section { padding: 100px 5%; text-align: center; background: white; transition: background 0.3s; }
        .cta-section h2 { font-size: 40px; margin-bottom: 20px; transition: color 0.3s; }
        .cta-section p { font-size: 20px; color: var(--gray); margin-bottom: 40px; }

        footer { background: var(--dark); color: white; padding: 40px 5%; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); transition: background 0.3s, border-color 0.3s; }
        footer p { opacity: 0.7; }

        /* Landing Page Dark Mode Overrides */
        body.dark-mode {
            background: #0B0F19;
            color: #F9FAFB;
        }
        body.dark-mode .navbar {
            background: #111827;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        body.dark-mode .nav-links a {
            color: #F9FAFB;
        }
        body.dark-mode .nav-links a:hover {
            color: var(--primary);
        }
        body.dark-mode .hero {
            background: linear-gradient(135deg, rgba(79,70,229,0.08) 0%, rgba(16,185,129,0.08) 100%);
        }
        body.dark-mode .hero-text h1 {
            color: #F9FAFB;
        }
        body.dark-mode .feature-card {
            background: #111827;
            border-color: #1F2937;
        }
        body.dark-mode .feature-card h3 {
            color: #F9FAFB;
        }
        body.dark-mode .how-it-works {
            background: #0F172A;
        }
        body.dark-mode .step-num {
            border-color: #0F172A;
        }
        body.dark-mode .step h3 {
            color: #F9FAFB;
        }
        body.dark-mode .cta-section {
            background: #0B0F19;
            color: #F9FAFB;
        }
        body.dark-mode .cta-section h2 {
            color: #F9FAFB;
        }
        body.dark-mode footer {
            background: #090D16;
            border-top: 1px solid #1F2937;
        }
    </style>
</head>
<body>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>

    <!-- Header Navigation -->
    <nav class="navbar">
        <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
            <img src="assets/images/logo.png" alt="University of Sahiwal Logo" style="height: 45px; object-fit: contain;">
        </a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How it Works</a>
        </div>
        <div class="nav-buttons" style="display: flex; align-items: center;">
            <button id="theme-toggle" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 5px; margin-right: 15px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;" title="Toggle Theme">🌙</button>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?= $_SESSION['role'] ?>/dashboard.php" class="btn" style="padding: 10px 24px; border-radius: 6px; margin-right:5px;">Go to Dashboard</a>
                <a href="logout.php" class="btn btn-outline" style="padding: 10px 24px; border-radius: 6px; border-color:var(--danger); color:var(--danger);">Logout</a>
            <?php else: ?>
                <a href="login.php?role=student" class="btn btn-outline" style="padding: 10px 18px; border-radius: 6px; font-size:14px; margin-right:5px;">Student Login</a>
                <a href="login.php?role=teacher" class="btn" style="padding: 10px 18px; border-radius: 6px; font-size:14px; background:#10B981; margin-right:5px;">Teacher Login</a>
                <a href="register.php" class="btn btn-outline" style="padding: 10px 18px; border-radius: 6px; font-size:14px; border-color:var(--gray); color:var(--gray);">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- SECTION 1: Hero -->
    <section class="hero" id="home">
        <div class="hero-text">
            <h1>The Modern Way to <span>Calculate GPA</span> & Track Progress.</h1>
            <p>A simplified result evaluation system where students and teachers register, teachers enter exam marks, and the system automatically calculates cumulative GPAs with beautiful visual charts.</p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="login.php?role=student" class="btn" style="font-size: 16px; padding: 14px 28px;">Student Login</a>
                <a href="login.php?role=teacher" class="btn" style="font-size: 16px; padding: 14px 28px; background:#10B981;">Teacher Login</a>
                <a href="register.php" class="btn btn-outline" style="font-size: 16px; padding: 14px 28px; border: 2px solid var(--primary); color: var(--primary); background: transparent;">Register Now</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&q=80&w=1000" alt="University Students Dashboard">
        </div>
    </section>

    <!-- SECTION 2: Features -->
    <section class="features" id="features">
        <div class="section-title">
            <h2>Core Platform Features</h2>
            <p>Simplified evaluation, secure portals, and visual charts for everyone.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Real-Time Grading</h3>
                <p>Teachers enter internal and external marks manually, and the system instantly estimates grades and registers them in the database.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🧮</div>
                <h3>Automatic CGPA Calculation</h3>
                <p>Calculates exact cumulative GPA averages dynamically using credit hours and grades, removing manual computational steps.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3>Visual Grade Analytics</h3>
                <p>Presents academic standing and grade distribution metrics inside beautiful line and doughnut charts synced with dark mode.</p>
            </div>
        </div>
    </section>

    <!-- SECTION 3: How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-title">
            <h2>How the Process Works</h2>
            <p>A streamlined workflow from registration to evaluation.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Registration & Enrollment</h3>
                <p style="color:var(--gray)">Students and teachers register themselves in the portal. Students get enrolled in their respective semesters.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Manual Marks Entry</h3>
                <p style="color:var(--gray)">The teacher selects the course and enters internal (max 40) and external (max 60) marks for the student.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Auto CGPA & Charts</h3>
                <p style="color:var(--gray)">The system instantly calculates the student's cumulative CGPA and displays it in beautiful, interactive charts.</p>
            </div>
        </div>
    </section>

    <!-- SECTION 4: Statistics -->
    <section class="stats-section">
        <h2>Trusted by Modern Educational Institutions</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <h4>99.9%</h4>
                <p>System Uptime</p>
            </div>
            <div class="stat-item">
                <h4>10k+</h4>
                <p>Students Managed</p>
            </div>
            <div class="stat-item">
                <h4>0</h4>
                <p>Calculation Errors</p>
            </div>
        </div>
    </section>

    <!-- SECTION 5: CTA -->
    <section class="cta-section">
        <h2>Ready to transform your examination process?</h2>
        <p>Join the next generation of academic management systems.</p>
        <a href="register.php" class="btn" style="font-size: 20px; padding: 16px 40px; box-shadow: var(--shadow-lg);">Create Fast Account</a>
    </section>

    <!-- Footer -->
    <footer>
        <div style="display:flex; justify-content:space-between; align-items:center; max-width: 1200px; margin: 0 auto;">
            <div style="font-size: 24px; font-weight:bold;">ExamSys</div>
            <p>&copy; 2026 University Examination System. All rights reserved.</p>
            <div style="display:flex; gap: 15px;">
                <a href="admin_login.php" style="color:var(--gray); text-decoration:none;">Admin Access</a>
            </div>
        </div>
    </footer>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        
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
        }
        
        // Initial load
        const currentTheme = localStorage.getItem('theme') || 'light';
        setTheme(currentTheme);
        
        themeToggle.addEventListener('click', () => {
            const isDark = document.body.classList.contains('dark-mode');
            setTheme(isDark ? 'light' : 'dark');
        });
    </script>
</body>
</html>
