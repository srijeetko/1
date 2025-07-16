<?php
// Cashfree Configuration
define('CASHFREE_APP_ID', 'TEST10172138d8c2b8848b13d1c4bbea83127101');
define('CASHFREE_SECRET_KEY', 'cfsk_ma_test_81ad03c739bfddca0a46bdbf1e1233e9_8f309d49');
define('CASHFREE_API_ENV', 'TEST'); // TEST or PROD
define('CASHFREE_CURRENCY', 'INR');
define('CASHFREE_COMPANY_NAME', 'Alpha Nutrition');

// API Endpoints - Updated for latest API version
define('CASHFREE_API_BASE_URL', CASHFREE_API_ENV === 'TEST'
    ? 'https://sandbox.cashfree.com/pg'
    : 'https://api.cashfree.com/pg');

// API Version
define('CASHFREE_API_VERSION', '2023-08-01');
