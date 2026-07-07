<?php
// teacher/dashboard.php
require_once '../includes/auth.php';
checkRole('teacher');

// Get teacher ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT t.teacher_id, u.full_name as name 
    FROM teachers t 
    JOIN users u ON t.user_id = u.user_id 
    WHERE t.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['teacher_id'];

// Get Stats
$assigned_courses_count = $conn->query("SELECT COUNT(*) FROM course_assignments WHERE teacher_id = $teacher_id")->fetch_row()[0];
$marks_entered_count = $conn->query("
    SELECT COUNT(DISTINCT m.mark_id) 
    FROM marks m 
    JOIN course_assignments ca ON m.course_id = ca.course_id 
    WHERE ca.teacher_id = $teacher_id AND m.teacher_id = $teacher_id
")->fetch_row()[0];

// Query course averages for chart
$avg_marks_query = $conn->query("
    SELECT c.course_code, AVG(m.total_marks) as average_score
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.course_id
    JOIN marks m ON c.course_id = m.course_id
    WHERE ca.teacher_id = $teacher_id AND m.total_marks IS NOT NULL AND m.is_ufm = 0
    GROUP BY c.course_id
");

$course_labels = [];
$course_averages = [];
if ($avg_marks_query) {
    while($row = $avg_marks_query->fetch_assoc()) {
        $course_labels[] = $row['course_code'];
        $course_averages[] = round((float)$row['average_score'], 1);
    }
}

// Query assigned courses list
$courses_list = $conn->query("
    SELECT c.course_code, c.course_name, ca.semester, ca.batch_year
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.course_id
    WHERE ca.teacher_id = $teacher_id
    ORDER BY ca.semester, c.course_code
");

require_once '../includes/header.php';
?>

<!-- Include Chart.js via CDN -->
<script src="../assets/js/chart.js"></script>

<style>
.teacher-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 24px;
    margin-top: 25px;
}
@media (max-width: 992px) {
    .teacher-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h1>Welcome, <?= htmlspecialchars($teacher['name']) ?></h1>
</div>

<div class="card-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary-light">📚</div>
        <div class="stat-info">
            <h3><?= $assigned_courses_count ?></h3>
            <p>Assigned Courses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-light">✔️</div>
        <div class="stat-info">
            <h3><?= $marks_entered_count ?></h3>
            <p>Marks Revisions Entered</p>
        </div>
    </div>
</div>

<div class="teacher-grid">
    <!-- Assigned Courses Card -->
    <div class="table-container" style="margin-top: 0;">
        <div class="table-header">
            <h2>My Teaching Schedule</h2>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Title</th>
                        <th>Semester</th>
                        <th>Batch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses_list && $courses_list->num_rows > 0): ?>
                        <?php while($crs = $courses_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($crs['course_code']) ?></strong></td>
                                <td><?= htmlspecialchars($crs['course_name']) ?></td>
                                <td>Semester <?= htmlspecialchars($crs['semester']) ?></td>
                                <td><?= htmlspecialchars($crs['batch_year']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--gray);">No course assignments scheduled.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Class Averages Analytics Card -->
    <div class="table-container" style="margin-top: 0;">
        <div class="table-header">
            <h2>Class Averages Analytics</h2>
        </div>
        <div style="padding: 20px;">
            <?php if (!empty($course_averages)): ?>
                <div style="position: relative; height: 230px;">
                    <canvas id="classAveragesChart"></canvas>
                </div>
            <?php else: ?>
                <div style="height: 230px; display:flex; align-items:center; justify-content:center; color:var(--gray); font-style: italic; text-align:center;">
                    No student grades entered yet to generate performance averages.
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
        <a href="courses.php" class="btn">View My Courses</a>
        <a href="marks_entry.php" class="btn bg-success-light" style="background:#10B981;">Enter Marks</a>
    </div>
</div>

<script>
let averagesChart;

function updateAveragesChartTheme(theme) {
    const isDark = theme === 'dark';
    const textColor = isDark ? '#9CA3AF' : '#64748B';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
    
    if (averagesChart && averagesChart.options && averagesChart.options.scales) {
        if (averagesChart.options.scales.x && averagesChart.options.scales.x.ticks) {
            averagesChart.options.scales.x.ticks.color = textColor;
        }
        if (averagesChart.options.scales.y) {
            if (averagesChart.options.scales.y.ticks) {
                averagesChart.options.scales.y.ticks.color = textColor;
            }
            if (averagesChart.options.scales.y.grid) {
                averagesChart.options.scales.y.grid.color = gridColor;
            }
        }
        averagesChart.update();
    }
}

<?php if (!empty($course_averages)): ?>
const averagesCtx = document.getElementById('classAveragesChart').getContext('2d');
averagesChart = new Chart(averagesCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($course_labels) ?>,
        datasets: [{
            label: 'Class Average',
            data: <?= json_encode($course_averages) ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1.5,
            borderRadius: 6
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
                max: 100,
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

// Initialize chart colors based on current theme
const currentTheme = localStorage.getItem('theme') || 'light';
updateAveragesChartTheme(currentTheme);

// Listen for theme change events
document.addEventListener('themeChanged', (e) => {
    updateAveragesChartTheme(e.detail.theme);
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
