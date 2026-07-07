<?php
// teacher/courses.php
require_once '../includes/auth.php';
checkRole('teacher');

// Get teacher ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher_id = $stmt->get_result()->fetch_assoc()['teacher_id'];

// Get assigned courses
$courses = $conn->query("
    SELECT c.course_id, c.course_code, c.course_name as name, c.credits, ca.semester, ca.batch_year
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.course_id
    WHERE ca.teacher_id = $teacher_id
    ORDER BY ca.semester DESC, ca.batch_year DESC
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>My Assigned Courses</h1>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Credits</th>
                <th>Semester</th>
                <th>Batch Year</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $courses->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['course_code']) ?></strong></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['credits']) ?></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?></td>
                <td><?= htmlspecialchars($row['batch_year']) ?></td>
                <td>
                    <a href="marks_entry.php?course_id=<?= $row['course_id'] ?>&batch_year=<?= urlencode($row['batch_year']) ?>&semester=<?= $row['semester'] ?>" class="btn" style="padding: 6px 12px; font-size:13px;">Enter Marks</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($courses->num_rows == 0): ?>
            <tr><td colspan="6" style="text-align:center; padding: 20px;">No courses assigned to you yet.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
