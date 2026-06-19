<?php
/**
 * admin/payments.php — View All Payments
 */
require_once '../config.php';
require_once '../db.php';
require_admin();

$sql = "SELECT p.id AS pay_id, p.order_id, u.name AS user_name, p.payment_method,
               p.amount, p.payment_status, p.payment_date
        FROM payments p
        JOIN orders  o ON o.id = p.order_id
        JOIN users   u ON u.id = o.user_id
        ORDER BY p.payment_date DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Payments</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="payments-page-header">
      <div><h1>Payments</h1><p>View all payment transactions</p></div>
    </div>

    <div class="payments-search-area">
      <div class="payments-search-box">
        <img src="images/search.png" alt="Search">
        <input type="text" id="paymentSearchInput" placeholder="Search payments...">
      </div>
    </div>

    <div class="payments-table-section">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Pay ID</th><th>Order ID</th><th>Student</th>
              <th>Method</th><th>Amount</th><th>Status</th><th>Date</th>
            </tr>
          </thead>
          <tbody id="paymentsTableBody">
            <?php if (mysqli_num_rows($result) === 0): ?>
              <tr><td colspan="7" class="no-orders">No payments found</td></tr>
            <?php else: ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                  $sc = '';
                  if ($row['payment_status'] === 'Paid')   $sc = 'payment-paid';
                  if ($row['payment_status'] === 'Failed') $sc = 'payment-failed';
                ?>
                <tr>
                  <td>PAY<?= str_pad($row['pay_id'], 4, '0', STR_PAD_LEFT) ?></td>
                  <td>#<?= $row['order_id'] ?></td>
                  <td><?= e($row['user_name']) ?></td>
                  <td><?= e($row['payment_method']) ?></td>
                  <td>Rs.<?= number_format($row['amount'], 2) ?></td>
                  <td><span class="payment-status <?= $sc ?>"><?= e($row['payment_status']) ?></span></td>
                  <td><?= date('d M Y h:iA', strtotime($row['payment_date'])) ?></td>
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
<script>
  document.getElementById('paymentSearchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#paymentsTableBody tr').forEach(function(row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
</script>
</body>
</html>
