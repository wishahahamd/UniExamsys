<?php
// teacher/marks_entry.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole('teacher');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher_id = $stmt->get_result()->fetch_assoc()['teacher_id'];

$error = '';
$success = '';

// Check if form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_marks') {
    $exam_id = (int)$_POST['exam_id'];
    $course_id = (int)$_POST['course_id'];
    
    $marksData = $_POST['marks']; // Expected array: student_id => ['internal'=>x, 'external'=>y, 'ufm'=>0/1]
    
    $conn->begin_transaction();
    try {
        $scale = getGradingScale($conn);
        foreach($marksData as $student_id => $data) {
            $internalStr = $data['internal'];
            $externalStr = $data['external'];
            
            $internal = ($internalStr !== '') ? (float)$internalStr : "NULL";
            $external = ($externalStr !== '') ? (float)$externalStr : "NULL";
            $is_ufm = isset($data['ufm']) ? 1 : 0;
            
            if($internalStr === '' && $externalStr === '') {
                continue; 
            }
            
            $int_val = $internal === "NULL" ? 0 : $internal;
            $ext_val = $external === "NULL" ? 0 : $external;
            $total = $int_val + $ext_val;
            
            // Calculate actual grade and points on the fly!
            $calc = calculateGrade($total, $is_ufm, $scale);
            $grade = $calc['grade'];
            $grade_point = $calc['point'];
            
            $totalValStr = $total;
            
            $sql = "INSERT INTO marks (student_id, course_id, exam_id, internal_marks, external_marks, total_marks, grade, grade_point, is_ufm, teacher_id) 
                    VALUES ($student_id, $course_id, $exam_id, $internal, $external, $totalValStr, '$grade', $grade_point, $is_ufm, $teacher_id)
                    ON DUPLICATE KEY UPDATE 
                    internal_marks = $internal, external_marks = $external, total_marks = $totalValStr, grade = '$grade', grade_point = $grade_point, is_ufm = $is_ufm, teacher_id = $teacher_id";
            
            $conn->query($sql);
        }
        $conn->commit();
        $success = "Marks saved successfully.";
    } catch(Exception $e) {
        $conn->rollback();
        $error = "Error saving marks: " . $e->getMessage();
    }
}

// Prepare UI drop-downs
// Fetch all active exams directly
$exams = $conn->query("
    SELECT exam_id as id, exam_name as name, exam_type as type, semester, batch_year 
    FROM exams 
    ORDER BY semester DESC, batch_year DESC
");

// Filter form variables
$selected_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

$assigned_courses = null;
if($selected_exam > 0) {
    // Get exams to find batch & sem
    $ex_data = $conn->query("SELECT semester, batch_year FROM exams WHERE exam_id = $selected_exam")->fetch_assoc();
    $sem = $ex_data['semester'];
    
    // Get all courses belonging to this semester
    $assigned_courses = $conn->query("
        SELECT course_id as id, course_code, course_name as name 
        FROM courses
        WHERE semester = $sem
        ORDER BY course_code
    ");
}

$studentsList = null;
if($selected_exam > 0 && $selected_course > 0) {
    // Get students enrolled in this course for this semester+batch
    $query = "
        SELECT s.student_id, s.roll_number, u.full_name as name, 
               m.internal_marks, m.external_marks, m.is_ufm
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN marks m ON s.student_id = m.student_id AND m.course_id = $selected_course AND m.exam_id = $selected_exam
        WHERE e.course_id = $selected_course 
          AND e.semester = (SELECT semester FROM exams WHERE exam_id = $selected_exam)
          AND e.batch_year = (SELECT batch_year FROM exams WHERE exam_id = $selected_exam)
        ORDER BY s.roll_number
    ";
    $studentsList = $conn->query($query);
}

// Fetch grading scale for JavaScript live estimation
$scale_query = $conn->query("SELECT grade, min_percentage, max_percentage, grade_point FROM grading_scale ORDER BY min_percentage DESC");
$js_scale = [];
if($scale_query) {
    while($row = $scale_query->fetch_assoc()) {
        $js_scale[] = [
            'grade' => $row['grade'],
            'min' => (float)$row['min_percentage'],
            'max' => (float)$row['max_percentage'],
            'point' => (float)$row['grade_point']
        ];
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Marks Entry</h1>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="background: var(--white); padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; border: 1px solid var(--gray-light);">
    <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0; min-width: 250px;">
            <label>Select Exam Session</label>
            <select name="exam_id" class="form-control" onchange="this.form.submit()" required>
                <option value="">-- Choose Exam --</option>
                <?php while($ex = $exams->fetch_assoc()): ?>
                    <option value="<?= $ex['id'] ?>" <?= $selected_exam == $ex['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ex['name']) ?> (Sem <?= $ex['semester'] ?>, <?= htmlspecialchars($ex['batch_year']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <?php if($selected_exam > 0 && $assigned_courses): ?>
        <div class="form-group" style="margin-bottom: 0; min-width: 250px;">
            <label>Select Course</label>
            <select name="course_id" class="form-control" required>
                <option value="">-- Choose Course --</option>
                <?php while($crs = $assigned_courses->fetch_assoc()): ?>
                    <option value="<?= $crs['id'] ?>" <?= $selected_course == $crs['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($crs['course_code']) ?> - <?= htmlspecialchars($crs['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn">Load Students</button>
        <?php endif; ?>
    </form>
</div>

<?php if($studentsList && $studentsList->num_rows > 0): ?>
<form method="POST">
    <input type="hidden" name="action" value="save_marks">
    <input type="hidden" name="exam_id" value="<?= $selected_exam ?>">
    <input type="hidden" name="course_id" value="<?= $selected_course ?>">
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Student Name</th>
                    <th style="width: 130px;">Internal (Max 40)</th>
                    <th style="width: 130px;">External (Max 60)</th>
                    <th>Total</th>
                    <th style="text-align: center; width: 120px;">Estimated Grade</th>
                    <th style="text-align: center; width: 120px;">Grade Points</th>
                    <th>UFM Flag</th>
                </tr>
            </thead>
            <tbody>
                <?php while($stu = $studentsList->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($stu['roll_number']) ?></strong></td>
                    <td><?= htmlspecialchars($stu['name']) ?></td>
                    <td>
                        <input type="number" step="0.5" min="0" max="40" 
                            name="marks[<?= $stu['student_id'] ?>][internal]" 
                            class="form-control" 
                            value="<?= $stu['internal_marks'] !== null ? htmlspecialchars($stu['internal_marks']) : '' ?>"
                            oninput="calculateTotal(this)" onchange="calculateTotal(this)" data-max="40">
                    </td>
                    <td>
                        <input type="number" step="0.5" min="0" max="60" 
                            name="marks[<?= $stu['student_id'] ?>][external]" 
                            class="form-control" 
                            value="<?= $stu['external_marks'] !== null ? htmlspecialchars($stu['external_marks']) : '' ?>"
                            oninput="calculateTotal(this)" onchange="calculateTotal(this)" data-max="60">
                    </td>
                    <td>
                        <strong class="row-total">
                            <?= ($stu['internal_marks'] !== null || $stu['external_marks'] !== null) ? (($stu['internal_marks'] ?? 0) + ($stu['external_marks'] ?? 0)) : '--' ?>
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <span class="row-grade badge" style="font-size: 13px; font-weight: 700; min-width: 35px; display: inline-block;">--</span>
                    </td>
                    <td style="text-align: center;">
                        <strong class="row-points" style="color: var(--gray);">--</strong>
                    </td>
                    <td>
                        <input type="checkbox" name="marks[<?= $stu['student_id'] ?>][ufm]" value="1" <?= $stu['is_ufm'] ? 'checked' : '' ?> onchange="calculateTotal(this)">
                        <span style="font-size: 13px; color: var(--danger); margin-left: 3px;">Mark UFM</span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px; text-align: right;">
        <button type="submit" class="btn" style="background: #10B981; color: #ffffff; font-weight: 700; padding: 12px 30px; border-radius: 6px; font-size: 15px; border: none; cursor: pointer; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); transition: background 0.2s;">Save All Marks</button>
    </div>
</form>

<script>
const gradingScale = <?= json_encode($js_scale) ?>;

function getGradeForMarks(marks, isUfm) {
    if (isUfm) return { grade: 'UFM', point: 0.00 };
    if (marks === null || isNaN(marks)) return { grade: '--', point: null };
    
    for (let s of gradingScale) {
        if (marks >= s.min && marks <= s.max) {
            return { grade: s.grade, point: s.point };
        }
    }
    
    // Fallback default scale
    if (marks >= 90) return { grade: 'A+', point: 4.00 };
    if (marks >= 85) return { grade: 'A', point: 3.67 };
    if (marks >= 80) return { grade: 'A-', point: 3.33 };
    if (marks >= 75) return { grade: 'B+', point: 3.00 };
    if (marks >= 70) return { grade: 'B', point: 2.67 };
    if (marks >= 65) return { grade: 'B-', point: 2.33 };
    if (marks >= 60) return { grade: 'C+', point: 2.00 };
    if (marks >= 55) return { grade: 'C', point: 1.67 };
    if (marks >= 50) return { grade: 'C-', point: 1.33 };
    return { grade: 'F', point: 0.00 };
}

function updateRowGrades(row) {
    let internalInput = row.querySelector('input[name$="[internal]"]').value;
    let externalInput = row.querySelector('input[name$="[external]"]').value;
    let isUfm = row.querySelector('input[name$="[ufm]"]').checked;
    
    let internalVal = internalInput !== '' ? parseFloat(internalInput) : null;
    let externalVal = externalInput !== '' ? parseFloat(externalInput) : null;
    
    let totalText = '--';
    let gradeText = '--';
    let pointsText = '--';
    let badgeClass = 'badge-success';
    
    if (isUfm) {
        totalText = 'UFM';
        let res = getGradeForMarks(0, true);
        gradeText = res.grade;
        pointsText = res.point.toFixed(2);
        badgeClass = 'badge-danger';
    } else if (internalVal !== null || externalVal !== null) {
        let total = (internalVal || 0) + (externalVal || 0);
        totalText = total.toFixed(1);
        let res = getGradeForMarks(total, false);
        gradeText = res.grade;
        pointsText = res.point !== null ? res.point.toFixed(2) : '--';
        if (gradeText === 'F') {
            badgeClass = 'badge-danger';
        }
    }
    
    row.querySelector('.row-total').innerText = totalText;
    
    let gradeEl = row.querySelector('.row-grade');
    gradeEl.innerText = gradeText;
    gradeEl.className = 'row-grade badge ' + (gradeText === '--' ? '' : badgeClass);
    if (gradeText === '--') {
        gradeEl.style.display = 'none';
    } else {
        gradeEl.style.display = 'inline-block';
    }
    
    row.querySelector('.row-points').innerText = pointsText;
}

function calculateTotal(input) {
    let row = input.closest('tr');
    let internalInput = row.querySelector('input[name$="[internal]"]').value;
    let externalInput = row.querySelector('input[name$="[external]"]').value;
    
    if(internalInput !== '') {
        let val = parseFloat(internalInput);
        if(val > 40) { alert('Internal marks cannot exceed 40'); row.querySelector('input[name$="[internal]"]').value = 40; }
        if(val < 0) { row.querySelector('input[name$="[internal]"]').value = 0; }
    }
    if(externalInput !== '') {
        let val = parseFloat(externalInput);
        if(val > 60) { alert('External marks cannot exceed 60'); row.querySelector('input[name$="[external]"]').value = 60; }
        if(val < 0) { row.querySelector('input[name$="[external]"]').value = 0; }
    }
    
    updateRowGrades(row);
}

// Perform initial run on load to show pre-saved marks grades
document.querySelectorAll('tbody tr').forEach(row => {
    updateRowGrades(row);
});
</script>

<?php elseif($studentsList): ?>
<div class="alert alert-warning" style="margin-top:20px;">No enrolled students found for this specific course in the selected batch/semester.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
