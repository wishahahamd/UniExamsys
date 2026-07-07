<?php
// admin/results.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole('admin');

$success = '';
$error = '';

if(isset($_GET['publish'])) {
    $id = (int)$_GET['publish'];
    if(processExamResults($id, $conn)) {
        $success = "Results published successfully!";
    } else {
        $error = "Failed to process results.";
    }
}

if(isset($_GET['unpublish'])) {
    $id = (int)$_GET['unpublish'];
    $conn->query("UPDATE exams SET is_published = FALSE WHERE exam_id = $id");
    $success = "Results unpublished.";
}

$exams = $conn->query("SELECT * FROM exams ORDER BY start_date DESC");
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Result Publishing</h1>
</div>

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Sem/Batch</th>
                <th>Exam Status</th>
                <th>Result Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $exams->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['exam_name']) ?></strong></td>
                <td>Sem <?= htmlspecialchars($row['semester']) ?> (<?= htmlspecialchars($row['batch_year']) ?>)</td>
                <td>
                    <?php if($row['is_published']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Draft</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($row['is_published']): ?>
                        <span class="badge badge-success">Results Live</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Not Published</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!$row['is_published']): ?>
                        <a href="?publish=<?= $row['exam_id'] ?>" class="btn" style="padding: 6px 12px; font-size:13px; text-decoration:none;" onclick="return confirm('Publish all results? This will calculate grades and make them visible to students.');">Publish Results</a>
                    <?php else: ?>
                        <a href="?unpublish=<?= $row['exam_id'] ?>" class="btn" style="padding: 6px 12px; font-size:13px; background:var(--warning); text-decoration:none; margin-right:5px;" onclick="return confirm('Hide results from students?');">Unpublish</a>
                        <a href="gazette.php?exam_id=<?= $row['exam_id'] ?>" class="btn bg-success-light" style="background:#10B981; padding: 6px 12px; font-size:13px; text-decoration:none; margin-right:5px;">Gazette</a>
                        <a href="tr.php?exam_id=<?= $row['exam_id'] ?>" class="btn bg-purple-light" style="padding: 6px 12px; font-size:13px; text-decoration:none;">Tabulation Register (TR)</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if($exams->num_rows == 0): ?>
            <tr><td colspan="5" style="text-align:center; padding: 20px;">No exams found.</td></tr>
            <?php endif;?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
