<?php
/**
 * Demo Checkout page
 * After a successful order we:
 *   1. Send the order to Yotpo (createOrder)
 *   2. Redirect to the order confirmation page (where conversion tracking fires)
 */

// Configure session cookie BEFORE session_start()
// Required for HTTPS environments — without Secure flag, browsers drop the cookie.
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('yotposhop');
    session_start();
}

require_once __DIR__ . '/src/YotpoClient.php';
$config = require __DIR__ . '/config/yotpo.php';
$client = new YotpoClient($config);

// ── Product catalogue (single source of truth — never trust $_GET for prices) ──
$catalogue = [
    'PROD-101' => [
        'id'          => 'PROD-101',
        'name'        => 'Premium Wireless Headphones',
        'price'       => 149.99,
        'image'       => 'https://placehold.co/600x400/1a1a2e/e0e0e0?text=Headphones',
        'description' => 'Crystal-clear audio with 40-hour battery life.',
        'category'    => 'Electronics',
        'sku'         => 'SKU-WH-101',
    ],
    'PROD-202' => [
        'id'          => 'PROD-202',
        'name'        => 'Ergonomic Laptop Stand',
        'price'       => 59.99,
        'image'       => 'https://placehold.co/600x400/0f3460/e0e0e0?text=Laptop+Stand',
        'description' => 'Adjustable aluminium stand compatible with any laptop.',
        'category'    => 'Accessories',
        'sku'         => 'SKU-LS-202',
    ],
    'PROD-303' => [
        'id'          => 'PROD-303',
        'name'        => 'Smart Fitness Tracker',
        'price'       => 89.99,
        'image'       => 'https://placehold.co/600x400/16213e/e0e0e0?text=Fitness+Tracker',
        'description' => 'Monitors heart rate, sleep quality, and workouts non-stop.',
        'category'    => 'Wearables',
        'sku'         => 'SKU-FT-303',
    ],
];

// Look up product from catalogue — fall back to first product if ID unknown
$productId  = $_GET['product_id'] ?? 'PROD-101';
$product    = $catalogue[$productId] ?? reset($catalogue);

$productUrl  = $config['store_url'] . '/product.php?id=' . urlencode($product['id']);
$imageUrl    = $product['image'];
$productName = $product['name'];
$price       = $product['price'];

// ── Ensure a CSRF token exists in the session ─────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check — must be first
    // If the token is missing or doesn't match, redirect back to the form.
    // This can happen if the session expired between page load and submit.
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['csrf_error'] = 'Your session expired. Please try again.';
        header('Location: checkout.php?product_id=' . urlencode($product['id']));
        exit;
    }

    $email      = trim($_POST['email'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $qty        = max(1, min(99, (int) ($_POST['qty'] ?? 1)));   // bounded 1–99
    $orderTotal = $price * $qty;

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }

    if (empty($errors)) {
        // Generate a demo order ID (in production use your DB auto-increment)
        $orderId = 'ORD-' . time();

        try {
            // ── Step 1: Auto-sync the product to Yotpo first ─────────────────
            // Yotpo requires the product to exist before an order can reference it.
            $client->upsertProduct([
                'external_id' => $product['id'],
                'name'        => $product['name'],
                'url'         => $productUrl,
                'image_url'   => $imageUrl,
                'price'       => $product['price'],
                'currency'    => $config['currency'],
                'description' => $product['description'],
                'sku'         => $product['sku'],
                'category'    => $product['category'],
            ]);

            // ── Step 2: Create order in Yotpo (fulfillment included) ─────────
            $client->createOrder([
                'external_id'        => $orderId,
                'customer_email'     => $email,
                'customer_name'      => $name,
                'payment_status'     => 'paid',
                'fulfillment_status' => 'success',
                'fulfillment_date'   => null,   // null = now (auto-formatted by YotpoClient)
                'currency'           => $config['currency'],
                'total_price'        => $orderTotal,
                'line_items'         => [
                    [
                        'external_product_id' => $product['id'],
                        'name'                => $product['name'],
                        'price'               => $product['price'],
                        'quantity'            => $qty,
                        'image_url'           => $imageUrl,
                        'product_url'         => $productUrl,
                    ],
                ],
            ]);

            // Regenerate CSRF token after successful use
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Redirect to confirmation page with conversion tracking
            header("Location: order_confirmation.php?"
                . "order_id=" . urlencode($orderId)
                . "&amount="  . urlencode($orderTotal)
                . "&currency=" . urlencode($config['currency'])
                . "&email="   . urlencode($email)
                . "&name="    . urlencode($name)
            );
            exit;

        } catch (Exception $e) {
            $errors[] = 'Yotpo API error: ' . $e->getMessage();
        }

    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout – YoptoShop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="index.php" class="logo">🛒 YoptoShop</a>
    <nav>
      <a href="index.php">Products</a>
      <a href="sync_products.php">Sync Products</a>
      <a href="checkout.php" class="active">Demo Checkout</a>
    </nav>
  </div>
</header>

<main class="container" style="padding-top: 2rem; max-width: 820px;">
  <h1>Checkout</h1>
  <p class="subtitle">Completing this form will send a demo order to Yotpo and trigger a review-request email.</p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>

  <?php if (!empty($_SESSION['csrf_error'])): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($_SESSION['csrf_error']) ?></div>
    <?php unset($_SESSION['csrf_error']); ?>
  <?php endif; ?>

  <div class="checkout-layout">

    <!-- Order Summary -->
    <div class="order-summary">
      <h2>Order Summary</h2>
      <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($productName) ?>" style="width:100%; border-radius:8px; margin-bottom:1rem;">
      <p class="summary-name"><?= htmlspecialchars($productName) ?></p>
      <p class="summary-id">Product ID: <code><?= htmlspecialchars($product['id']) ?></code></p>
      <p class="summary-price">$<?= number_format($price, 2) ?> each</p>
      <hr>
      <p class="summary-note">Yotpo order will be sent with <code>fulfillment_status: success</code> so a review email is immediately scheduled.</p>
    </div>

    <!-- Checkout Form -->
    <form method="POST" class="checkout-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" placeholder="Jane Doe"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" placeholder="jane@example.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

      <label for="qty">Quantity</label>
      <input type="number" id="qty" name="qty" value="<?= (int)($_POST['qty'] ?? 1) ?>" min="1" max="99">

      <button type="submit" class="btn-primary" style="width:100%;margin-top:1rem;">
        Place Order &amp; Send to Yotpo ›
      </button>
    </form>
  </div>

  <div class="info-panel" style="margin-top:2rem;">
    <h3>What happens behind the scenes?</h3>
    <ol class="step-list">
      <li><strong>Create Order</strong> – <code>POST /core/v3/stores/{app_key}/orders</code> with customer details and line items.</li>
      <li><strong>Fulfill Order</strong> – <code>PATCH /core/v3/stores/{app_key}/orders/{orderId}</code> with <code>fulfillment_status: success</code>.</li>
      <li>Yotpo schedules a review-request email to the customer a few days after the fulfilment date.</li>
    </ol>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <p>YoptoShop · Yotpo Reviews v3 Integration Reference · PHP</p>
  </div>
</footer>
</body>
</html>
