<?php
/**
 * user/Html/MyOrders.php — Student Order History
 */
require_once '../../config.php';
require_once '../../db.php';
require_student();

$user_id = $_SESSION['user_id'];
$filter  = $_GET['status'] ?? 'All';
$allowed = ['All','Pending','Processing','Ready','Completed'];
if (!in_array($filter, $allowed)) $filter = 'All';

if ($filter === 'All') {
    $stmt = mysqli_prepare($conn,
        "SELECT o.id, o.total_amount, o.order_status, o.payment_method, o.created_at
         FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT o.id, o.total_amount, o.order_status, o.payment_method, o.created_at
         FROM orders o WHERE o.user_id = ? AND o.order_status = ? ORDER BY o.created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, 'is', $user_id, $filter);
}
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Orders</title>
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<div class="dashboard-page">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <header class="dashboard-header">
      <div><h1>My Orders</h1><p>Your complete order history</p></div>
      <div class="header-actions">
        <button class="icon-btn" type="button"><i class="fa-regular fa-bell"></i></button>
        <div class="user-chip">
          <div class="avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
          <span><?= e($_SESSION['name']) ?></span>
        </div>
      </div>
    </header>

    <!-- Filter Tabs -->
    <div class="orders-filter-bar">
      <?php foreach (['All','Pending','Processing','Ready','Completed'] as $tab): ?>
        <a href="MyOrders.php?status=<?= $tab ?>" style="text-decoration:none;">
          <button class="filter-tab <?= $filter===$tab?'active':'' ?>"><?= $tab ?></button>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="orders-section">
      <?php if (mysqli_num_rows($orders) === 0): ?>
        <div class="orders-empty">
          <i class="fa-solid fa-bag-shopping"></i>
          <p>No orders found<?= $filter!=='All' ? ' for status: '.$filter : '' ?>.</p>
          <a href="Menu.php" style="color:#7047f2;font-weight:700;">Browse Menu</a>
        </div>
      <?php else: ?>
        <?php while ($ord = mysqli_fetch_assoc($orders)):
          // Fetch items for this order
          $items_stmt = mysqli_prepare($conn,
            "SELECT f.food_name, f.image, oi.quantity, oi.subtotal
             FROM order_items oi JOIN food_items f ON f.id = oi.food_item_id
             WHERE oi.order_id = ?"
          );
          mysqli_stmt_bind_param($items_stmt, 'i', $ord['id']);
          mysqli_stmt_execute($items_stmt);
          $items = mysqli_stmt_get_result($items_stmt);

          // Badge class
          $badge = 'badge-pending';
          if ($ord['order_status']==='Processing') $badge = 'badge-processing';
          if ($ord['order_status']==='Ready')      $badge = 'badge-ready';
          if ($ord['order_status']==='Completed')  $badge = 'badge-completed';
        ?>
          <article class="order-card">
            <div class="order-card-header">
              <div class="order-id-group">
                <span class="order-id">Order #<?= $ord['id'] ?></span>
                <span class="order-date">
                  <i class="fa-regular fa-calendar"></i>
                  <?= date('d M Y · h:i A', strtotime($ord['created_at'])) ?>
                </span>
              </div>
              <span class="order-status-badge <?= $badge ?>"><?= e($ord['order_status']) ?></span>
            </div>

            <div class="order-card-body">
              <div class="order-items-list">
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                  <div class="order-item-row">
                    <img src="../Images/<?= e($item['image']) ?>" class="order-thumb"
                         onerror="this.src='../Images/food.jpg'" alt="<?= e($item['food_name']) ?>">
                    <div class="order-item-info">
                      <span class="order-item-name"><?= e($item['food_name']) ?></span>
                      <span class="order-item-qty">Qty: <?= $item['quantity'] ?></span>
                    </div>
                    <span class="order-item-price">Rs.<?= number_format($item['subtotal'], 2) ?></span>
                  </div>
                <?php endwhile; ?>
              </div>

              <div class="order-card-footer">
                <div class="order-total">
                  <span>Total Amount</span>
                  <strong>Rs.<?= number_format($ord['total_amount'], 2) ?></strong>
                </div>
                <div class="order-card-actions">
                  <a href="TrackOrders.php?order_id=<?= $ord['id'] ?>" class="btn-view-details">
                    <i class="fa-solid fa-eye"></i> View Details
                  </a>
                </div>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
