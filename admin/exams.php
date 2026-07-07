<?php
// admin/exams.php
require_once '../includes/auth.php';
checkRole('admin');

$error = '';
$success = '';

// Handle Actions
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($conn->query("DELETE FROM exams WHERE exam_id = $id")) {
        $success = "Exam deleted successfully.";
    } else {
        $error = "Error deleting exam: " . $conn->error;
    }
}
if(isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $is_published = $_GET['status'] == 'Published' ? 1 : 0;
    $conn->query("UPDATE exams SET is_published = $is_published WHERE exam_id = $id");
    header("Location: exams.php");
    exit();
}

// Handle Add
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $semester = (int)$_POST['semester'];
    $batch_year = trim($_POST['batch_year']);
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);
    
    if($_POST['action'] == 'add') {
        $stmt = $conn->prepare("INSERT INTO exams (exam_name, exam_type, semester, batch_year, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $name, $type, $semester, $batch_year, $start_date, $end_date);
        
        if($stmt->execute()) {
            $success = "Exam session created successfully.";
        } else {
            $error = "Error adding exam: " . $conn->error;
        }
    }
}

$exams = $conn->query("SELECT * FROM exams ORDER BY start_date DESC");
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Exam Sessions</h1>
    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">+ Create Exam</button>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Type</th>
                <th>Sem/Batch</th>
                <th>Dates</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $exams->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['exam_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['exam_type']) ?></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?> (<?= htmlspecialchars($row['batch_year']) ?>)</td>
                <td><?= htmlspecialchars($row['start_date']) ?> to <?= htmlspecialchars($row['end_date']) ?></td>
                <td>
                    <?php if($row['is_published']): ?>
                        <span class="badge badge-success">Published</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Draft</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!$row['is_published']): ?>
                        <a href="?id=<?= $row['exam_id'] ?>&status=Published" class="badge badge-success" style="text-decoration:none;">Publish</a>
                    <?php else: ?>
                        <a href="?id=<?= $row['exam_id'] ?>&status=Draft" class="badge badge-warning" style="text-decoration:none;">Unpublish</a>
                    <?php endif; ?>
                    <a href="?delete=<?= $row['exam_id'] ?>" class="badge badge-danger" onclick="return confirm('Delete this exam completely?');" style="text-decoration:none; margin-left:5px;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($exams->num_rows == 0): ?>
            <tr><td colspan="6" style="text-align:center; padding: 20px;">No exams found.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding: 30px; border-radius: 8px; width: 600px; max-width: 90%;">
        <h2 style="margin-bottom: 20px;">Create Exam Session</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group" style="grid-column: span 2;"><label>Exam Name (e.g. Midterm Fall 2024)</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Exam Type</label>
                    <select name="type" class="form-control" required>
                        <option value="Midterm">Midterm</option>
                        <option value="Final">Final</option>
                        <option value="Quiz">Quiz</option>
                    </select>
                </div>
                <div class="form-group"><label>Semester</label><input type="number" name="semester" class="form-control" required min="1"></div>
                <div class="form-group"><label>Batch Year</label><input type="text" name="batch_year" class="form-control" required placeholder="2024-2028"></div>
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" required></div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background:var(--gray);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Exam</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
