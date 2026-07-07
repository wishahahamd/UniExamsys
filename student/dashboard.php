<?php
// student/dashboard.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole('student');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT s.*, u.full_name as name, u.email as user_email 
    FROM students s 
    JOIN users u ON s.user_id = u.user_id 
    WHERE s.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['student_id'];

$academic_summary = getStudentAcademicSummary($student_id, $conn);

// Get Stats
$enrolled_courses = $conn->query("SELECT COUNT(*) FROM enrollments WHERE student_id = $student_id")->fetch_row()[0];
$published_results = $conn->query("
    SELECT COUNT(DISTINCT m.exam_id) 
    FROM marks m 
    JOIN exams e ON m.exam_id = e.exam_id 
    WHERE m.student_id = $student_id AND e.is_published = TRUE
")->fetch_row()[0];

// Query SGPA progression data
$chart_data_query = $conn->query("
    SELECT e.exam_name, c.semester, c.credits, m.grade_point
    FROM marks m
    JOIN exams e ON m.exam_id = e.exam_id
    JOIN courses c ON m.course_id = c.course_id
    WHERE m.student_id = $student_id AND e.is_published = TRUE
    ORDER BY c.semester ASC
");

$semester_gpas = [];
if ($chart_data_query) {
    while ($row = $chart_data_query->fetch_assoc()) {
        $sem = 'Sem ' . $row['semester'];
        if (!isset($semester_gpas[$sem])) {
            $semester_gpas[$sem] = [
                'credits' => 0,
                'points' => 0
            ];
        }
        $semester_gpas[$sem]['credits'] += $row['credits'];
        if ($row['grade_point'] !== null) {
            $semester_gpas[$sem]['points'] += ($row['credits'] * $row['grade_point']);
        }
    }
}

$chart_labels = [];
$chart_gpas = [];
foreach ($semester_gpas as $sem => $data) {
    $chart_labels[] = $sem;
    $chart_gpas[] = $data['credits'] > 0 ? round($data['points'] / $data['credits'], 2) : 0.00;
}

require_once '../includes/header.php';
?>

<!-- Include Chart.js via CDN -->
<script src="../assets/js/chart.js"></script>

<style>
.student-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 25px;
}
@media (max-width: 992px) {
    .student-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h1>Welcome, <?= htmlspecialchars($student['name']) ?></h1>
</div>

<div class="card-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary-light">📋</div>
        <div class="stat-info">
            <h3><?= $enrolled_courses ?></h3>
            <p>Enrolled Courses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-light">🎓</div>
        <div class="stat-info">
            <h3><?= $published_results ?></h3>
            <p>Published Results</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple-light">📜</div>
        <div class="stat-info">
            <h3><?= $academic_summary['cgpa'] ?></h3>
            <p>Current CGPA</p>
        </div>
    </div>
</div>

<div class="student-grid">
    <!-- Profile Card -->
    <div class="table-container" style="margin-top: 0;">
        <div class="table-header">
            <h2>Student Profile Details</h2>
        </div>
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr; gap: 12px; background: var(--light); padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray-light);">
                <div><strong>Roll Number:</strong> <span style="float:right; color:var(--dark); font-weight:600;"><?= htmlspecialchars($student['roll_number']) ?></span></div>
                <div><strong>Program:</strong> <span style="float:right; color:var(--dark); font-weight:600;"><?= htmlspecialchars($student['program'] ?? 'N/A') ?></span></div>
                <div><strong>Department:</strong> <span style="float:right; color:var(--dark); font-weight:600;"><?= htmlspecialchars($student['department'] ?? 'N/A') ?></span></div>
                <div><strong>Current Semester:</strong> <span style="float:right; color:var(--dark); font-weight:600;"><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></span></div>
                <div><strong>Batch Year:</strong> <span style="float:right; color:var(--dark); font-weight:600;"><?= htmlspecialchars($student['batch_year'] ?? 'N/A') ?></span></div>
                <div><strong>Email:</strong> <span style="float:right; color:var(--dark); font-weight:600;"><?= htmlspecialchars($student['user_email']) ?></span></div>
            </div>
        </div>
    </div>

    <!-- CGPA Progression Chart Card -->
    <div class="table-container" style="margin-top: 0;">
        <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Academic Performance Curve</h2>
            <?php if (empty($chart_gpas)): ?>
                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706; font-size: 11px; padding: 4px 8px; border-radius: 12px;">Preview Mode</span>
            <?php endif; ?>
        </div>
        <div style="padding: 20px; position: relative;">
            <div style="position: relative; height: 215px;">
                <canvas id="studentCgpaChart"></canvas>
            </div>
            <?php if (empty($chart_gpas)): ?>
                <div class="no-data-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(1px); pointer-events: none;">
                    <span style="background: var(--white); padding: 8px 16px; border-radius: 20px; box-shadow: var(--shadow); font-size: 13px; font-weight: 600; color: var(--gray); border: 1px solid var(--gray-light);">No published results yet</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="table-container" style="margin-top: 25px;">
    <div class="table-header">
        <h2>Quick Actions</h2>
    </div>
    <div style="padding: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
        <a href="results.php" class="btn bg-success-light" style="background:#10B981;">View Results</a>
        <a href="hall_ticket.php" class="btn">Download Hall Ticket</a>
        <a href="transcript.php" class="btn" style="background:var(--primary-hover);">View Transcript</a>
    </div>
</div>

<script>
let cgpaChart;

function updateCgpaChartTheme(theme) {
    const isDark = theme === 'dark';
    const textColor = isDark ? '#9CA3AF' : '#64748B';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
    
    if (cgpaChart && cgpaChart.options && cgpaChart.options.scales) {
        if (cgpaChart.options.scales.x && cgpaChart.options.scales.x.ticks) {
            cgpaChart.options.scales.x.ticks.color = textColor;
        }
        if (cgpaChart.options.scales.y) {
            if (cgpaChart.options.scales.y.ticks) {
                cgpaChart.options.scales.y.ticks.color = textColor;
            }
            if (cgpaChart.options.scales.y.grid) {
                cgpaChart.options.scales.y.grid.color = gridColor;
            }
        }
        cgpaChart.update();
    }
}

<?php 
$js_labels = !empty($chart_labels) ? $chart_labels : ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'];
$js_data = !empty($chart_gpas) ? $chart_gpas : [3.40, 3.65, 3.58, 3.80];
$is_preview = empty($chart_gpas);
?>
const cgpaCtx = document.getElementById('studentCgpaChart').getContext('2d');
cgpaChart = new Chart(cgpaCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($js_labels) ?>,
        datasets: [{
            label: '<?= $is_preview ? "Sample SGPA (No Data)" : "SGPA" ?>',
            data: <?= json_encode($js_data) ?>,
            borderColor: '<?= $is_preview ? "rgba(99, 102, 241, 0.4)" : "#6366F1" ?>',
            backgroundColor: '<?= $is_preview ? "rgba(99, 102, 241, 0.03)" : "rgba(99, 102, 241, 0.1)" ?>',
            borderDash: <?= $is_preview ? "[5, 5]" : "[]" ?>,
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            pointBackgroundColor: '<?= $is_preview ? "rgba(99, 102, 241, 0.4)" : "#6366F1" ?>',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
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
                min: 0,
                max: 4,
                grid: { color: 'rgba(0, 0, 0, 0.05)' },
                ticks: { color: '#64748B', stepSize: 1.0 }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#64748B' }
            }
        }
    }
});

// Initialize chart colors based on current theme
const currentTheme = localStorage.getItem('theme') || 'light';
updateCgpaChartTheme(currentTheme);

// Listen for theme change events
document.addEventListener('themeChanged', (e) => {
    updateCgpaChartTheme(e.detail.theme);
});
</script>

<?php require_once '../includes/footer.php'; ?>
