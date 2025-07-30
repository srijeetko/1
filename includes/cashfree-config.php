<?php
// Cashfree Configuration

// Force production mode - using real Cashfree API even during development
// Production credentials - ALWAYS USED
define('CASHFREE_APP_ID', '667364de1dbc524e0b260a7c3c463766');
define('CASHFREE_SECRET_KEY', 'cfsk_ma_prod_6331e813da65e12110f3edc596329a3d_2879154f');
define('CASHFREE_API_ENV', 'PROD');

/*
// Uncomment this section if you want to use test/sandbox mode
$isLocalhost = (isset($_SERVER['HTTP_HOST']) &&
    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
     strpos($_SERVER['HTTP_HOST'], '.local') !== false));

if ($isLocalhost) {
    // Test credentials for local development
    define('CASHFREE_APP_ID', 'TEST10172138d8c2b8848b13d1c4bbea83127101');
    define('CASHFREE_SECRET_KEY', 'cfsk_ma_test_81ad03c739bfddca0a46bdbf1e1233e9_8f309d49');
    define('CASHFREE_API_ENV', 'TEST');
} else {
    // Production credentials
    define('CASHFREE_APP_ID', '667364de1dbc524e0b260a7c3c463766');
    define('CASHFREE_SECRET_KEY', 'cfsk_ma_prod_6331e813da65e12110f3edc596329a3d_2879154f');
    define('CASHFREE_API_ENV', 'PROD');
}
*/
define('CASHFREE_CURRENCY', 'INR');
define('CASHFREE_COMPANY_NAME', 'Alpha Nutrition');

// API Endpoints - Updated for latest API version
define('CASHFREE_API_BASE_URL', CASHFREE_API_ENV === 'TEST'
    ? 'https://sandbox.cashfree.com/pg'
    : 'https://api.cashfree.com/pg');

// API Version
define('CASHFREE_API_VERSION', '2023-08-01');

// Base URL for return and webhook URLs (HTTPS required for production API)
// Update this with your ngrok HTTPS URL when testing
define('CASHFREE_BASE_URL', 'https://50a7b1afaf2f.ngrok-free.app/1'); // Replace with your actual ngrok URL

// Ngrok bypass header for API requests
define('NGROK_BYPASS_HEADER', 'ngrok-skip-browser-warning');
