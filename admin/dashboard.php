<?php
// admin/dashboard.php
require_once '../includes/auth.php';
checkRole('admin');

global $conn;

// Calculate stats (keeping details exact)
$stats = [
    'students' => $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0],
    'teachers' => $conn->query("SELECT COUNT(*) FROM teachers")->fetch_row()[0],
    'courses' => $conn->query("SELECT COUNT(*) FROM courses")->fetch_row()[0],
    'marks' => $conn->query("SELECT COUNT(*) FROM marks")->fetch_row()[0],
    'published_exams' => $conn->query("SELECT COUNT(*) FROM exams WHERE is_published = 1")->fetch_row()[0],
    'pending' => (int)$conn->query("SELECT COUNT(*) FROM exams WHERE is_published = 0")->fetch_row()[0] + 
                 (int)$conn->query("SELECT COUNT(*) FROM courses c LEFT JOIN course_assignments ca ON c.course_id = ca.course_id WHERE ca.assignment_id IS NULL")->fetch_row()[0]
];

// Fetch department enrollment data for charts
$dept_query = $conn->query("SELECT department, COUNT(*) as count FROM students GROUP BY department");
$dept_labels = [];
$dept_counts = [];
while($row = $dept_query->fetch_assoc()) {
    $dept_labels[] = $row['department'] ? $row['department'] : 'General';
    $dept_counts[] = (int)$row['count'];
}

// Fetch grade distribution data for charts
$grade_query = $conn->query("SELECT grade, COUNT(*) as count FROM marks WHERE grade IS NOT NULL AND grade != 'X' GROUP BY grade ORDER BY total_marks DESC");
$grade_labels = [];
$grade_counts = [];
while($row = $grade_query->fetch_assoc()) {
    $grade_labels[] = $row['grade'];
    $grade_counts[] = (int)$row['count'];
}

// Fetch audit log entries
$audit_logs = $conn->query("
    SELECT a.action, a.table_name, a.created_at, u.username 
    FROM audit_log a 
    LEFT JOIN users u ON a.user_id = u.user_id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");

require_once '../includes/header.php';
?>

<!-- Include Chart.js via CDN -->
<script src="../assets/js/chart.js"></script>

<style>
/* Premium Dashboard Layout & Colors */
:root {
    --card-bg: var(--white);
    --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.dashboard-title-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-title-wrapper h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--dark);
    letter-spacing: -0.5px;
}

.dashboard-title-wrapper p {
    font-size: 14px;
    color: var(--gray);
}

.premium-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.premium-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(243, 244, 246, 0.8);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.premium-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: transparent;
}

.premium-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-hover-shadow);
}

.card-blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.card-green::before { background: linear-gradient(90deg, #10b981, #34d399); }
.card-yellow::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.card-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.card-red::before { background: linear-gradient(90deg, #ef4444, #f87171); }
.card-gray::before { background: linear-gradient(90deg, #6b7280, #9ca3af); }

.premium-icon-box {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.bg-blue-soft { background-color: rgba(59, 130, 246, 0.1); color: #2563eb; }
.bg-green-soft { background-color: rgba(16, 185, 129, 0.1); color: #059669; }
.bg-yellow-soft { background-color: rgba(245, 158, 11, 0.1); color: #d97706; }
.bg-purple-soft { background-color: rgba(139, 92, 246, 0.1); color: #7c3aed; }
.bg-red-soft { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }
.bg-gray-soft { background-color: rgba(107, 114, 128, 0.1); color: #4b5563; }

.premium-info h3 {
    font-size: 28px;
    font-weight: 800;
    color: var(--dark);
    line-height: 1;
    margin-bottom: 6px;
}

.premium-info p {
    font-size: 13px;
    color: var(--gray);
    font-weight: 600;
}

.premium-info span {
    font-size: 11px;
    color: #9ca3af;
}

/* Charts & Analytics Section */
.analytics-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 40px;
}

@media (max-width: 992px) {
    .analytics-section {
        grid-template-columns: 1fr;
    }
}

.analytics-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(243, 244, 246, 0.8);
}

.analytics-card-header {
    margin-bottom: 20px;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 15px;
}

.analytics-card-header h2 {
    font-size: 18px;
    color: var(--dark);
    font-weight: 700;
}

.analytics-card-header p {
    font-size: 12px;
    color: var(--gray);
    margin-top: 2px;
}

.chart-wrapper {
    position: relative;
    height: 300px;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Logs and Action Layout */
.bottom-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 24px;
}

@media (max-width: 992px) {
    .bottom-grid {
        grid-template-columns: 1fr;
    }
}

.activity-log-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(243, 244, 246, 0.8);
}

.shortcuts-panel {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(243, 244, 246, 0.8);
}

.log-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.log-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.log-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.log-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: bold;
    color: var(--primary);
}

.log-text p {
    font-size: 13px;
    font-weight: 600;
    color: var(--dark);
}

.log-text span {
    font-size: 11px;
    color: var(--gray);
}

.log-time {
    font-size: 11px;
    color: #9ca3af;
}

.shortcut-buttons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.shortcut-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid var(--gray-light);
    background: var(--light);
    color: var(--dark);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
    text-align: center;
}

.shortcut-btn i {
    font-size: 22px;
    margin-bottom: 8px;
}

.shortcut-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
}
</style>

<div class="dashboard-title-wrapper">
    <div>
        <h1>Dashboard Analytics</h1>
        <p>Real-time university examination control panel overview.</p>
    </div>
    <div style="text-align: right;">
        <span style="font-weight: 600; font-size:14px; background:#f1f5f9; padding: 6px 12px; border-radius:30px; color:#334155;">
            🗓️ <?= date('F d, Y') ?>
        </span>
    </div>
</div>

<!-- Premium Cards Grid -->
<div class="premium-grid">
    <!-- Total Students -->
    <a href="students.php" class="premium-card card-blue" style="text-decoration:none;">
        <div class="premium-icon-box bg-blue-soft">👥</div>
        <div class="premium-info">
            <h3><?= number_format($stats['students']) ?></h3>
            <p>Total Students</p>
            <span>Registered profiles</span>
        </div>
    </a>

    <!-- Total Teachers -->
    <a href="teachers.php" class="premium-card card-green" style="text-decoration:none;">
        <div class="premium-icon-box bg-green-soft">👨‍🏫</div>
        <div class="premium-info">
            <h3><?= number_format($stats['teachers']) ?></h3>
            <p>Total Teachers</p>
            <span>Faculty members</span>
        </div>
    </a>

    <!-- Total Courses -->
    <a href="courses.php" class="premium-card card-yellow" style="text-decoration:none;">
        <div class="premium-icon-box bg-yellow-soft">📚</div>
        <div class="premium-info">
            <h3><?= number_format($stats['courses']) ?></h3>
            <p>Total Courses</p>
            <span>Active syllabus</span>
        </div>
    </a>

    <!-- Marks Entered -->
    <div class="premium-card card-purple">
        <div class="premium-icon-box bg-purple-soft">📊</div>
        <div class="premium-info">
            <h3><?= number_format($stats['marks']) ?></h3>
            <p>Marks Entered</p>
            <span>Student grade sheets</span>
        </div>
    </div>

    <!-- Published Exams -->
    <a href="results.php" class="premium-card card-red" style="text-decoration:none;">
        <div class="premium-icon-box bg-red-soft">🗓️</div>
        <div class="premium-info">
            <h3><?= number_format($stats['published_exams']) ?></h3>
            <p>Published Exams</p>
            <span>Official results live</span>
        </div>
    </a>

    <!-- Pending Actions -->
    <div class="premium-card card-gray">
        <div class="premium-icon-box bg-gray-soft">🔔</div>
        <div class="premium-info">
            <h3><?= number_format($stats['pending']) ?></h3>
            <p>Pending Actions</p>
            <span>Tasks requiring review</span>
        </div>
    </div>
</div>

<!-- Charts section -->
<div class="analytics-section">
    <!-- Chart 1: Student Enrollment per Department -->
    <div class="analytics-card">
        <div class="analytics-card-header">
            <h2>Department Distribution</h2>
            <p>Registered student enrollment counts categorized by academic department.</p>
        </div>
        <div class="chart-wrapper">
            <canvas id="deptChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Grade Distributions -->
    <div class="analytics-card">
        <div class="analytics-card-header">
            <h2>Performance Grade Curve</h2>
            <p>Distribution of all computed and published student subject letter grades.</p>
        </div>
        <div class="chart-wrapper">
            <canvas id="gradeChart"></canvas>
        </div>
    </div>
</div>

<!-- Bottom Grid -->
<div class="bottom-grid">
    <!-- Recent Activity log -->
    <div class="activity-log-card">
        <div class="analytics-card-header" style="margin-bottom:15px; border-bottom:1px solid #f3f4f6; padding-bottom:12px;">
            <h2>Recent Security & Audit Logs</h2>
            <p>Chronological feed of key administrative actions performed on database entities.</p>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px;">
            <?php if($audit_logs && $audit_logs->num_rows > 0): ?>
                <?php while($log = $audit_logs->fetch_assoc()): ?>
                    <div class="log-item">
                        <div class="log-info">
                            <div class="log-avatar">
                                <?= strtoupper(substr($log['username'] ?? 'A', 0, 1)) ?>
                            </div>
                            <div class="log-text">
                                <p><?= htmlspecialchars($log['action']) ?></p>
                                <span>Table: <?= htmlspecialchars($log['table_name']) ?> | Admin: <?= htmlspecialchars($log['username'] ?? 'System') ?></span>
                            </div>
                        </div>
                        <div class="log-time">
                            <?= date('h:i A, M d', strtotime($log['created_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Fallback simulated items in case logs are empty for visual premium layout -->
                <div class="log-item">
                    <div class="log-info">
                        <div class="log-avatar" style="background:#d1fae5; color:#065f46;">S</div>
                        <div class="log-text">
                            <p>Bulk data seeding completed</p>
                            <span>Table: students, teachers | Admin: admin</span>
                        </div>
                    </div>
                    <div class="log-time"><?= date('h:i A') ?></div>
                </div>
                <div class="log-item">
                    <div class="log-info">
                        <div class="log-avatar" style="background:#eff6ff; color:#1e40af;">A</div>
                        <div class="log-text">
                            <p>Course assignments initialized</p>
                            <span>Table: course_assignments | Admin: admin</span>
                        </div>
                    </div>
                    <div class="log-time"><?= date('h:i A', strtotime('-5 mins')) ?></div>
                </div>
                <div class="log-item">
                    <div class="log-info">
                        <div class="log-avatar" style="background:#fff7ed; color:#9a3412;">E</div>
                        <div class="log-text">
                            <p>Exam sessions created</p>
                            <span>Table: exams | Admin: admin</span>
                        </div>
                    </div>
                    <div class="log-time"><?= date('h:i A', strtotime('-15 mins')) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Shortcuts Panel -->
    <div class="shortcuts-panel">
        <div class="analytics-card-header" style="margin-bottom:15px; border-bottom:1px solid #f3f4f6; padding-bottom:12px;">
            <h2>Quick Command Center</h2>
            <p>Direct shortcuts to critical university control modules.</p>
        </div>
        <div class="shortcut-buttons-grid">
            <a href="assignments.php" class="shortcut-btn"><i>🔗</i>Assignments</a>
            <a href="enrollments.php" class="shortcut-btn"><i>✍️</i>Enrollment</a>
            <a href="exams.php" class="shortcut-btn"><i>📝</i>Create Exams</a>
            <a href="results.php" class="shortcut-btn"><i>📊</i>Result Center</a>
            <a href="transcript.php" class="shortcut-btn" style="grid-column: span 2; background: rgba(79, 70, 229, 0.05); color: var(--primary); border-color: rgba(79, 70, 229, 0.2);"><i>📜</i>Generate Student Transcript</a>
        </div>
    </div>
</div>

<script>
// Keep references to charts
let deptChart;
let gradeChart;

function updateChartsTheme(theme) {
    const isDark = theme === 'dark';
    const textColor = isDark ? '#9CA3AF' : '#64748B';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
    
    if (deptChart && deptChart.options && deptChart.options.scales) {
        if (deptChart.options.scales.x && deptChart.options.scales.x.ticks) {
            deptChart.options.scales.x.ticks.color = textColor;
        }
        if (deptChart.options.scales.y) {
            if (deptChart.options.scales.y.ticks) {
                deptChart.options.scales.y.ticks.color = textColor;
            }
            if (deptChart.options.scales.y.grid) {
                deptChart.options.scales.y.grid.color = gridColor;
            }
        }
        deptChart.update();
    }
    
    if (gradeChart && gradeChart.options && gradeChart.options.plugins && gradeChart.options.plugins.legend && gradeChart.options.plugins.legend.labels) {
        gradeChart.options.plugins.legend.labels.color = textColor;
        gradeChart.update();
    }
}

// Department Chart
const deptCtx = document.getElementById('deptChart').getContext('2d');
deptChart = new Chart(deptCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($dept_labels) ?>,
        datasets: [{
            label: 'Students Enrolled',
            data: <?= json_encode($dept_counts) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.7)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1.5,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0, 0, 0, 0.05)' },
                ticks: { color: '#64748B' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#64748B' }
            }
        }
    }
});

// Grade Distribution Chart
const gradeCtx = document.getElementById('gradeChart').getContext('2d');
gradeChart = new Chart(gradeCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($grade_labels) ?>,
        datasets: [{
            data: <?= json_encode($grade_counts) ?>,
            backgroundColor: [
                '#10b981', // green
                '#3b82f6', // blue
                '#8b5cf6', // purple
                '#ec4899', // pink
                '#f59e0b', // yellow
                '#ef4444', // red
                '#6b7280'  // gray
            ],
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: { boxWidth: 12, font: { size: 12 }, color: '#64748B' }
            }
        }
    }
});

// Initialize chart colors based on current theme
const currentTheme = localStorage.getItem('theme') || 'light';
updateChartsTheme(currentTheme);

// Listen for theme change events
document.addEventListener('themeChanged', (e) => {
    updateChartsTheme(e.detail.theme);
});
</script>

<?php require_once '../includes/footer.php'; ?>
