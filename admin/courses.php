<?php
// admin/courses.php
require_once '../includes/auth.php';
checkRole('admin');

$error = '';
$success = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($conn->query("DELETE FROM courses WHERE course_id = $id")) {
        $success = "Course deleted successfully.";
    } else {
        $error = "Error deleting course: " . $conn->error;
    }
}

// Handle Add
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $course_code = trim($_POST['course_code']);
    $name = trim($_POST['name']);
    $credits = (int)$_POST['credits'];
    $department = trim($_POST['department']);
    $semester = (int)$_POST['semester'];
    
    if($_POST['action'] == 'add') {
        $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, credits, department, semester) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $course_code, $name, $credits, $department, $semester);
        
        if($stmt->execute()) {
            $success = "Course added successfully.";
        } else {
            if($conn->errno == 1062){ // Duplicate entry
                $error = "Course Code already exists.";
            } else {
                $error = "Error adding course: " . $conn->error;
            }
        }
    }
}

$courses = $conn->query("SELECT * FROM courses ORDER BY department, semester");
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Courses</h1>
    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">+ Add Course</button>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Credits</th>
                <th>Department</th>
                <th>Semester</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $courses->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['course_code']) ?></strong></td>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= htmlspecialchars($row['credits']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?></td>
                <td>
                    <a href="?delete=<?= $row['course_id'] ?>" class="badge badge-danger" onclick="return confirm('Delete course?');" style="text-decoration:none;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($courses->num_rows == 0): ?>
            <tr><td colspan="6" style="text-align:center; padding: 20px;">No courses found.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding: 30px; border-radius: 8px; width: 500px; max-width: 90%;">
        <h2 style="margin-bottom: 20px;">Add New Course</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; gap: 15px;">
                <div class="form-group"><label>Course Code</label><input type="text" name="course_code" class="form-control" required placeholder="e.g. CS101"></div>
                <div class="form-group"><label>Course Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Credits</label><input type="number" name="credits" class="form-control" min="1" max="10" required value="3"></div>
                <div class="form-group"><label>Department</label><input type="text" name="department" class="form-control" required></div>
                <div class="form-group"><label>Semester Taught In</label><input type="number" name="semester" class="form-control" min="1" max="10" required></div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background:var(--gray);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Course</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
