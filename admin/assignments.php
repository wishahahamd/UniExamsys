<?php
// admin/assignments.php
require_once '../includes/auth.php';
checkRole('admin');

$error = '';
$success = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($conn->query("DELETE FROM course_assignments WHERE assignment_id = $id")) {
        $success = "Assignment removed successfully.";
    } else {
        $error = "Error removing assignment: " . $conn->error;
    }
}

// Handle Add
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $course_id = (int)$_POST['course_id'];
    $semester = (int)$_POST['semester'];
    $batch_year = trim($_POST['batch_year']);
    
    if($_POST['action'] == 'add') {
        $stmt = $conn->prepare("INSERT INTO course_assignments (teacher_id, course_id, semester, batch_year) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $teacher_id, $course_id, $semester, $batch_year);
        
        if($stmt->execute()) {
            $success = "Teacher assigned to course successfully.";
        } else {
            if($conn->errno == 1062){ // Duplicate entry
                $error = "This assignment already exists.";
            } else {
                $error = "Error assigning course: " . $conn->error;
            }
        }
    }
}

$assignments = $conn->query("
    SELECT ca.assignment_id as id, u.full_name as teacher_name, c.course_code, c.course_name, ca.semester, ca.batch_year
    FROM course_assignments ca
    JOIN teachers t ON ca.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    JOIN courses c ON ca.course_id = c.course_id
    ORDER BY ca.semester, ca.batch_year, c.course_code
");

$teachers = $conn->query("
    SELECT t.teacher_id as id, u.full_name as name, t.employee_id 
    FROM teachers t 
    JOIN users u ON t.user_id = u.user_id 
    ORDER BY u.full_name
");
$courses = $conn->query("SELECT course_id as id, course_code, course_name as name FROM courses ORDER BY course_code");

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Course Assignments</h1>
    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">+ Assign Teacher</button>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Teacher Name</th>
                <th>Course</th>
                <th>Semester</th>
                <th>Batch Year</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $assignments->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['teacher_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['course_code']) ?> - <?= htmlspecialchars($row['course_name']) ?></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?></td>
                <td><?= htmlspecialchars($row['batch_year']) ?></td>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" class="badge badge-danger" onclick="return confirm('Remove this assignment?');" style="text-decoration:none;">Remove</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($assignments->num_rows == 0): ?>
            <tr><td colspan="5" style="text-align:center; padding: 20px;">No assignments found.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding: 30px; border-radius: 8px; width: 500px; max-width: 90%;">
        <h2 style="margin-bottom: 20px;">Assign Teacher to Course</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; gap: 15px;">
                <div class="form-group"><label>Teacher</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">Select Teacher</option>
                        <?php while($t = $teachers->fetch_assoc()): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['employee_id']) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php while($c = $courses->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Semester Taught</label><input type="number" name="semester" class="form-control" min="1" max="10" required></div>
                <div class="form-group"><label>Batch Year</label><input type="text" name="batch_year" class="form-control" required placeholder="e.g. 2024-2028"></div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background:var(--gray);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
