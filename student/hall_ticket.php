<?php
// student/hall_ticket.php
require_once '../includes/auth.php';
checkRole('student');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['student_id'];

// Get upcoming/current exams for this student
$exams = $conn->query("
    SELECT DISTINCT e.exam_id as id, e.exam_name as name, e.semester, e.batch_year, e.exam_type as type, e.start_date, e.end_date
    FROM exams e
    JOIN enrollments en ON e.semester = en.semester AND e.batch_year = en.batch_year
    WHERE en.student_id = $student_id AND e.is_published = TRUE
    ORDER BY e.start_date DESC
");

$selected_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$exam_details = null;
$courses = null;

if($selected_exam > 0) {
    $ex_res = $conn->query("SELECT * FROM exams WHERE exam_id = $selected_exam AND is_published = TRUE");
    if($ex_res && $ex_res->num_rows > 0) {
        $exam_details = $ex_res->fetch_assoc();
        
        // Get courses enrolled in this semester
        $courses = $conn->query("
            SELECT c.course_code, c.course_name, c.credits, c.department 
            FROM enrollments e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.student_id = $student_id 
              AND e.semester = {$exam_details['semester']} 
              AND e.batch_year = '{$exam_details['batch_year']}'
        ");
    }
}

require_once '../includes/header.php';
?>

<div class="page-header no-print">
    <h1>Hall Ticket Generation</h1>
</div>

<div style="background: var(--white); padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; border: 1px solid var(--gray-light);" class="no-print">
    <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 300px;">
            <label>Select Published Exam Schedule</label>
            <select name="exam_id" class="form-control" onchange="this.form.submit()" required>
                <option value="">-- Choose Exam --</option>
                <?php while($ex = $exams->fetch_assoc()): ?>
                    <option value="<?= $ex['id'] ?>" <?= $selected_exam == $ex['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ex['name']) ?> 
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<?php if($selected_exam > 0 && $exam_details && $courses): ?>

<style>
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; border:none; box-shadow:none; padding: 20px; }
    .no-print { display: none !important; }
    .sidebar, .topbar { display: none !important; }
}
.ticket-box { border: 2px solid #000; padding: 30px; border-radius: 4px; font-family: 'Times New Roman', serif; background:white; }
.ticket-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; color: #000; }
.ticket-header h1 { font-size: 28px; text-transform: uppercase; margin:0; }
.ticket-header h2 { font-size: 20px; margin: 10px 0 0; font-weight:normal; }
.ticket-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; color: #000; }
.ticket-info p { margin: 8px 0; font-size: 16px; }
.ticket-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; color: #000; }
.ticket-table th, .ticket-table td { border: 1px solid #000; padding: 12px; text-align: left; }
.ticket-table th { background: #f0f0f0; }
.ticket-footer { display: flex; justify-content: space-between; margin-top: 50px; color: #000; }
.ticket-footer .sig { border-top: 1px solid #000; padding-top: 10px; width: 250px; text-align: center; }
</style>

<div class="print-area ticket-box">
    <div class="ticket-header">
        <h1>University Examination System</h1>
        <h2>HALL TICKET</h2>
        <h3 style="margin-top:10px; font-weight:bold;"><?= htmlspecialchars($exam_details['exam_name']) ?> (<?= htmlspecialchars($exam_details['exam_type']) ?>)</h3>
    </div>
    
    <div class="ticket-info">
        <div>
            <p><strong>Student Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
            <p><strong>Roll Number:</strong> <?= htmlspecialchars($student['roll_number']) ?></p>
            <p><strong>Program:</strong> <?= htmlspecialchars($student['program']) ?></p>
        </div>
        <div>
            <p><strong>Department:</strong> <?= htmlspecialchars($student['department']) ?></p>
            <p><strong>Semester:</strong> <?= htmlspecialchars($exam_details['semester']) ?></p>
            <p><strong>Exam Period:</strong> <?= htmlspecialchars($exam_details['start_date']) ?> to <?= htmlspecialchars($exam_details['end_date']) ?></p>
        </div>
    </div>
    
    <table class="ticket-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Credits</th>
                <th>Invigilator Signature</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while($c = $courses->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                <td><?= htmlspecialchars($c['course_name']) ?></td>
                <td><?= htmlspecialchars($c['credits']) ?></td>
                <td></td>
            </tr>
            <?php endwhile; ?>
            <?php if($courses->num_rows == 0): ?>
            <tr><td colspan="5" style="text-align:center;">No courses found for this semester.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="ticket-footer">
        <div class="sig">Student Signature</div>
        <div class="sig">Controller of Examinations</div>
    </div>
</div>

<div class="no-print" style="margin-top: 30px; text-align:center;">
    <button onclick="window.print()" class="btn btn-primary" style="font-size: 16px; padding: 12px 24px; background:var(--primary);"><span style="margin-right:10px;">🖨️</span> Print Hall Ticket</button>
</div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
