<?php
// admin/tr.php
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

$tr_data = [];
while($stu = $students_res->fetch_assoc()) {
    $student_id = $stu['student_id'];
    
    // Fetch marks for this student and exam
    $marks_res = $conn->query("
        SELECT course_id, internal_marks, external_marks, total_marks, grade, grade_point, is_ufm
        FROM marks
        WHERE student_id = $student_id AND exam_id = $exam_id
    ");
    
    $student_marks = [];
    $total_credits = 0;
    $total_points = 0;
    $has_ufm = false;
    
    while($m = $marks_res->fetch_assoc()) {
        $student_marks[$m['course_id']] = $m;
        if($m['is_ufm']) {
            $has_ufm = true;
        }
        
        $course_id = $m['course_id'];
        if(isset($courses[$course_id])) {
            $credits = $courses[$course_id]['credits'];
            $total_credits += $credits;
            $total_points += ($credits * $m['grade_point']);
        }
    }
    
    $sgpa = 0.00;
    if($total_credits > 0) {
        $sgpa = number_format($total_points / $total_credits, 2);
    }
    
    $tr_data[] = [
        'roll_number' => $stu['roll_number'],
        'name' => $stu['name'],
        'marks' => $student_marks,
        'total_credits' => $total_credits,
        'total_points' => $total_points,
        'sgpa' => $sgpa
    ];
}

require_once '../includes/header.php';
?>

<style>
@media print {
    @page {
        size: landscape;
        margin: 10mm;
    }
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; border:none; box-shadow:none; padding: 0; }
    .no-print { display: none !important; }
    .sidebar, .topbar { display: none !important; }
    
    table {
        page-break-inside: avoid;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}

.tr-container {
    background: white;
    padding: 30px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    font-family: 'Times New Roman', Times, serif;
    color: #000;
    border: 1px solid var(--gray-light);
    overflow-x: auto;
}

.tr-header {
    text-align: center;
    border-bottom: 3px double #000;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.tr-header h1 {
    font-size: 28px;
    text-transform: uppercase;
    margin: 0;
}

.tr-header h2 {
    font-size: 18px;
    margin: 8px 0 0;
    font-weight: normal;
}

.tr-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.tr-table th, .tr-table td {
    border: 1px solid #000;
    padding: 6px 8px;
    font-size: 12px;
    text-align: center;
}

.tr-table th {
    background: #f8fafc;
    font-weight: bold;
    text-transform: uppercase;
}

.student-info-cell {
    text-align: left !important;
    font-weight: 500;
}
</style>

<div class="page-header no-print">
    <h1>Tabulation Register (TR)</h1>
    <a href="results.php" class="btn" style="background: var(--gray);">Back to Results</a>
</div>

<div style="text-align: right; margin-bottom: 20px;" class="no-print">
    <button onclick="window.print()" class="btn" style="background:var(--primary);"><span style="margin-right:8px;">🖨️</span> Print Master Ledger</button>
</div>

<div class="print-area tr-container">
    <div class="tr-header">
        <h1>University Examination System</h1>
        <h2>TABULATION REGISTER SHEET (TR)</h2>
        <h3 style="margin-top: 10px; font-weight: bold; font-size:16px;">
            EXAM Session: <?= htmlspecialchars($exam['exam_name']) ?> (<?= htmlspecialchars($exam['exam_type']) ?>)<br>
            Semester: <?= htmlspecialchars($exam['semester']) ?> | Batch Year: <?= htmlspecialchars($exam['batch_year']) ?>
        </h3>
    </div>
    
    <table class="tr-table">
        <thead>
            <!-- First Row Headers -->
            <tr>
                <th rowspan="2" style="width: 10%;">Roll Number</th>
                <th rowspan="2" style="width: 15%;">Student Name</th>
                <?php foreach($courses as $c): ?>
                    <th colspan="4"><?= htmlspecialchars($c['course_code']) ?> (Cr: <?= $c['credits'] ?>)</th>
                <?php endforeach; ?>
                <th rowspan="2" style="width: 5%;">Total Credits</th>
                <th rowspan="2" style="width: 5%;">Total Points</th>
                <th rowspan="2" style="width: 6%;">SGPA</th>
            </tr>
            <!-- Second Row Sub-Headers -->
            <tr>
                <?php foreach($courses as $c): ?>
                    <th style="font-size: 10px; width: 4%;">Int</th>
                    <th style="font-size: 10px; width: 4%;">Ext</th>
                    <th style="font-size: 10px; width: 5%;">Tot</th>
                    <th style="font-size: 10px; width: 4%;">Gr</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tr_data as $g): ?>
            <tr>
                <td class="student-info-cell"><strong><?= htmlspecialchars($g['roll_number']) ?></strong></td>
                <td class="student-info-cell"><?= htmlspecialchars($g['name']) ?></td>
                
                <?php foreach($courses as $cid => $c): ?>
                    <?php if(isset($g['marks'][$cid])): 
                        $m = $g['marks'][$cid];
                        if($m['is_ufm']) {
                            $int_d = 'UFM';
                            $ext_d = 'UFM';
                            $tot_d = 'UFM';
                            $gr_d = 'UFM';
                        } else {
                            $int_d = $m['internal_marks'] !== null ? htmlspecialchars($m['internal_marks']) : '-';
                            $ext_d = $m['external_marks'] !== null ? htmlspecialchars($m['external_marks']) : '-';
                            $tot_d = $m['total_marks'] !== null ? htmlspecialchars($m['total_marks']) : '-';
                            $gr_d = $m['grade'] ? htmlspecialchars($m['grade']) : 'X';
                        }
                    ?>
                        <td><?= $int_d ?></td>
                        <td><?= $ext_d ?></td>
                        <td><strong><?= $tot_d ?></strong></td>
                        <td><strong><?= $gr_d ?></strong></td>
                    <?php else: ?>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <td><?= $g['total_credits'] ?></td>
                <td><?= number_format($g['total_points'], 2) ?></td>
                <td><strong><?= $g['sgpa'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($tr_data)): ?>
            <tr>
                <td colspan="<?= 3 + (count($courses) * 4) ?>" style="text-align: center; padding: 20px;">No students enrolled in this semester and batch session.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 50px; display: flex; justify-content: space-between; font-family: 'Times New Roman', serif;">
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 10px; font-size:12px;">Prepared By</div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 10px; font-size:12px;">Verified By</div>
        <div style="text-align: center; width: 250px; border-top: 1px solid #000; padding-top: 10px; font-weight: bold; font-size:12px;">Controller of Examinations</div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
