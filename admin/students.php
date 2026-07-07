<?php
// admin/students.php
require_once '../includes/auth.php';
checkRole('admin');

$error = '';
$success = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Delete user (cascade will handle student table)
    $stmt = $conn->prepare("SELECT user_id FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()) {
        $conn->query("DELETE FROM users WHERE user_id = " . $row['user_id']);
        $success = "Student deleted successfully.";
    }
}

// Handle Add
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $roll_number = trim($_POST['roll_number']);
    $name = trim($_POST['name']);
    $program = trim($_POST['program']);
    $department = trim($_POST['department']);
    $semester = (int)$_POST['semester'];
    $batch_year = trim($_POST['batch_year']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    
    if($_POST['action'] == 'add') {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $roll_number, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Roll Number (Username) or Email already exists.";
        } else {
            // Create user account
            $password = password_hash($roll_number, PASSWORD_DEFAULT);
            $stmt1 = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'student')");
            $stmt1->bind_param("ssss", $roll_number, $password, $email, $name);
            
            if($stmt1->execute()) {
                $user_id = $conn->insert_id;
                $stmt2 = $conn->prepare("INSERT INTO students (user_id, roll_number, program, department, semester, batch_year, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssiss", $user_id, $roll_number, $program, $department, $semester, $batch_year, $contact);
                if($stmt2->execute()){
                    $success = "Student added successfully. Login password is the Roll Number.";
                } else {
                    $error = "Error adding student profile: " . $conn->error;
                }
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

$students = $conn->query("
    SELECT s.student_id as id, s.roll_number, u.full_name as name, s.program, s.department, s.semester, s.batch_year, u.email 
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    ORDER BY s.student_id DESC
");
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Students</h1>
    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">+ Add Student</button>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Roll No</th>
                <th>Name</th>
                <th>Program/Dept</th>
                <th>Sem/Batch</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $students->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['roll_number']) ?></strong></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['program']) ?> / <?= htmlspecialchars($row['department']) ?></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?> (<?= htmlspecialchars($row['batch_year']) ?>)</td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" class="badge badge-danger" onclick="return confirm('Are you sure you want to delete this student?');" style="text-decoration:none;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($students->num_rows == 0): ?>
            <tr><td colspan="6" style="text-align:center; padding: 20px;">No students found.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding: 30px; border-radius: 8px; width: 600px; max-width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-bottom: 20px;">Add New Student</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group"><label>Roll Number (Username)</label><input type="text" name="roll_number" class="form-control" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Program (e.g. B.Tech)</label><input type="text" name="program" class="form-control" required></div>
                <div class="form-group"><label>Department (e.g. CSE)</label><input type="text" name="department" class="form-control" required></div>
                <div class="form-group"><label>Semester (Number)</label><input type="number" name="semester" class="form-control" min="1" max="10" required></div>
                <div class="form-group"><label>Batch Year (e.g. 2024-2028)</label><input type="text" name="batch_year" class="form-control" required></div>
                <div class="form-group"><label>Contact No</label><input type="text" name="contact" class="form-control"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background:var(--gray);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Student</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
