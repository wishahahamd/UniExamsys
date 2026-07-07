<?php
// admin/teachers.php
require_once '../includes/auth.php';
checkRole('admin');

$error = '';
$success = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT user_id FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()) {
        $conn->query("DELETE FROM users WHERE user_id = " . $row['user_id']);
        $success = "Teacher deleted successfully.";
    }
}

// Handle Add
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $employee_id = trim($_POST['employee_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $designation = trim($_POST['designation']);
    $contact = trim($_POST['contact']);
    
    if($_POST['action'] == 'add') {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $employee_id, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Employee ID (Username) or Email already exists.";
        } else {
            // Create user account
            $password = password_hash($employee_id, PASSWORD_DEFAULT);
            $stmt1 = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'teacher')");
            $stmt1->bind_param("ssss", $employee_id, $password, $email, $name);
            
            if($stmt1->execute()) {
                $user_id = $conn->insert_id;
                $stmt2 = $conn->prepare("INSERT INTO teachers (user_id, employee_id, department, designation, contact_number) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("issss", $user_id, $employee_id, $department, $designation, $contact);
                if($stmt2->execute()){
                    $success = "Teacher added successfully. Login password is the Employee ID.";
                } else {
                    $error = "Error adding teacher profile: " . $conn->error;
                }
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

$teachers = $conn->query("
    SELECT t.teacher_id as id, t.employee_id, u.full_name as name, u.email, t.department, t.designation, t.contact_number as contact 
    FROM teachers t 
    JOIN users u ON t.user_id = u.user_id 
    ORDER BY t.teacher_id DESC
");
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Teachers</h1>
    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">+ Add Teacher</button>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $teachers->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['employee_id']) ?></strong></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['designation']) ?></td>
                <td><?= htmlspecialchars($row['contact']) ?></td>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" class="badge badge-danger" onclick="return confirm('Are you sure you want to delete this teacher?');" style="text-decoration:none;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($teachers->num_rows == 0): ?>
            <tr><td colspan="7" style="text-align:center; padding: 20px;">No teachers found.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding: 30px; border-radius: 8px; width: 600px; max-width: 90%;">
        <h2 style="margin-bottom: 20px;">Add New Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group"><label>Employee ID (Username)</label><input type="text" name="employee_id" class="form-control" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>Department</label><input type="text" name="department" class="form-control" required></div>
                <div class="form-group"><label>Designation</label><input type="text" name="designation" class="form-control" required></div>
                <div class="form-group"><label>Contact No</label><input type="text" name="contact" class="form-control"></div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background:var(--gray);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Teacher</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
