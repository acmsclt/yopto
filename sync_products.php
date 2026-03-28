<?php
/**
 * Sync Products to Yotpo
 *
 * Run this page to push your product catalogue to Yotpo's API.
 * In production you'd call YotpoClient from a cron job / queue worker.
 */

require_once __DIR__ . '/src/YotpoClient.php';
$config = require __DIR__ . '/config/yotpo.php';

$client = new YotpoClient($config);

// ── CSRF token setup ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---------- Sample products to sync (replace with DB query) ----------
$products = [
    [
        'external_id' => 'PROD-101',
        'name'        => 'Premium Wireless Headphones',
        'url'         => $config['store_url'] . '/product.php?id=PROD-101',
        'image_url'   => 'https://placehold.co/600x400/1a1a2e/e0e0e0?text=Headphones',
        'price'       => 149.99,
        'currency'    => $config['currency'],
        'description' => 'Crystal-clear audio with 40-hour battery life.',
        'sku'         => 'SKU-WH-101',
        'brand'       => 'YoptoShop',
        'category'    => 'Electronics',
    ],
    [
        'external_id' => 'PROD-202',
        'name'        => 'Ergonomic Laptop Stand',
        'url'         => $config['store_url'] . '/product.php?id=PROD-202',
        'image_url'   => 'https://placehold.co/600x400/0f3460/e0e0e0?text=Laptop+Stand',
        'price'       => 59.99,
        'currency'    => $config['currency'],
        'description' => 'Adjustable aluminium stand compatible with any laptop.',
        'sku'         => 'SKU-LS-202',
        'brand'       => 'YoptoShop',
        'category'    => 'Accessories',
    ],
    [
        'external_id' => 'PROD-303',
        'name'        => 'Smart Fitness Tracker',
        'url'         => $config['store_url'] . '/product.php?id=PROD-303',
        'image_url'   => 'https://placehold.co/600x400/16213e/e0e0e0?text=Fitness+Tracker',
        'price'       => 89.99,
        'currency'    => $config['currency'],
        'description' => 'Monitors heart rate, sleep quality, and workouts non-stop.',
        'sku'         => 'SKU-FT-303',
        'brand'       => 'YoptoShop',
        'category'    => 'Wearables',
    ],
];

$results = [];
$hasError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check — must be first
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
    foreach ($products as $product) {
        try {
            $response = $client->upsertProduct($product);
            $results[] = ['product' => $product['name'], 'status' => 'success', 'response' => $response];
        } catch (Exception $e) {
            $hasError = true;
            $results[] = ['product' => $product['name'], 'status' => 'error', 'message' => $e->getMessage()];
        }
    }
    // Regenerate CSRF token after use
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Also attempt token generation to display the auth demo
$tokenStatus = null;
$tokenError  = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_token'])) {
    try {
        $token = $client->refreshUToken();
        $tokenStatus = substr($token, 0, 8) . '…[redacted]';
    } catch (Exception $e) {
        $tokenError = $e->getMessage();
    }
    // Regenerate CSRF token after use
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sync Products – YoptoShop</title>
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
      <a href="sync_products.php" class="active">Sync Products</a>
      <a href="checkout.php">Demo Checkout</a>
    </nav>
  </div>
</header>

<main class="container" style="padding-top: 2rem;">
  <h1>Backend Sync · Products</h1>
  <p class="subtitle">Push your product catalogue to Yotpo so review emails include the right product information.</p>

  <!-- ── Step 1 : UToken ──────────────────────────────────────────────── -->
  <div class="info-panel">
    <h2>Step 1 – Generate a UToken</h2>
    <p>The UToken authenticates all subsequent API requests. The <code>YotpoClient</code> class caches it and auto-refreshes on expiry.</p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <button type="submit" name="get_token" value="1" class="btn-primary">Generate UToken</button>
    </form>
    <?php if ($tokenStatus): ?>
      <div class="alert alert-success">✅ Token obtained: <code><?= htmlspecialchars($tokenStatus) ?></code> (cached on disk)</div>
    <?php elseif ($tokenError): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($tokenError) ?></div>
    <?php endif; ?>

    <details class="code-block">
      <summary>View PHP code</summary>
      <pre><code>$config = require 'config/yotpo.php';
$client = new YotpoClient($config);

// Refresh token (also auto-called on first API request)
$token = $client->refreshUToken();
</code></pre>
    </details>
  </div>

  <!-- ── Step 2 : Sync Products ───────────────────────────────────────── -->
  <div class="info-panel">
    <h2>Step 2 – Send Products to Yotpo</h2>
    <p>Sends <strong><?= count($products) ?> products</strong> from the catalogue to Yotpo using a PUT (upsert) call.</p>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <button type="submit" name="sync" value="1" class="btn-primary">Sync Products Now</button>
    </form>

    <?php if (!empty($results)): ?>
      <div style="margin-top:1rem;">
        <?php foreach ($results as $r): ?>
          <?php if ($r['status'] === 'success'): ?>
            <div class="alert alert-success">✅ <strong><?= htmlspecialchars($r['product']) ?></strong> synced successfully</div>
          <?php else: ?>
            <div class="alert alert-error">❌ <strong><?= htmlspecialchars($r['product']) ?></strong> – <?= htmlspecialchars($r['message']) ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <details class="code-block">
      <summary>View PHP code</summary>
      <pre><code>$client->upsertProduct([
    'external_id' => 'PROD-101',      // Must be unique & stable
    'name'        => 'My Product',
    'url'         => 'https://mystore.com/product/101',
    'image_url'   => 'https://mystore.com/img/101.jpg',
    'price'       => 149.99,
    'currency'    => 'USD',
    'description' => 'Product description here.',
    'sku'         => 'SKU-101',
    'brand'       => 'MyBrand',
    'category'    => 'Electronics',
]);
</code></pre>
    </details>
  </div>

  <div class="info-panel" style="background: var(--card-bg-alt);">
    <h3>📌 Products to Sync</h3>
    <table class="data-table">
      <thead>
        <tr><th>ID</th><th>Name</th><th>Price</th><th>SKU</th><th>Category</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td><code><?= htmlspecialchars($p['external_id']) ?></code></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td>$<?= number_format($p['price'], 2) ?></td>
          <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
          <td><?= htmlspecialchars($p['category']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>

<footer class="site-footer">
  <div class="container">
    <p>YoptoShop · Yotpo Reviews v3 Integration Reference · PHP</p>
  </div>
</footer>
</body>
</html>
