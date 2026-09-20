<?php
// DragonPay Gateway — COPY to dragonpay.php and fill real credentials
// Do NOT commit dragonpay.php (see .gitignore). Use env vars in production.
// IMPORTANT: configure the public callback in the DragonPay merchant dashboard at
// https://uphsl.edu.ph/online_payment/retback. Do NOT add a returnurl field to the
// signed request payload in PHP because it invalidates the merchant digest.
define('DRAGONPAY_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('DRAGONPAY_MERCHANT_PASSWORD', 'YOUR_MERCHANT_PASSWORD');
define('DRAGONPAY_ENV', 'live'); // live | test
