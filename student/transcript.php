<?php
// student/transcript.php
require_once '../includes/auth.php';
checkRole('student');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['student_id'];

// Get all marks for published exams
$query = "
    SELECT m.exam_id, e.exam_name, c.course_code, c.course_name, c.credits, c.semester, m.total_marks, m.grade, m.grade_point, m.is_ufm
    FROM marks m
    JOIN exams e ON m.exam_id = e.exam_id
    JOIN courses c ON m.course_id = c.course_id
    WHERE m.student_id = ? AND e.is_published = TRUE
    ORDER BY c.semester, c.course_code
";
$stmt2 = $conn->prepare($query);
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$marks_res = $stmt2->get_result();

$semesters_data = [];
$total_credits_cumulative = 0;
$total_points_cumulative = 0;

while($row = $marks_res->fetch_assoc()) {
    $sem = $row['semester'];
    if(!isset($semesters_data[$sem])) {
        $semesters_data[$sem] = [
            'courses' => [],
            'semester_credits' => 0,
            'semester_points' => 0
        ];
    }
    
    $semesters_data[$sem]['courses'][] = $row;
    
    // Add to semester calculations
    $semesters_data[$sem]['semester_credits'] += $row['credits'];
    if($row['grade_point'] !== null) {
        $semesters_data[$sem]['semester_points'] += ($row['credits'] * $row['grade_point']);
    }
    
    // Add to cumulative calculations
    $total_credits_cumulative += $row['credits'];
    if($row['grade_point'] !== null) {
        $total_points_cumulative += ($row['credits'] * $row['grade_point']);
    }
}

require_once '../includes/header.php';
?>

<style>
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; border:none; box-shadow:none; padding: 20px; }
    .no-print { display: none !important; }
    .sidebar, .topbar { display: none !important; }
    .watermark { display: block !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

.transcript-container {
    background: white;
    padding: 40px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    font-family: 'Times New Roman', Times, serif;
    color: #111;
    position: relative;
    border: 1px solid var(--gray-light);
    overflow: hidden;
}

.watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-40deg);
    font-size: 5.5rem;
    color: rgba(0, 0, 0, 0.035);
    font-weight: 900;
    pointer-events: none;
    user-select: none;
    white-space: nowrap;
    z-index: 1;
    text-transform: uppercase;
    letter-spacing: 5px;
}

.transcript-header {
    text-align: center;
    border-bottom: 3px double #111;
    padding-bottom: 20px;
    margin-bottom: 30px;
    position: relative;
    z-index: 2;
}

.transcript-header h1 {
    font-size: 32px;
    text-transform: uppercase;
    margin: 0;
    letter-spacing: 1px;
}

.transcript-header p {
    margin: 5px 0 0;
    font-size: 16px;
    font-style: italic;
    color: #444;
}

.student-meta-box {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
    font-size: 16px;
    position: relative;
    z-index: 2;
    background: rgba(249, 250, 251, 0.7);
    padding: 15px;
    border-radius: var(--radius);
    border: 1px solid var(--gray-light);
}

.student-meta-box p {
    margin: 6px 0;
}

.semester-section {
    margin-bottom: 35px;
    position: relative;
    z-index: 2;
}

.semester-title {
    font-size: 18px;
    font-weight: bold;
    border-bottom: 2px solid #111;
    padding-bottom: 5px;
    margin-bottom: 12px;
    text-transform: uppercase;
}

.transcript-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.transcript-table th, .transcript-table td {
    border: 1px solid #222;
    padding: 8px 12px;
    text-align: left;
    font-size: 15px;
}

.transcript-table th {
    background: #f3f4f6;
    font-weight: bold;
}

.sem-summary-row {
    display: flex;
    justify-content: flex-end;
    font-size: 15px;
    font-weight: bold;
    padding: 5px 12px 15px;
    gap: 20px;
}

.cumulative-summary-box {
    border-top: 3px double #111;
    padding-top: 20px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 18px;
    font-weight: bold;
    position: relative;
    z-index: 2;
}

.transcript-footer-sig {
    margin-top: 60px;
    display: flex;
    justify-content: space-between;
    position: relative;
    z-index: 2;
}

.transcript-footer-sig .sig {
    border-top: 1px solid #111;
    padding-top: 10px;
    width: 200px;
    text-align: center;
    font-size: 14px;
}
</style>

<div class="page-header no-print">
    <h1>Academic Transcript</h1>
</div>

<?php if(empty($semesters_data)): ?>
    <div class="alert alert-warning">No published results found. A transcript can only be generated once results have been officially published by the Controller of Examinations.</div>
<?php else: ?>

<div style="text-align: right; margin-bottom: 20px;" class="no-print">
    <button onclick="window.print()" class="btn" style="background:var(--primary);"><span style="margin-right:8px;">🖨️</span> Print Transcript</button>
</div>

<div class="print-area transcript-container">
    <!-- Background Watermark -->
    <div class="watermark">OFFICIAL TRANSCRIPT</div>
    
    <div class="transcript-header">
        <h1>University Examination System</h1>
        <p>Official Academic Transcript</p>
    </div>
    
    <div class="student-meta-box">
        <div>
            <p><strong>Student Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
            <p><strong>Roll Number:</strong> <?= htmlspecialchars($student['roll_number']) ?></p>
            <p><strong>Program:</strong> <?= htmlspecialchars($student['program']) ?></p>
        </div>
        <div>
            <p><strong>Department:</strong> <?= htmlspecialchars($student['department']) ?></p>
            <p><strong>Batch Year:</strong> <?= htmlspecialchars($student['batch_year']) ?></p>
            <p><strong>Date Generated:</strong> <?= date('F d, Y') ?></p>
        </div>
    </div>
    
    <?php ksort($semesters_data); foreach($semesters_data as $sem => $data): ?>
        <div class="semester-section">
            <div class="semester-title">Semester <?= $sem ?></div>
            <table class="transcript-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Course Code</th>
                        <th style="width: 50%;">Course Name</th>
                        <th style="width: 10%; text-align: center;">Credits</th>
                        <th style="width: 15%; text-align: center;">Total Marks</th>
                        <th style="width: 10%; text-align: center;">Grade</th>
                        <th style="width: 10%; text-align: center;">Grade Point</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['courses'] as $crs): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($crs['course_code']) ?></strong></td>
                        <td><?= htmlspecialchars($crs['course_name']) ?></td>
                        <td style="text-align: center;"><?= $crs['credits'] ?></td>
                        <td style="text-align: center;"><?= $crs['is_ufm'] ? 'UFM' : ($crs['total_marks'] !== null ? htmlspecialchars($crs['total_marks']) : '-') ?></td>
                        <td style="text-align: center;">
                            <strong><?= htmlspecialchars($crs['grade']) ?></strong>
                        </td>
                        <td style="text-align: center;"><?= htmlspecialchars($crs['grade_point']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php 
            $sgpa = 0.00;
            if($data['semester_credits'] > 0) {
                $sgpa = number_format($data['semester_points'] / $data['semester_credits'], 2);
            }
            ?>
            <div class="sem-summary-row">
                <span>Semester Credits: <?= $data['semester_credits'] ?></span>
                <span>SGPA: <?= $sgpa ?></span>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php 
    $cgpa = 0.00;
    if($total_credits_cumulative > 0) {
        $cgpa = number_format($total_points_cumulative / $total_credits_cumulative, 2);
    }
    ?>
    <div class="cumulative-summary-box">
        <div>Total Cumulative Credits: <?= $total_credits_cumulative ?></div>
        <div>Cumulative CGPA: <span style="font-size: 22px; font-weight: 900; border-bottom: 2px solid #111; padding-bottom: 2px;"><?= $cgpa ?></span></div>
    </div>
    
    <div class="transcript-footer-sig">
        <div class="sig">Prepared By</div>
        <div class="sig">Checked By</div>
        <div class="sig" style="font-weight: bold;">Controller of Examinations</div>
    </div>
</div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
