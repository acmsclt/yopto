<?php
/**
 * Order Confirmation page
 * - Shows order summary to the customer
 * - Fires Yotpo Conversion Tracking (Step 5 of the docs)
 */

$config = require __DIR__ . '/config/yotpo.php';

$orderId  = htmlspecialchars($_GET['order_id']  ?? 'ORD-DEMO');
$amount   = number_format((float) ($_GET['amount'] ?? 0), 2, '.', '');
$currency = htmlspecialchars($_GET['currency'] ?? $config['currency']);
$email    = htmlspecialchars($_GET['email']    ?? '');
$name     = htmlspecialchars($_GET['name']     ?? 'Customer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed – YoptoShop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- ══════════════════════════════════════════════════════════════════ -->
  <!--   STEP 5 · Yotpo Conversion Tracking                             -->
  <!--   Must be placed AFTER every successful checkout.                -->
  <!--   Load the Yotpo widget.js first, then set yotpoTrackConversion. -->
  <!-- ══════════════════════════════════════════════════════════════════ -->
  <script type="text/javascript">
    (function e() {
      var e = document.createElement("script");
      e.type = "text/javascript";
      e.async = true;
      e.src = "//staticw2.yotpo.com/<?= htmlspecialchars($config['app_key']) ?>/widget.js";
      var t = document.getElementsByTagName("script")[0];
      t.parentNode.insertBefore(e, t);
    })();
  </script>
  <script>
    yotpoTrackConversionData = {
      orderId:       "<?= $orderId ?>",
      orderAmount:   "<?= $amount ?>",
      orderCurrency: "<?= $currency ?>"
    };
  </script>
  <!-- Fallback for no-JS environments -->
  <noscript>
    <img src="//api.yotpo.com/conversion_tracking.gif?app_key=<?= urlencode($config['app_key']) ?>&amp;order_id=<?= urlencode($orderId) ?>&amp;order_amount=<?= urlencode($amount) ?>&amp;order_currency=<?= urlencode($currency) ?>"
         width="1" height="1" alt="">
  </noscript>
  <!-- ══ End Yotpo Conversion Tracking ════════════════════════════════ -->

  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="index.php" class="logo">🛒 YoptoShop</a>
    <nav>
      <a href="index.php">Products</a>
      <a href="sync_products.php">Sync Products</a>
      <a href="checkout.php">Demo Checkout</a>
    </nav>
  </div>
</header>

<main class="container" style="padding-top:3rem; max-width:680px; text-align:center;">

  <div class="confirm-icon">🎉</div>
  <h1>Order Confirmed!</h1>
  <p class="subtitle">Thank you, <strong><?= $name ?></strong>. Your order has been placed and sent to Yotpo.</p>

  <div class="confirm-card">
    <div class="confirm-row"><span>Order ID</span><strong><code><?= $orderId ?></code></strong></div>
    <div class="confirm-row"><span>Email</span><strong><?= $email ?></strong></div>
    <div class="confirm-row"><span>Total</span><strong><?= $currency ?> <?= $amount ?></strong></div>
    <div class="confirm-row"><span>Status</span><strong class="badge-success">Fulfilled ✓</strong></div>
  </div>

  <div class="info-panel" style="text-align:left; margin-top:2rem;">
    <h3>What just happened?</h3>
    <ol class="step-list">
      <li><strong>Order created</strong> in Yotpo with customer details and line items.</li>
      <li><strong>Order fulfilled</strong> – Yotpo will send a review-request email a few days after today.</li>
      <li><strong>Conversion Tracking fired</strong> – the Yotpo pixel loaded in this page's <code>&lt;head&gt;</code> records the purchase.</li>
    </ol>
    <p>Check your <a href="https://reviews.yotpo.com/" target="_blank">Yotpo dashboard</a> to see the order appear under <em>Reviews › Pending Requests</em>.</p>
  </div>

  <a href="index.php" class="btn-primary" style="display:inline-block;margin-top:1.5rem;">← Continue Shopping</a>

</main>

<footer class="site-footer">
  <div class="container">
    <p>YoptoShop · Yotpo Reviews v3 Integration Reference · PHP</p>
  </div>
</footer>
</body>
</html>
