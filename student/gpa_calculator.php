<?php
// student/gpa_calculator.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole('student');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT s.*, u.full_name as name 
    FROM students s 
    JOIN users u ON s.user_id = u.user_id 
    WHERE s.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['student_id'];

// Get actual published courses and marks
$actual_courses = [];
$actual_query = $conn->prepare("
    SELECT c.course_code, c.course_name, c.credits, m.grade, m.grade_point, m.is_ufm, e.exam_name, c.semester
    FROM marks m
    JOIN exams e ON m.exam_id = e.exam_id
    JOIN courses c ON m.course_id = c.course_id
    WHERE m.student_id = ? AND e.is_published = TRUE
    ORDER BY c.semester, c.course_code
");
$actual_query->bind_param("i", $student_id);
$actual_query->execute();
$actual_res = $actual_query->get_result();
while ($row = $actual_res->fetch_assoc()) {
    $actual_courses[] = $row;
}

// Fetch database grading scale for JS
$scale_query = $conn->query("SELECT grade, grade_point FROM grading_scale ORDER BY min_percentage DESC");
$js_scale = [];
if ($scale_query && $scale_query->num_rows > 0) {
    while($row = $scale_query->fetch_assoc()) {
        $js_scale[$row['grade']] = (float)$row['grade_point'];
    }
} else {
    // Fallback scale
    $js_scale = [
        'A+' => 4.00, 'A' => 3.67, 'A-' => 3.33,
        'B+' => 3.00, 'B' => 2.67, 'B-' => 2.33,
        'C+' => 2.00, 'C' => 1.67, 'C-' => 1.33,
        'F' => 0.00, 'UFM' => 0.00
    ];
}

require_once '../includes/header.php';
?>

<!-- Include Chart.js via CDN -->
<script src="../assets/js/chart.js"></script>

<style>
.calculator-grid {
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 992px) {
    .calculator-grid {
        grid-template-columns: 1fr;
    }
}
.summary-panel {
    background: var(--white);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-light);
    position: sticky;
    top: 90px;
}
.projected-card {
    text-align: center;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
}
.projected-card h3 {
    font-size: 36px;
    font-weight: 800;
    margin: 5px 0;
}
.projected-card p {
    font-size: 13px;
    opacity: 0.9;
    font-weight: 500;
}
.metric-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
    color: var(--dark);
}
.metric-row span:last-child {
    color: var(--primary);
}
.btn-secondary {
    background: #10B981;
}
.btn-secondary:hover {
    background: #059669;
}
</style>

<div class="calculator-grid">
    <!-- Left Pane: Courses Input & List -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Completed Courses -->
        <div class="table-container">
            <div class="table-header" style="justify-content: space-between;">
                <h2>Completed Academic Courses</h2>
                <span style="font-size: 12px; font-weight: 600; background: #EEF2FF; color: #4F46E5; padding: 4px 8px; border-radius: 20px;">Actual History</span>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Inc</th>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th style="text-align: center;">Credits</th>
                            <th style="text-align: center;">Grade</th>
                            <th style="text-align: center;">Points</th>
                        </tr>
                    </thead>
                    <tbody id="actual-courses-tbody">
                        <?php if (empty($actual_courses)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--gray); font-style: italic; padding: 20px;">No published results found in your records.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($actual_courses as $crs): ?>
                                <tr class="course-row actual-row" data-credits="<?= $crs['credits'] ?>" data-point="<?= $crs['grade_point'] !== null ? $crs['grade_point'] : 0.00 ?>" data-grade="<?= htmlspecialchars($crs['grade']) ?>">
                                    <td style="text-align: center;">
                                        <input type="checkbox" checked onchange="recalculateGPA()" class="row-checkbox">
                                    </td>
                                    <td><strong><?= htmlspecialchars($crs['course_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($crs['course_name']) ?> <span style="font-size: 11px; color: var(--gray);">(<?= htmlspecialchars($crs['exam_name']) ?>)</span></td>
                                    <td style="text-align: center;"><?= $crs['credits'] ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-success" style="font-size: 12px;"><?= htmlspecialchars($crs['grade']) ?></span>
                                    </td>
                                    <td style="text-align: center; font-weight: 600; color: var(--dark);"><?= number_format($crs['grade_point'] !== null ? $crs['grade_point'] : 0.00, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Simulated Courses -->
        <div class="table-container">
            <div class="table-header" style="justify-content: space-between; align-items: center;">
                <h2>Simulate Future/Hypothetical Courses</h2>
                <button type="button" class="btn" style="padding: 8px 16px; font-size: 13px;" onclick="addSimulatedRow()">➕ Add Course</button>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Inc</th>
                            <th style="width: 150px;">Course Code</th>
                            <th>Course Title</th>
                            <th style="width: 100px; text-align: center;">Credits</th>
                            <th style="width: 130px; text-align: center;">Target Grade</th>
                            <th style="width: 80px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="simulated-courses-tbody">
                        <!-- Simulated courses rows will be inserted here dynamically -->
                        <tr id="no-sim-row">
                            <td colspan="6" style="text-align: center; color: var(--gray); font-style: italic; padding: 20px;">No simulated courses added. Click "Add Course" above.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Right Pane: Summary Cards & Chart -->
    <div class="summary-panel">
        <div class="projected-card">
            <p>PROJECTED CUMULATIVE GPA</p>
            <h3 id="projected-cgpa">0.00</h3>
            <p id="simulation-status">Based entirely on actual records</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px;">
            <div class="metric-row">
                <span>Total Accumulated Credits:</span>
                <span id="projected-credits" style="color: var(--dark);">0</span>
            </div>
            <div class="metric-row">
                <span>Total Simulated Credits:</span>
                <span id="simulated-credits">0</span>
            </div>
            <div class="metric-row" style="border-top: 1px solid var(--gray-light); padding-top: 10px; margin-top: 5px;">
                <span>Total Combined Credits:</span>
                <span id="combined-credits" style="color: var(--primary); font-weight: 700;">0</span>
            </div>
        </div>

        <div style="border-top: 1px solid var(--gray-light); padding-top: 20px;">
            <h4 style="font-size: 14px; font-weight: 700; color: var(--dark); margin-bottom: 15px; text-align: center;">Projected Grade Distribution</h4>
            <div style="position: relative; height: 200px; display: flex; justify-content: center; align-items: center;">
                <canvas id="gradeDistributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const scale = <?= json_encode($js_scale) ?>;
let simulatedRowIndex = 0;
let gradeChart = null;

function addSimulatedRow() {
    const noSimRow = document.getElementById('no-sim-row');
    if (noSimRow) {
        noSimRow.style.display = 'none';
    }

    simulatedRowIndex++;
    const tbody = document.getElementById('simulated-courses-tbody');
    const tr = document.createElement('tr');
    tr.className = 'course-row simulated-row';
    tr.id = `sim-row-${simulatedRowIndex}`;
    
    // Build grade options dropdown
    let gradeOptions = '';
    for (let grade in scale) {
        gradeOptions += `<option value="${grade}">${grade} (${scale[grade].toFixed(2)})</option>`;
    }

    tr.innerHTML = `
        <td style="text-align: center;">
            <input type="checkbox" checked onchange="recalculateGPA()" class="row-checkbox">
        </td>
        <td>
            <input type="text" class="form-control" placeholder="e.g. BBA302" style="padding: 6px 12px; font-size:13px; font-weight:600;" value="SIM-${simulatedRowIndex}">
        </td>
        <td>
            <input type="text" class="form-control" placeholder="Hypothetical Course Title" style="padding: 6px 12px; font-size:13px;" value="Hypothetical Course ${simulatedRowIndex}">
        </td>
        <td>
            <select class="form-control sim-credits" onchange="recalculateGPA()" style="padding: 6px 12px; font-size:13px; text-align: center;">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3" selected>3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
            </select>
        </td>
        <td>
            <select class="form-control sim-grade" onchange="recalculateGPA()" style="padding: 6px 12px; font-size:13px; font-weight: 700; text-align: center;">
                ${gradeOptions}
            </select>
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn btn-danger" style="padding: 6px 12px; font-size:12px; background:var(--danger);" onclick="deleteSimulatedRow(${simulatedRowIndex})">🗑️</button>
        </td>
    `;
    
    tbody.appendChild(tr);
    recalculateGPA();
}

function deleteSimulatedRow(index) {
    const row = document.getElementById(`sim-row-${index}`);
    if (row) {
        row.remove();
    }
    
    const tbody = document.getElementById('simulated-courses-tbody');
    const simRows = tbody.querySelectorAll('.simulated-row');
    if (simRows.length === 0) {
        const noSimRow = document.getElementById('no-sim-row');
        if (noSimRow) {
            noSimRow.style.display = 'table-row';
        }
    }
    
    recalculateGPA();
}

function recalculateGPA() {
    let totalActualCredits = 0;
    let totalActualPoints = 0;
    
    let totalSimCredits = 0;
    let totalSimPoints = 0;
    
    let gradeCounts = {};
    for (let grade in scale) {
        gradeCounts[grade] = 0;
    }

    // Process actual completed courses
    const actualRows = document.querySelectorAll('.actual-row');
    actualRows.forEach(row => {
        const checkbox = row.querySelector('.row-checkbox');
        const credits = parseFloat(row.getAttribute('data-credits'));
        const point = parseFloat(row.getAttribute('data-point'));
        const grade = row.getAttribute('data-grade');
        
        if (checkbox && checkbox.checked) {
            totalActualCredits += credits;
            totalActualPoints += (credits * point);
            
            if (gradeCounts.hasOwnProperty(grade)) {
                gradeCounts[grade]++;
            }
        }
    });

    // Process simulated courses
    const simRows = document.querySelectorAll('.simulated-row');
    simRows.forEach(row => {
        const checkbox = row.querySelector('.row-checkbox');
        const credits = parseFloat(row.querySelector('.sim-credits').value);
        const grade = row.querySelector('.sim-grade').value;
        const point = scale[grade] !== undefined ? scale[grade] : 0.00;
        
        if (checkbox && checkbox.checked) {
            totalSimCredits += credits;
            totalSimPoints += (credits * point);
            
            if (gradeCounts.hasOwnProperty(grade)) {
                gradeCounts[grade]++;
            }
        }
    });

    // Combined metrics
    const totalCombinedCredits = totalActualCredits + totalSimCredits;
    const totalCombinedPoints = totalActualPoints + totalSimPoints;
    const combinedCGPA = totalCombinedCredits > 0 ? (totalCombinedPoints / totalCombinedCredits) : 0.00;

    // Update UI elements
    document.getElementById('projected-cgpa').innerText = combinedCGPA.toFixed(2);
    document.getElementById('projected-credits').innerText = totalActualCredits;
    document.getElementById('simulated-credits').innerText = totalSimCredits;
    document.getElementById('combined-credits').innerText = totalCombinedCredits;

    // Update simulation status label
    const statusLabel = document.getElementById('simulation-status');
    if (totalSimCredits > 0) {
        statusLabel.innerText = `Simulating ${simRows.length} hypothetical course(s)`;
    } else {
        statusLabel.innerText = "Based entirely on actual records";
    }

    // Update visual chart
    updateGradeChart(gradeCounts);
}

function updateGradeChart(gradeCounts) {
    const labels = Object.keys(gradeCounts).filter(grade => gradeCounts[grade] > 0);
    const data = labels.map(grade => gradeCounts[grade]);
    
    // Standard color palette mapping for grades
    const bgColors = {
        'A+': '#10b981', 'A': '#3b82f6', 'A-': '#60a5fa',
        'B+': '#8b5cf6', 'B': '#a78bfa', 'B-': '#c084fc',
        'C+': '#fbbf24', 'C': '#f59e0b', 'C-': '#d97706',
        'F': '#ef4444', 'UFM': '#b91c1c'
    };
    
    const colors = labels.map(grade => bgColors[grade] || '#6b7280');

    if (gradeChart !== null) {
        gradeChart.data.labels = labels;
        gradeChart.data.datasets[0].data = data;
        gradeChart.data.datasets[0].backgroundColor = colors;
        gradeChart.update();
    } else {
        const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
        gradeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: getComputedStyle(document.body).getPropertyValue('--white') || '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 10,
                            font: { size: 11 },
                            color: '#64748B'
                        }
                    }
                }
            }
        });
    }
}

function updateChartThemes(theme) {
    const isDark = theme === 'dark';
    const textColor = isDark ? '#9CA3AF' : '#64748B';
    
    if (gradeChart) {
        gradeChart.options.plugins.legend.labels.color = textColor;
        gradeChart.options.datasets[0].borderColor = isDark ? '#111827' : '#ffffff';
        gradeChart.update();
    }
}

// Initial calculation
recalculateGPA();

// Sync chart colors on theme toggle
document.addEventListener('themeChanged', (e) => {
    updateChartThemes(e.detail.theme);
    // Force recalculation to capture border updates
    recalculateGPA();
});
</script>

<?php require_once '../includes/footer.php'; ?>
