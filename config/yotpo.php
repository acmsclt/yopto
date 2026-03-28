<?php
/**
 * Yotpo Reviews Integration - Configuration
 *
 * ENVIRONMENT: STAGING
 * Store:       Vape & Go Staging store (Account ID: 1772436)
 * Dashboard:   https://reviews.yotpo.com/#/settings/account-settings
 *
 * ── Switch to PRODUCTION ──────────────────────────────────────────────────────
 * When going live, swap the STAGING block below for the PRODUCTION block.
 *
 * PRODUCTION (vapeandgo.co.uk — Account ID: 1769428):
 *   'app_key'    => 'kyu1RIRD9mO3DlYPc2fkJXhRPO81cpIbtLHgH7ec',
 *   'api_secret' => '7lswzL4yvwQK26x58figu4dLfnMoikvNbaZhnCD9',
 *   'store_url'  => 'https://vapeandgo.co.uk',
 * ─────────────────────────────────────────────────────────────────────────────
 */
return [
    // ── STAGING credentials (Vape & Go Staging store) ─────────────────────────
    'app_key'    => 'XfeuktivjjLOIz0xJSt5loX1fQ6Jw7mXN9I',

    // TODO: Open https://settings.yotpo.com/#/general_settings, switch to
    //       "Vape & Go Staging store", click "Get secret key" and paste it here.
    'api_secret' => 'STAGING_SECRET_KEY_NEEDED',

    // Yotpo Core API base URL (v3)
    'api_base'   => 'https://api.yotpo.com/core/v3',

    // UToken endpoint (authentication)
    'auth_url'   => 'https://api.yotpo.com/oauth/token',

    // Staging store base URL
    'store_url'  => 'https://dev-env.tabsyst.com/yotpo',

    // Default currency
    'currency'   => 'GBP',

    // Set to true to write every API request/response to yotpo_debug.txt
    // Fine for staging — disable for production
    'debug'      => true,
];
