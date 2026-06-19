<?php
/**
 * user/Html/Cart.php — Cart View + Place Order
 */
require_once '../../config.php';
require_once '../../db.php';
require_student();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// ── CART ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $food_id = (int)($_POST['food_id'] ?? 0);

    // Increase quantity
    if ($action === 'increase' && isset($_SESSION['cart'][$food_id])) {
        $_SESSION['cart'][$food_id]['qty']++;
    }
    // Decrease quantity
    if ($action === 'decrease' && isset($_SESSION['cart'][$food_id])) {
        $_SESSION['cart'][$food_id]['qty']--;
        if ($_SESSION['cart'][$food_id]['qty'] <= 0) {
            unset($_SESSION['cart'][$food_id]);
        }
    }
    // Remove item
    if ($action === 'remove') {
        unset($_SESSION['cart'][$food_id]);
    }
    // ── PLACE ORDER ───────────────────────────────────────────
    if ($action === 'place_order' && !empty($_SESSION['cart'])) {
        $payment_method = $_POST['payment'] === 'card' ? 'Card' : 'Cash';
        $user_id        = $_SESSION['user_id'];

        // Calculate total
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['qty'];
        }
        $service_fee = 20.00;
        $grand_total = $total + $service_fee;

        // Insert order
        $ord_stmt = mysqli_prepare($conn,
            'INSERT INTO orders (user_id, total_amount, payment_method, order_status) VALUES (?,?,?,"Pending")'
        );
        mysqli_stmt_bind_param($ord_stmt, 'ids', $user_id, $grand_total, $payment_method);
        mysqli_stmt_execute($ord_stmt);
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($ord_stmt);

        // Insert order items + deduct inventory
        foreach ($_SESSION['cart'] as $item) {
            $subtotal   = $item['price'] * $item['qty'];
            $food_id_it = $item['id'];
            $qty        = $item['qty'];
            $unit_price = $item['price'];

            $oi = mysqli_prepare($conn,
                'INSERT INTO order_items (order_id, food_item_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)'
            );
            mysqli_stmt_bind_param($oi, 'iiidd', $order_id, $food_id_it, $qty, $unit_price, $subtotal);
            mysqli_stmt_execute($oi);
            mysqli_stmt_close($oi);

            // Deduct from inventory
            $inv = mysqli_prepare($conn,
                'UPDATE inventory SET quantity = GREATEST(0, quantity - ?) WHERE food_item_id = ?'
            );
            mysqli_stmt_bind_param($inv, 'ii', $qty, $food_id_it);
            mysqli_stmt_execute($inv);
            mysqli_stmt_close($inv);
        }

        // Insert payment record
        $pay_status = $payment_method === 'Card' ? 'Paid' : 'Pending';
        $pay = mysqli_prepare($conn,
            'INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (?,?,?,?)'
        );
        mysqli_stmt_bind_param($pay, 'isds', $order_id, $payment_method, $grand_total, $pay_status);
        mysqli_stmt_execute($pay);
        mysqli_stmt_close($pay);

        // Clear cart
        $_SESSION['cart'] = [];
        header('Location: TrackOrders.php?order_id=' . $order_id);
        exit;
    }

    header('Location: Cart.php');
    exit;
}

// ── Compute totals ────────────────────────────────────────────
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$service_fee = 20.00;
$total       = $subtotal + $service_fee;
$item_count  = array_sum(array_column($_SESSION['cart'], 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Your Cart</title>
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<div class="dashboard-page">
  <?php include 'includes/sidebar.php'; ?>

  <main class="cart-main-content">
    <div class="cart-inner">
      <!-- Cart Items -->
      <section class="cart-panel">
        <div class="cart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div>
            <h1>Your Cart <span>(<?= $item_count ?> Item<?= $item_count != 1 ? 's' : '' ?>)</span></h1>
            <p>Review your selected meals before placing the order.</p>
          </div>
          <div class="header-actions">
            <button class="icon-btn" type="button"><i class="fa-regular fa-bell"></i></button>
            <div class="user-chip">
              <div class="avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
              <span><?= e($_SESSION['name']) ?></span>
            </div>
          </div>
        </div>

        <div class="cart-items">
          <?php if (empty($_SESSION['cart'])): ?>
            <p style="color:#888;padding:20px 0;">Your cart is empty. <a href="Menu.php">Browse Menu</a></p>
          <?php else: ?>
            <?php foreach ($_SESSION['cart'] as $fid => $item): ?>
              <article class="cart-item">
                <img src="../Images/<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>"
                     onerror="this.src='../Images/food.jpg'" />
                <div class="item-info">
                  <h2><?= e($item['name']) ?></h2>
                  <p class="price">Rs.<?= number_format($item['price'], 2) ?></p>
                </div>

                <!-- Quantity Controls -->
                <div class="qty-box">
                  <form method="POST" style="display:contents;">
                    <input type="hidden" name="food_id" value="<?= $fid ?>">
                    <input type="hidden" name="action"  value="decrease">
                    <button class="minus-btn" type="submit">-</button>
                  </form>
                  <span class="quantity"><?= $item['qty'] ?></span>
                  <form method="POST" style="display:contents;">
                    <input type="hidden" name="food_id" value="<?= $fid ?>">
                    <input type="hidden" name="action"  value="increase">
                    <button class="plus-btn" type="submit">+</button>
                  </form>
                </div>

                <!-- Remove -->
                <form method="POST">
                  <input type="hidden" name="food_id" value="<?= $fid ?>">
                  <input type="hidden" name="action"  value="remove">
                  <button class="delete-btn" type="submit"><i class="fa-regular fa-trash-can"></i></button>
                </form>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <a href="Menu.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to menu</a>
      </section>

      <!-- Checkout Panel -->
      <aside class="checkout-panel">
        <section class="summary-box">
          <h2>Order Summary</h2>
          <div class="summary-card">
            <div class="summary-row"><span>Sub Total</span><strong>Rs.<?= number_format($subtotal, 2) ?></strong></div>
            <div class="summary-row"><span>Service Fee</span><strong>Rs.<?= number_format($service_fee, 2) ?></strong></div>
            <div class="summary-row total"><span>Total</span><strong>Rs.<?= number_format($total, 2) ?></strong></div>
          </div>
        </section>

        <section class="payment-box">
          <h2>Payment Method</h2>
          <form method="POST" action="Cart.php" id="orderForm">
            <input type="hidden" name="action" value="place_order">
            <label class="payment-option">
              <input type="radio" name="payment" value="cash" />
              <span class="radio-dot"></span><strong>Cash on pickup</strong>
            </label>
            <label class="payment-option">
              <input type="radio" name="payment" value="card" checked />
              <span class="radio-dot"></span><strong>Card payment</strong>
            </label>
          </form>
        </section>

        <?php if (!empty($_SESSION['cart'])): ?>
          <button class="place-order-btn" type="submit" form="orderForm">Place Order</button>
        <?php else: ?>
          <button class="place-order-btn" type="button" disabled style="opacity:.5;cursor:not-allowed;">Place Order</button>
        <?php endif; ?>
      </aside>
    </div>
  </main>
</div>
</body>
</html>
