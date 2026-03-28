# Yotpo Reviews v3 – PHP Integration Reference

A self-contained PHP example project that demonstrates a full Yotpo Reviews v3
integration for a custom / generic e-commerce store.

---

## 📁 Project Structure

```
yopto/
├── config/
│   └── yotpo.php              ← Your App Key, API Secret and store settings
├── src/
│   └── YotpoClient.php        ← PHP API client (auth + products + orders)
├── assets/
│   └── css/style.css          ← UI styles
├── index.php                  ← Product listing – star ratings widget
├── product.php                ← Product detail – full reviews widget
├── sync_products.php          ← Admin: push products to Yotpo API
├── checkout.php               ← Demo checkout – creates & fulfils order in Yotpo
└── order_confirmation.php     ← Order confirmed – conversion tracking pixel
```

---

## 🔑 Configuration (Required first step)

1. Log in to your Yotpo dashboard and locate your **App Key** and **API Secret**.
   → https://support.yotpo.com/v1/docs/finding-your-yotpo-app-key-and-secret-key

2. Open `config/yotpo.php` and replace the placeholder values:

```php
'app_key'    => 'YOUR_YOTPO_APP_KEY',
'api_secret' => 'YOUR_YOTPO_API_SECRET',
'store_url'  => 'http://localhost/yopto',   // your site's base URL
```

---

## ▶️ Running Locally

This project requires **PHP 8.0+** and the **cURL** extension.

```bash
# From inside the project directory:
php -S localhost:8080
```

Then open http://localhost:8080 in your browser.

---

## 📋 Integration Checklist

| Step | What | File |
|------|------|------|
| 1 | **Generate UToken** (auto-cached) | `src/YotpoClient.php` |
| 2 | **Sync products** to Yotpo | `sync_products.php` |
| 3a | **Create order** in Yotpo after checkout | `checkout.php` |
| 3b | **Fulfill order** so review email is triggered | `checkout.php` |
| 4a | **JS widget library** in `<head>` | `index.php` / `product.php` |
| 4b | **Star Rating widget** near product title | `index.php` / `product.php` |
| 4c | **Full Reviews widget** on product detail | `product.php` |
| 5  | **Conversion Tracking** on confirmation page | `order_confirmation.php` |

---

## 🔌 Yotpo API Endpoints Used

| Action | Method | Endpoint |
|--------|--------|----------|
| Auth – get UToken | POST | `https://api.yotpo.com/oauth/token` |
| Upsert product | PUT | `https://api.yotpo.com/core/v3/stores/{appKey}/products` |
| Create order | POST | `https://api.yotpo.com/core/v3/stores/{appKey}/orders` |
| Fulfill order | PATCH | `https://api.yotpo.com/core/v3/stores/{appKey}/orders/{orderId}` |

---

## 📦 YotpoClient Usage

```php
require_once 'src/YotpoClient.php';
$config = require 'config/yotpo.php';
$client = new YotpoClient($config);

// Push a product
$client->upsertProduct([
    'external_id' => 'PROD-101',
    'name'        => 'My Product',
    'url'         => 'https://mystore.com/product/101',
    'image_url'   => 'https://mystore.com/img/101.jpg',
    'price'       => 49.99,
    'currency'    => 'USD',
    'description' => 'A great product.',
    'sku'         => 'SKU-101',
]);

// Create + fulfill an order
$client->createOrder([
    'external_id'    => 'ORD-9999',
    'customer_email' => 'jane@example.com',
    'customer_name'  => 'Jane Doe',
    'total_price'    => 49.99,
    'currency'       => 'USD',
    'line_items'     => [[
        'external_product_id' => 'PROD-101',
        'name'                => 'My Product',
        'price'               => 49.99,
        'quantity'            => 1,
    ]],
]);
$client->fulfillOrder('ORD-9999', '2024-03-01');
```

---

## 🔗 Useful Links

- [Yotpo Core API Reference](https://core-api.yotpo.com/reference/welcome)
- [Generic Platform Installation Guide](https://support.yotpo.com/docs/generic-other-platforms-installing-yotpo-reviews-v3)
- [Finding your App Key & Secret](https://support.yotpo.com/v1/docs/finding-your-yotpo-app-key-and-secret-key)
- [Yotpo Dashboard](https://reviews.yotpo.com/)
