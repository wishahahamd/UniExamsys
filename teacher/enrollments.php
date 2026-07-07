<?php
// teacher/enrollments.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole('teacher');

$error = '';
$success = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($conn->query("DELETE FROM enrollments WHERE enrollment_id = $id")) {
        $success = "Enrollment removed successfully.";
    } else {
        $error = "Error removing enrollment: " . $conn->error;
    }
}

// Handle Add Multiple (Bulk Enrollment based on selected course, semester, and batch)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'bulk_enroll') {
        $course_id = (int)$_POST['course_id'];
        $semester = (int)$_POST['semester'];
        $batch_year = trim($_POST['batch_year']);
        
        // Find all students in that semester and batch
        $stmt_students = $conn->prepare("SELECT student_id FROM students WHERE semester = ? AND batch_year = ?");
        $stmt_students->bind_param("is", $semester, $batch_year);
        $stmt_students->execute();
        $res = $stmt_students->get_result();
        
        $enrolled = 0;
        $failed = 0;
        
        while($student = $res->fetch_assoc()) {
            $student_id = $student['student_id'];
            $insert = $conn->prepare("INSERT IGNORE INTO enrollments (student_id, course_id, semester, batch_year) VALUES (?, ?, ?, ?)");
            $insert->bind_param("iiis", $student_id, $course_id, $semester, $batch_year);
            if($insert->execute() && $insert->affected_rows > 0) {
                $enrolled++;
            } else {
                $failed++;
            }
        }
        
        $success = "Successfully enrolled $enrolled students. ($failed skipped/already enrolled).";
    }
}

// Get all enrolled students in the system
$enrollments = $conn->query("
    SELECT e.enrollment_id as id, s.roll_number, u.full_name as student_name, c.course_code, c.course_name, e.semester, e.batch_year
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN courses c ON e.course_id = c.course_id
    ORDER BY e.batch_year, e.semester, c.course_code, s.roll_number
");

// Fetch all courses in the system for the dropdown
$courses_res = $conn->query("
    SELECT course_id as id, course_code, course_name as name 
    FROM courses 
    ORDER BY course_code
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Course Enrollments</h1>
    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">+ Enroll Students</button>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Roll Number</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Sem / Batch</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $enrollments->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['roll_number']) ?></strong></td>
                <td><?= htmlspecialchars($row['student_name']) ?></td>
                <td><?= htmlspecialchars($row['course_code']) ?> - <?= htmlspecialchars($row['course_name']) ?></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?> (<?= htmlspecialchars($row['batch_year']) ?>)</td>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" class="badge badge-danger" onclick="return confirm('Remove this student enrollment?');" style="text-decoration:none;">Remove</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($enrollments->num_rows == 0): ?>
            <tr><td colspan="5" style="text-align:center; padding: 20px; color: var(--gray);">No student enrollments found. Click "+ Enroll Students" above to enroll classes.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<!-- Bulk Enroll Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding: 30px; border-radius: 8px; width: 500px; max-width: 90%;">
        <h2 style="margin-bottom: 20px; color: var(--dark);">Enroll Class to Course</h2>
        <p style="margin-bottom: 20px; font-size: 14px; color: var(--gray);">Select a course, target semester, and target batch. All students belonging to the selected semester and batch will be enrolled in the course automatically.</p>
        <form method="POST">
            <input type="hidden" name="action" value="bulk_enroll">
            <div style="display: grid; gap: 15px;">
                <div class="form-group">
                    <label style="font-weight:600; margin-bottom:8px; display:block; color:var(--dark);">Select Course</label>
                    <select name="course_id" class="form-control" required style="width:100%;">
                        <option value="">-- Select Course --</option>
                        <?php while($c = $courses_res->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; margin-bottom:8px; display:block; color:var(--dark);">Target Semester</label>
                    <input type="number" name="semester" class="form-control" min="1" max="10" required placeholder="e.g. 1">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; margin-bottom:8px; display:block; color:var(--dark);">Target Batch Year</label>
                    <input type="text" name="batch_year" class="form-control" required placeholder="e.g. 2026">
                </div>
            </div>
            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background:var(--gray-light); color:var(--dark);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Enroll Students</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
