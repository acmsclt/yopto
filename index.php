<?php
/**
 * Products page – shows product listing with Yotpo Star Ratings.
 *
 * In a real app these would come from your database.
 * Replace YOUR_YOTPO_APP_KEY with your actual key.
 */

$config = require __DIR__ . '/config/yotpo.php';

// ---------- Sample product catalogue (replace with DB query) ----------
$products = [
    [
        'id'          => 'PROD-101',
        'name'        => 'Premium Wireless Headphones',
        'price'       => 149.99,
        'image'       => 'https://placehold.co/400x300/1a1a2e/e0e0e0?text=Headphones',
        'url'         => $config['store_url'] . '/product.php?id=PROD-101',
        'description' => 'Crystal-clear audio with 40-hour battery life.',
        'category'    => 'Electronics',
    ],
    [
        'id'          => 'PROD-202',
        'name'        => 'Ergonomic Laptop Stand',
        'price'       => 59.99,
        'image'       => 'https://placehold.co/400x300/0f3460/e0e0e0?text=Laptop+Stand',
        'url'         => $config['store_url'] . '/product.php?id=PROD-202',
        'description' => 'Adjustable aluminium stand for any laptop.',
        'category'    => 'Accessories',
    ],
    [
        'id'          => 'PROD-303',
        'name'        => 'Smart Fitness Tracker',
        'price'       => 89.99,
        'image'       => 'https://placehold.co/400x300/16213e/e0e0e0?text=Fitness+Tracker',
        'url'         => $config['store_url'] . '/product.php?id=PROD-303',
        'description' => 'Track heart rate, sleep, and workouts 24/7.',
        'category'    => 'Wearables',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YoptoShop – Products</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- ══════════════════════════════════════════════════ -->
  <!--   STEP 1 · Yotpo JavaScript Library               -->
  <!--   Replace YOUR_YOTPO_APP_KEY with your real key   -->
  <!-- ══════════════════════════════════════════════════ -->
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

  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── Navigation ─────────────────────────────────────────────────────── -->
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

<!-- ── Hero ───────────────────────────────────────────────────────────── -->
<section class="hero">
  <div class="container">
    <h1>Yotpo Reviews Integration</h1>
    <p>A complete PHP reference implementation of Yotpo Reviews v3 for your e-commerce store.</p>
  </div>
</section>

<!-- ── Products Grid ──────────────────────────────────────────────────── -->
<main class="container">
  <h2 class="section-title">Our Products</h2>

  <div class="products-grid">
    <?php foreach ($products as $product): ?>
    <article class="product-card" id="product-<?= htmlspecialchars($product['id']) ?>">
      <a href="product.php?id=<?= urlencode($product['id']) ?>">
        <img src="<?= htmlspecialchars($product['image']) ?>"
             alt="<?= htmlspecialchars($product['name']) ?>"
             class="product-image">
      </a>
      <div class="product-info">
        <h3 class="product-name">
          <a href="product.php?id=<?= urlencode($product['id']) ?>">
            <?= htmlspecialchars($product['name']) ?>
          </a>
        </h3>

        <!-- ══════════════════════════════════════════════════ -->
        <!--   STEP 3 · Yotpo Star Rating Widget               -->
        <!--   Place this snippet near product title/price     -->
        <!-- ══════════════════════════════════════════════════ -->
        <div class="yotpo bottomLine"
             data-product-id="<?= htmlspecialchars($product['id']) ?>"
             data-url="<?= htmlspecialchars($product['url']) ?>">
        </div>
        <!-- ══ End Yotpo Star Rating ══════════════════════════ -->

        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
        <div class="product-footer">
          <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
          <a href="product.php?id=<?= urlencode($product['id']) ?>" class="btn-primary">View Details</a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <!-- ── Integration Info Cards ───────────────────────────────────────── -->
  <section class="info-cards">
    <h2 class="section-title" style="margin-top:3rem;">Integration Steps</h2>
    <div class="cards-row">
      <div class="info-card">
        <div class="card-icon">🔑</div>
        <h3>1. Authentication</h3>
        <p>Generate a UToken using your App Key &amp; API Secret. The token is auto-cached in <code>YotpoClient</code>.</p>
        <a href="sync_products.php" class="btn-outline">See Auth Demo →</a>
      </div>
      <div class="info-card">
        <div class="card-icon">📦</div>
        <h3>2. Sync Products</h3>
        <p>Push your product catalogue to Yotpo so review-request emails contain the right product info.</p>
        <a href="sync_products.php" class="btn-outline">Sync Now →</a>
      </div>
      <div class="info-card">
        <div class="card-icon">🛍️</div>
        <h3>3. Sync Orders</h3>
        <p>After fulfilment, send the order to Yotpo. Customers automatically receive a review request.</p>
        <a href="checkout.php" class="btn-outline">Demo Order →</a>
      </div>
      <div class="info-card">
        <div class="card-icon">⭐</div>
        <h3>4. Display Widgets</h3>
        <p>Embed the Reviews Widget &amp; Star Rating on product pages – zero back-end calls needed.</p>
        <a href="product.php?id=PROD-101" class="btn-outline">See Widget →</a>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container">
    <p>YoptoShop · Yotpo Reviews v3 Integration Reference · PHP</p>
  </div>
</footer>

</body>
</html>
