<?php
// admin/gazette.php
require_once '../includes/auth.php';
checkRole('admin');

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if($exam_id <= 0) {
    die("Invalid Exam Session.");
}

// Fetch exam details
$exam_res = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id");
if(!$exam_res || $exam_res->num_rows == 0) {
    die("Exam session not found.");
}
$exam = $exam_res->fetch_assoc();

// Fetch courses for this exam's semester
$courses_res = $conn->query("
    SELECT course_id, course_code, course_name, credits 
    FROM courses 
    WHERE semester = {$exam['semester']} 
    ORDER BY course_code
");
$courses = [];
while($c = $courses_res->fetch_assoc()) {
    $courses[$c['course_id']] = $c;
}

// Fetch enrolled students for this semester and batch
$students_res = $conn->query("
    SELECT s.student_id, s.roll_number, u.full_name as name 
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN enrollments e ON s.student_id = e.student_id
    WHERE e.semester = {$exam['semester']} AND e.batch_year = '{$exam['batch_year']}'
    GROUP BY s.student_id
    ORDER BY s.roll_number
");

$gazette_data = [];
while($stu = $students_res->fetch_assoc()) {
    $student_id = $stu['student_id'];
    
    // Fetch marks for this student and exam
    $marks_res = $conn->query("
        SELECT course_id, internal_marks, external_marks, total_marks, grade, grade_point, is_ufm
        FROM marks
        WHERE student_id = $student_id AND exam_id = $exam_id
    ");
    
    $student_marks = [];
    $has_ufm = false;
    $has_fail = false;
    $total_credits = 0;
    $total_points = 0;
    $marks_entered_count = 0;
    
    while($m = $marks_res->fetch_assoc()) {
        $student_marks[$m['course_id']] = $m;
        if($m['is_ufm']) {
            $has_ufm = true;
        }
        if($m['grade'] === 'F') {
            $has_fail = true;
        }
        
        $course_id = $m['course_id'];
        if(isset($courses[$course_id])) {
            $credits = $courses[$course_id]['credits'];
            $total_credits += $credits;
            $total_points += ($credits * $m['grade_point']);
            $marks_entered_count++;
        }
    }
    
    // Determine status
    $status = 'PASS';
    if($has_ufm) {
        $status = 'UFM';
    } elseif($has_fail) {
        $status = 'FAIL / REAPPEAR';
    } elseif($marks_entered_count == 0) {
        $status = 'ABSENT / NO MARKS';
    }
    
    $sgpa = 0.00;
    if($total_credits > 0) {
        $sgpa = number_format($total_points / $total_credits, 2);
    }
    
    $gazette_data[] = [
        'roll_number' => $stu['roll_number'],
        'name' => $stu['name'],
        'marks' => $student_marks,
        'status' => $status,
        'sgpa' => $sgpa,
        'total_credits' => $total_credits
    ];
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
}

.gazette-container {
    background: white;
    padding: 30px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    font-family: 'Courier New', Courier, monospace;
    color: #000;
    border: 1px solid var(--gray-light);
}

.gazette-header {
    text-align: center;
    border-bottom: 2px dashed #000;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.gazette-header h1 {
    font-size: 26px;
    text-transform: uppercase;
    margin: 0;
}

.gazette-header h2 {
    font-size: 18px;
    margin: 8px 0 0;
    font-weight: normal;
}

.gazette-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.gazette-table th, .gazette-table td {
    border: 1px solid #000;
    padding: 10px;
    text-align: left;
    font-size: 13px;
}

.gazette-table th {
    background: #f3f4f6;
    font-weight: bold;
}

.status-badge {
    font-weight: bold;
    padding: 2px 6px;
    border: 1px solid #000;
}

.status-pass { background: #d1fae5; color: #065f46; }
.status-fail { background: #fee2e2; color: #991b1b; }
.status-ufm { background: #fef3c7; color: #92400e; }
</style>

<div class="page-header no-print">
    <h1>Official Exam Gazette</h1>
    <a href="results.php" class="btn" style="background: var(--gray);">Back to Results</a>
</div>

<div style="text-align: right; margin-bottom: 20px;" class="no-print">
    <button onclick="window.print()" class="btn" style="background:var(--primary);"><span style="margin-right:8px;">🖨️</span> Print Gazette</button>
</div>

<div class="print-area gazette-container">
    <div class="gazette-header">
        <h1>University Examination System</h1>
        <h2>OFFICIAL RESULT GAZETTE SHEET</h2>
        <h3 style="margin-top: 10px; font-weight: bold; font-size:16px;">
            EXAM Session: <?= htmlspecialchars($exam['exam_name']) ?> (<?= htmlspecialchars($exam['exam_type']) ?>)<br>
            Semester: <?= htmlspecialchars($exam['semester']) ?> | Batch Year: <?= htmlspecialchars($exam['batch_year']) ?>
        </h3>
    </div>
    
    <table class="gazette-table">
        <thead>
            <tr>
                <th style="width: 15%;">Roll Number</th>
                <th style="width: 25%;">Student Name</th>
                <th style="width: 35%;">Course Grades</th>
                <th style="width: 10%; text-align: center;">Credits</th>
                <th style="width: 10%; text-align: center;">SGPA</th>
                <th style="width: 15%; text-align: center;">Result Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($gazette_data as $g): ?>
            <tr>
                <td><strong><?= htmlspecialchars($g['roll_number']) ?></strong></td>
                <td><?= htmlspecialchars($g['name']) ?></td>
                <td>
                    <?php 
                    $grades = [];
                    foreach($courses as $cid => $c) {
                        if(isset($g['marks'][$cid])) {
                            $m = $g['marks'][$cid];
                            $grade_str = $m['is_ufm'] ? 'UFM' : ($m['grade'] ? $m['grade'] : 'X');
                            $grades[] = "{$c['course_code']}:{$grade_str}";
                        } else {
                            $grades[] = "{$c['course_code']}:-";
                        }
                    }
                    echo implode(", ", $grades);
                    ?>
                </td>
                <td style="text-align: center;"><?= $g['total_credits'] ?></td>
                <td style="text-align: center;"><strong><?= $g['sgpa'] ?></strong></td>
                <td style="text-align: center;">
                    <?php 
                    $class = '';
                    if($g['status'] == 'PASS') $class = 'status-pass';
                    elseif($g['status'] == 'UFM') $class = 'status-ufm';
                    else $class = 'status-fail';
                    ?>
                    <span class="status-badge <?= $class ?>"><?= $g['status'] ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($gazette_data)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No students enrolled in this semester and batch session.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 50px; display: flex; justify-content: space-between; font-family: 'Courier New', monospace;">
        <div style="text-align: center; width: 200px; border-top: 1px dashed #000; padding-top: 10px; font-size:12px;">Prepared By</div>
        <div style="text-align: center; width: 200px; border-top: 1px dashed #000; padding-top: 10px; font-size:12px;">Verified By</div>
        <div style="text-align: center; width: 250px; border-top: 1px dashed #000; padding-top: 10px; font-weight: bold; font-size:12px;">Controller of Examinations</div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
