<?php
// student/results.php
require_once '../includes/auth.php';
checkRole('student');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_id = $stmt->get_result()->fetch_assoc()['student_id'];

// Get all published exams this student has marks in
$exams = $conn->query("
    SELECT DISTINCT e.exam_id, e.exam_name as name, e.semester, e.batch_year 
    FROM exams e
    JOIN marks m ON e.exam_id = m.exam_id
    WHERE m.student_id = $student_id AND e.is_published = TRUE
    ORDER BY e.start_date DESC
");

$selected_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$results = null;

if($selected_exam > 0) {
    // Verify exam is published
    $check_exam = $conn->query("SELECT is_published FROM exams WHERE exam_id = $selected_exam")->fetch_assoc();
    if($check_exam && $check_exam['is_published']) {
        $results = $conn->query("
            SELECT c.course_code, c.course_name, m.internal_marks, m.external_marks, m.total_marks, m.grade, m.grade_point, c.credits, m.is_ufm
            FROM marks m
            JOIN courses c ON m.course_id = c.course_id
            WHERE m.student_id = $student_id AND m.exam_id = $selected_exam
        ");
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>My Results</h1>
</div>

<div style="background: var(--white); padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; border: 1px solid var(--gray-light);">
    <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 300px;">
            <label>Select Published Exam</label>
            <select name="exam_id" class="form-control" onchange="this.form.submit()" required>
                <option value="">-- Choose Exam --</option>
                <?php while($ex = $exams->fetch_assoc()): ?>
                    <option value="<?= $ex['exam_id'] ?>" <?= $selected_exam == $ex['exam_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ex['name']) ?> (Sem <?= $ex['semester'] ?>, <?= htmlspecialchars($ex['batch_year']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<?php if($selected_exam > 0 && $results && $results->num_rows > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Credits</th>
                    <th>Internal (40)</th>
                    <th>External (60)</th>
                    <th>Total (100)</th>
                    <th>Grade</th>
                    <th>Point</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_credits = 0;
                $earned_points = 0;
                while($row = $results->fetch_assoc()): 
                    if($row['is_ufm']) {
                        $display_internal = 'UFM';
                        $display_external = 'UFM';
                        $display_total = 'UFM';
                    } else {
                        $display_internal = $row['internal_marks'] !== null ? htmlspecialchars($row['internal_marks']) : '-';
                        $display_external = $row['external_marks'] !== null ? htmlspecialchars($row['external_marks']) : '-';
                        $display_total = $row['total_marks'] !== null ? htmlspecialchars($row['total_marks']) : '-';
                    }
                    
                    // Calculate SGPA totals
                    $total_credits += $row['credits'];
                    if($row['grade_point'] !== null) {
                        $earned_points += ($row['credits'] * $row['grade_point']);
                    }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['course_code']) ?></strong></td>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><?= htmlspecialchars($row['credits']) ?></td>
                    <td><?= $display_internal ?></td>
                    <td><?= $display_external ?></td>
                    <td><strong><?= $display_total ?></strong></td>
                    <td>
                        <?php if($row['grade'] == 'F' || $row['grade'] == 'UFM'): ?>
                            <span class="badge badge-danger"><?= htmlspecialchars($row['grade']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-success"><?= htmlspecialchars($row['grade']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['grade_point']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php 
        $sgpa = 0.00;
        if($total_credits > 0) {
            $sgpa = number_format($earned_points / $total_credits, 2);
        }
        
        $legend_res = $conn->query("SELECT grade, grade_point FROM grading_scale ORDER BY min_percentage DESC");
        $legend_arr = [];
        if($legend_res) {
            while($l_row = $legend_res->fetch_assoc()) {
                $legend_arr[] = "<strong>{$l_row['grade']}</strong> ({$l_row['grade_point']})";
            }
        }
        $legend_str = implode(" | ", $legend_arr);
        ?>
        <div style="background: var(--light); padding: 20px; border-top: 1px solid var(--gray-light); text-align: right; font-size: 18px;">
            <div style="margin-bottom: 10px; font-size: 14px; color: var(--gray);">
                Legend: <?= $legend_str ?>
            </div>
            SGPA for this Exam: <strong><?= $sgpa ?></strong>
        </div>
    </div>
<?php elseif($selected_exam > 0): ?>
    <div class="alert alert-warning">Results not available or exam not published yet.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
