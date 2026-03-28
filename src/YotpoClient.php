<?php
/**
 * YotpoClient – a lightweight PHP wrapper for the Yotpo Core API v3.
 *
 * Covers:
 *  - UToken authentication
 *  - Sending products
 *  - Sending orders (with fulfillment status)
 */

class YotpoClient
{
    private string $appKey;
    private string $apiSecret;
    private string $apiBase;
    private string $authUrl;
    private ?string $uToken = null;

    // Simple file-based token cache
    private string $tokenCacheFile;

    // Set YOTPO_DEBUG=true in config to enable request logging
    private bool $debug;

    public function __construct(array $config)
    {
        $this->appKey    = $config['app_key'];
        $this->apiSecret = $config['api_secret'];
        $this->apiBase   = rtrim($config['api_base'], '/');
        $this->authUrl   = $config['auth_url'];
        $this->tokenCacheFile = sys_get_temp_dir() . '/yotpo_utoken_' . md5($this->appKey) . '.json';
        $this->debug     = !empty($config['debug']);
    }

    // ------------------------------------------------------------------ //
    //  Authentication
    // ------------------------------------------------------------------ //

    /**
     * Returns a valid UToken, renewing it if expired or missing.
     */
    public function getUToken(): string
    {
        // Try cache first
        if (file_exists($this->tokenCacheFile)) {
            $cached = json_decode(file_get_contents($this->tokenCacheFile), true);
            if (!empty($cached['token']) && $cached['expires_at'] > time()) {
                return $this->uToken = $cached['token'];
            }
        }

        return $this->refreshUToken();
    }

    /**
     * Request a fresh UToken from Yotpo and cache it.
     */
    public function refreshUToken(): string
    {
        $payload = [
            'client_id'     => $this->appKey,
            'client_secret' => $this->apiSecret,
            'grant_type'    => 'client_credentials',
        ];

        $response = $this->httpPost($this->authUrl, $payload, false);

        if (empty($response['access_token'])) {
            throw new RuntimeException('Yotpo: Failed to obtain UToken. Check your App Key / API Secret.');
        }

        $this->uToken = $response['access_token'];

        // Cache with a small buffer (5 min before real expiry)
        $expiresIn = $response['expires_in'] ?? 86400;
        file_put_contents($this->tokenCacheFile, json_encode([
            'token'      => $this->uToken,
            'expires_at' => time() + $expiresIn - 300,
        ]));

        return $this->uToken;
    }

    // ------------------------------------------------------------------ //
    //  Products
    // ------------------------------------------------------------------ //

    /**
     * Send a single product (or update it) to Yotpo.
     *
     * Required fields in $product:
     *   external_id, name, url, image_url, price, currency, description
     *
     * Optional: sku, brand, gtins, mpn, category, inventory_quantity
     */
    public function upsertProduct(array $product): array
    {
        $createEndpoint = $this->apiBase . "/stores/{$this->appKey}/products";

        // Build product body cleanly (no array_filter — keep zero/false values)
        $body = ['product' => [
            'external_id' => (string) $product['external_id'],
            'name'        => (string) $product['name'],
            'url'         => (string) $product['url'],
            'image_url'   => (string) $product['image_url'],
            'price'       => (float)  $product['price'],
            'currency'    => (string) ($product['currency'] ?? 'GBP'),
            'description' => (string) ($product['description'] ?? ''),
        ]];

        // Optional fields — only add when present
        foreach (['sku', 'brand', 'category', 'inventory_quantity'] as $field) {
            if (!empty($product[$field])) {
                $body['product'][$field] = $product[$field];
            }
        }

        // POST to create. If product already exists (409), that's fine — it's already
        // in Yotpo. Note: PATCH by external_id is not supported by Yotpo Core API v3.
        try {
            return $this->httpPost($createEndpoint, $body);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '[409]')) {
                // Product already exists in Yotpo — treat as success
                return ['status' => 'already_exists', 'external_id' => $product['external_id']];
            }
            throw $e;
        }
    }


    /**
     * Send multiple products to Yotpo sequentially (one API call per product).
     * For large catalogues consider running this from a cron job / queue worker.
     */
    public function upsertProductsSequentially(array $products): array
    {
        $results = [];
        foreach ($products as $product) {
            $results[] = $this->upsertProduct($product);
        }
        return $results;
    }

    // ------------------------------------------------------------------ //
    //  Orders
    // ------------------------------------------------------------------ //

    /**
     * Send an order to Yotpo so review-request emails can be triggered.
     *
     * Required fields in $order:
     *   external_id, customer_email, customer_name, line_items[]
     *       line_item: { external_product_id, name, price, quantity, image_url, product_url }
     *
     * Optional: created_at, fulfillment_date, fulfillment_status
     */
    public function createOrder(array $order): array
    {
        $endpoint = $this->apiBase . "/stores/{$this->appKey}/orders";

        // Parse first/last name
        $nameParts = explode(' ', trim($order['customer_name'] ?? ''), 2);
        $firstName = $order['customer_first_name'] ?? ($nameParts[0] ?? '');
        $lastName  = $order['customer_last_name']  ?? ($nameParts[1] ?? '');

        // Build line items cleanly
        $lineItems = [];
        foreach ($order['line_items'] as $item) {
            $li = [
                'external_product_id' => (string) $item['external_product_id'],
                'name'                => (string) $item['name'],
                'price'               => (float)  $item['price'],
                'quantity'            => (int)    ($item['quantity'] ?? 1),
            ];
            if (!empty($item['image_url']))   $li['image_url']   = $item['image_url'];
            if (!empty($item['product_url'])) $li['product_url'] = $item['product_url'];
            $lineItems[] = $li;
        }

        // Build customer object
        $customer = [
            'external_id' => (string) ($order['customer_external_id'] ?? $order['customer_email']),
            'email'       => (string) $order['customer_email'],
            'first_name'  => (string) $firstName,
            'last_name'   => (string) $lastName,
        ];
        if (!empty($order['customer_phone'])) {
            $customer['phone_number'] = $order['customer_phone'];
        }

        // Build the full order body
        // Per Yotpo docs, fulfillment_status + fulfillment_date can be set
        // at creation time — this avoids a separate PATCH call.
        // NOTE: Yotpo Core API v3 uses 'order_date', NOT 'created_at'
        $orderDate = $order['order_date'] ?? $order['created_at'] ?? null;

        $body = [
            'order' => [
                'external_id'        => (string) $order['external_id'],
                'payment_status'     => $order['payment_status'] ?? 'paid',
                'order_date'         => $this->toIso8601($orderDate),        // ← correct field name
                'fulfillment_date'   => $this->toIso8601($order['fulfillment_date'] ?? null),
                'fulfillment_status' => $order['fulfillment_status'] ?? 'success',
                'currency'           => $order['currency'] ?? 'GBP',
                'total_price'        => (float) ($order['total_price'] ?? 0),
                'customer'           => $customer,
                'line_items'         => $lineItems,
            ],
        ];

        return $this->httpPost($endpoint, $body);
    }



    /**
     * Mark an existing order as fulfilled so Yotpo triggers the review email.
     */
    public function fulfillOrder(string $orderId, ?string $fulfillmentDate = null): array
    {
        $endpoint = $this->apiBase . "/stores/{$this->appKey}/orders/{$orderId}";

        $body = [
            'order' => [
                // Always use full ISO 8601 UTC datetime
                'fulfillment_date'   => $this->toIso8601($fulfillmentDate),
                'fulfillment_status' => 'success',
            ],
        ];

        return $this->httpPatch($endpoint, $body);
    }

    // ------------------------------------------------------------------ //
    //  Date Helper
    // ------------------------------------------------------------------ //

    /**
     * Converts any date/datetime string (or null) to a full ISO 8601 UTC string.
     * Yotpo v3 API rejects plain date strings like '2026-03-08'.
     */
    private function toIso8601(?string $date): string
    {
        if ($date === null) {
            return gmdate('Y-m-d\TH:i:s') . 'Z';
        }
        // If already a full datetime, normalise to UTC Z suffix
        try {
            $dt = new DateTimeImmutable($date, new DateTimeZone('UTC'));
            return $dt->format('Y-m-d\TH:i:s') . 'Z';
        } catch (\Exception $e) {
            return gmdate('Y-m-d\TH:i:s') . 'Z';
        }
    }

    // ------------------------------------------------------------------ //
    //  HTTP Helpers
    // ------------------------------------------------------------------ //

    private function httpPost(string $url, array $body, bool $auth = true): array
    {
        return $this->curlRequest('POST', $url, $body, $auth);
    }

    private function httpPatch(string $url, array $body): array
    {
        return $this->curlRequest('PATCH', $url, $body);
    }

    private function curlRequest(string $method, string $url, array $body, bool $auth = true, bool $isRetry = false): array
    {
        $headers = ['Content-Type: application/json'];
        if ($auth) {
            $headers[] = 'X-Yotpo-Token: ' . $this->getUToken();
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => false,  // never follow redirects (auth redirect = stale token)
        ]);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        // ── Debug log (only when debug mode is enabled) ───────────────────
        if ($this->debug) {
            // Redact the auth token from logs — show only first 8 chars
            $safeHeaders = array_map(function (string $h): string {
                if (str_starts_with($h, 'X-Yotpo-Token:')) {
                    return 'X-Yotpo-Token: ' . substr(explode(': ', $h, 2)[1], 0, 8) . '…[redacted]';
                }
                return $h;
            }, $headers);

            $curlHeaders = '';
            foreach ($safeHeaders as $h) {
                $curlHeaders .= " \\\n  -H " . escapeshellarg($h);
            }
            $curlCmd = sprintf(
                "curl -s -X %s%s \\\n  -d %s \\\n  %s",
                $method,
                $curlHeaders,
                escapeshellarg(json_encode($body)),
                escapeshellarg($url)
            );

            $log = implode("\n", [
                str_repeat('-', 60),
                '[' . date('Y-m-d H:i:s') . '] ' . $method . ' ' . $url,
                'PAYLOAD : ' . json_encode($body),
                'HTTP     : ' . $httpCode,
                'RESPONSE : ' . $raw,
                '',
                'CURL CMD :',
                $curlCmd,
                '',
            ]);
            file_put_contents(
                dirname(__DIR__) . '/yotpo_debug.txt',
                $log,
                FILE_APPEND | LOCK_EX
            );
        }
        // ── End debug log ─────────────────────────────────────────────────

        if ($curlErr) {
            throw new RuntimeException("Yotpo cURL error: {$curlErr}");
        }

        // 401 = stale/expired UToken — delete cache, get a fresh token and retry once
        if ($httpCode === 401 && $auth && !$isRetry) {
            if (file_exists($this->tokenCacheFile)) {
                unlink($this->tokenCacheFile);
            }
            $this->uToken = null;
            return $this->curlRequest($method, $url, $body, $auth, true);
        }

        $decoded = json_decode($raw, true) ?? [];

        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? $raw;
            throw new RuntimeException("Yotpo API error [{$httpCode}] @ {$url}: {$msg}");
        }

        return $decoded;
    }
}
