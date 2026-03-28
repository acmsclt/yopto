<?php
/**
 * Product detail page
 * - Yotpo JS library loaded in <head>
 * - Yotpo Reviews Widget (full) embedded in page
 * - Yotpo Star Rating embedded near product title
 */

$config = require __DIR__ . '/config/yotpo.php';

// ---------- Sample product lookup (replace with DB query) ----------
$catalogue = [
    'PROD-101' => [
        'id'          => 'PROD-101',
        'name'        => 'Premium Wireless Headphones',
        'price'       => 149.99,
        'image'       => 'https://placehold.co/600x400/1a1a2e/e0e0e0?text=Headphones',
        'description' => 'Crystal-clear audio with active noise cancellation and 40-hour battery life. Perfect for work-from-home or travel.',
        'category'    => 'Electronics',
        'sku'         => 'SKU-WH-101',
    ],
    'PROD-202' => [
        'id'          => 'PROD-202',
        'name'        => 'Ergonomic Laptop Stand',
        'price'       => 59.99,
        'image'       => 'https://placehold.co/600x400/0f3460/e0e0e0?text=Laptop+Stand',
        'description' => 'Adjustable aluminium stand compatible with any laptop 11"–17". Foldable for easy portability.',
        'category'    => 'Accessories',
        'sku'         => 'SKU-LS-202',
    ],
    'PROD-303' => [
        'id'          => 'PROD-303',
        'name'        => 'Smart Fitness Tracker',
        'price'       => 89.99,
        'image'       => 'https://placehold.co/600x400/16213e/e0e0e0?text=Fitness+Tracker',
        'description' => 'Monitors heart rate, steps, sleep quality, and workouts continuously. Water-resistant, 7-day battery.',
        'category'    => 'Wearables',
        'sku'         => 'SKU-FT-303',
    ],
];

$productId = $_GET['id'] ?? 'PROD-101';
$product   = $catalogue[$productId] ?? $catalogue['PROD-101'];
$productUrl = $config['store_url'] . '/product.php?id=' . urlencode($product['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['name']) ?> – YoptoShop</title>
  <meta name="description" content="<?= htmlspecialchars($product['description']) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- ══════════════════════════════════════════════════ -->
  <!--   STEP 1 · Yotpo JavaScript Library               -->
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

<main class="container" style="padding-top: 2rem;">

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="index.php">Home</a> › <?= htmlspecialchars($product['name']) ?>
  </nav>

  <!-- Product layout -->
  <div class="product-detail">
    <div class="product-detail-image">
      <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
    </div>
    <div class="product-detail-info">
      <span class="product-badge"><?= htmlspecialchars($product['category']) ?></span>
      <h1><?= htmlspecialchars($product['name']) ?></h1>

      <!-- ══════════════════════════════════════════════════ -->
      <!--   STEP 3 · Yotpo Star Rating Widget               -->
      <!--   Place this near the product title/price         -->
      <!-- ══════════════════════════════════════════════════ -->
      <div class="yotpo bottomLine"
           data-product-id="<?= htmlspecialchars($product['id']) ?>"
           data-url="<?= htmlspecialchars($productUrl) ?>">
      </div>
      <!-- ══ End Yotpo Star Rating ══════════════════════════ -->

      <p class="price-tag">$<?= number_format($product['price'], 2) ?></p>
      <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
      <p class="product-sku">SKU: <code><?= htmlspecialchars($product['sku']) ?></code></p>

      <div class="action-row">
        <a href="checkout.php?product_id=<?= urlencode($product['id']) ?>&product_name=<?= urlencode($product['name']) ?>&price=<?= $product['price'] ?>&image=<?= urlencode($product['image']) ?>&url=<?= urlencode($productUrl) ?>"
           class="btn-primary">Buy Now</a>
        <a href="index.php" class="btn-outline">← Back to Products</a>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════ -->
  <!--   STEP 2 · Yotpo Reviews Widget (full)                           -->
  <!--   data-product-id must match what you sent to Yotpo's API        -->
  <!--   Only alphanumeric, underscore (_) and hyphen (-) are valid     -->
  <!-- ══════════════════════════════════════════════════════════════════ -->
  <section class="reviews-section">
    <h2>Customer Reviews</h2>
    <div class="yotpo yotpo-main-widget"
         data-product-id="<?= htmlspecialchars($product['id']) ?>"
         data-price="<?= $product['price'] ?>"
         data-currency="<?= htmlspecialchars($config['currency']) ?>"
         data-name="<?= htmlspecialchars($product['name']) ?>"
         data-url="<?= htmlspecialchars($productUrl) ?>"
         data-image-url="<?= htmlspecialchars($product['image']) ?>">
    </div>
  </section>
  <!-- ══ End Yotpo Reviews Widget ══════════════════════════════════════ -->

</main>

<footer class="site-footer">
  <div class="container">
    <p>YoptoShop · Yotpo Reviews v3 Integration Reference · PHP</p>
  </div>
</footer>

</body>
</html>
