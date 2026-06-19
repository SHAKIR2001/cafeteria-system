<?php
/**
 * admin/users.php — View & Delete Students
 */
require_once '../config.php';
require_once '../db.php';
require_admin();

$msg = '';

// ── DELETE USER ───────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Prevent self-deletion
    if ($del_id !== (int)$_SESSION['user_id']) {
        $del = mysqli_prepare($conn, 'DELETE FROM users WHERE id=? AND role="student"');
        mysqli_stmt_bind_param($del, 'i', $del_id);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
    }
    header('Location: users.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) $msg = 'User deleted successfully.';

// ── FETCH students ────────────────────────────────────────────
$result = mysqli_query($conn,
    "SELECT id, name, email, student_id, phone, created_at FROM users WHERE role='student' ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="users-page-header">
      <div><h1>Students</h1><p>Manage registered student accounts</p></div>
    </div>

    <?php if ($msg): ?>
      <p style="padding:10px 35px;color:#16a34a;font-weight:bold;"><?= e($msg) ?></p>
    <?php endif; ?>

    <div class="users-table-section" style="width:88%;margin:20px auto;">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Name</th><th>Email</th>
              <th>Student ID</th><th>Phone</th><th>Registered</th><th>Action</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
            <?php if (mysqli_num_rows($result) === 0): ?>
              <tr><td colspan="7" class="no-orders">No students registered yet</td></tr>
            <?php else: ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= e($row['name']) ?></td>
                  <td><?= e($row['email']) ?></td>
                  <td><?= e($row['student_id'] ?? '--') ?></td>
                  <td><?= e($row['phone'] ?? '--') ?></td>
                  <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                  <td>
                    <div class="user-actions">
                      <a href="users.php?delete=<?= $row['id'] ?>"
                         onclick="return confirm('Delete this student account? Their orders will also be removed.')">
                        <button class="delete-btn user-delete-btn">
                          <img src="images/trash.png" alt="Delete">
                        </button>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
<script src="script.js?v=<?= time() ?>"></script>
</body>
</html>
