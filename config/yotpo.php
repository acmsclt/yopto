<?php
/**
 * Yotpo Reviews Integration - Configuration
 * Replace these values with your actual Yotpo credentials
 * from your Yotpo Admin Dashboard.
 *
 * Dashboard: https://reviews.yotpo.com/#/settings/account-settings
 * Docs:      https://support.yotpo.com/docs/finding-your-yotpo-app-key-and-secret-key
 */
return [
    // Your Yotpo App Key (public)
    'app_key' => 'kyu1RIRD9mO3DlYPc2fkJXhRPO81cpIbtLHgH7ec',

    // Your Yotpo API Secret (keep this private – never expose in front-end code)
    'api_secret' => '7lswzL4yvwQK26x58figu4dLfnMoikvNbaZhnCD9',

    // Yotpo Core API base URL (v3)
    'api_base' => 'https://api.yotpo.com/core/v3',

    // UToken endpoint (authentication)
    'auth_url' => 'https://api.yotpo.com/oauth/token',

    // Your store's base URL (used for product URLs sent to Yotpo)
    'store_url' => 'https://dev-env.tabsyst.com/yotpo',

    // Default currency
    'currency' => 'GBP',

    // Set to true to write every API request/response to yotpo_debug.txt
    // Keep false in production — logs contain request payloads
    'debug' => true,
];
