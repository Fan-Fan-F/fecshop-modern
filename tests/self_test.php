<?php
/**
 * FECShop Modern - Automated Self-Test Suite
 * 验证现代环境兼容性、多货币精度、支付网关及前端布局
 */
ob_start();

$passed = 0;
$failed = 0;
$logMessages = [];

function assertTest($description, $condition) {
    global $passed, $failed, $logMessages;
    if ($condition) {
        $logMessages[] = " [PASS] $description";
        $passed++;
    } else {
        $logMessages[] = " [FAIL] $description";
        $failed++;
    }
}

// 1. Composer & Autoload Test
assertTest("Composer autoloader exists and loads", file_exists(__DIR__ . '/../vendor/autoload.php'));
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../yii/Yii.php';

Yii::setAlias('@fecshop', dirname(__DIR__));
Yii::setAlias('@webroot', __DIR__ . '/web');

assertTest("Yii version check (>= 2.0.40)", version_compare(Yii::getVersion(), '2.0.40', '>='));

// 2. Base Configuration & Application Bootstrap
$baseConfig = require Yii::getAlias('@fecshop/config/fecshop.php');
$appfrontConfig = require Yii::getAlias('@fecshop/app/appfront/config/appfront.php');
$config = yii\helpers\ArrayHelper::merge($baseConfig, $appfrontConfig);

$appConfig = [
    'id' => 'fecshop-test-app',
    'basePath' => dirname(__DIR__),
    'params' => $config['params'],
    'components' => array_merge($config['components'], [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite::memory:',
        ],
        'request' => [
            'class' => 'yii\web\Request',
            'cookieValidationKey' => 'test_self_check_cookie_key_32bytes_min!',
            'scriptFile' => __DIR__ . '/web/index.php',
            'scriptUrl' => '/index.php',
        ],
    ]),
];

$app = new yii\web\Application($appConfig);

// Setup SQLite In-Memory Database
Yii::$app->db->createCommand("
    CREATE TABLE store_base_config (id INTEGER PRIMARY KEY AUTOINCREMENT, key VARCHAR(255), value TEXT, created_at INTEGER, updated_at INTEGER);
    CREATE TABLE store_domain (id INTEGER PRIMARY KEY AUTOINCREMENT, key VARCHAR(255), app_name VARCHAR(50), lang VARCHAR(20), lang_name VARCHAR(50), local_theme_dir VARCHAR(255), third_theme_dir TEXT, currency VARCHAR(20), status INTEGER, created_at INTEGER, updated_at INTEGER);
    CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id VARCHAR(50), name TEXT, url_key VARCHAR(255), menu_custom TEXT, status INTEGER, menu_show INTEGER, sort_order INTEGER);
    CREATE TABLE static_block (id INTEGER PRIMARY KEY AUTOINCREMENT, identify VARCHAR(100), content TEXT, status INTEGER, created_at INTEGER, updated_at INTEGER);
")->execute();

Yii::$app->db->createCommand()->insert('store_base_config', [
    'key' => 'base_info',
    'value' => serialize(['default_currency' => 'USD', 'base_currency' => 'USD']),
    'created_at' => time(),
    'updated_at' => time(),
])->execute();

$currencyList = [
    ['currency_code' => 'USD', 'currency_symbol' => '$', 'currency_rate' => '1.0000'],
    ['currency_code' => 'EUR', 'currency_symbol' => '€', 'currency_rate' => '0.9200'],
    ['currency_code' => 'CNY', 'currency_symbol' => '¥', 'currency_rate' => '7.2350'],
    ['currency_code' => 'JPY', 'currency_symbol' => '¥', 'currency_rate' => '155.0000'],
    ['currency_code' => 'KWD', 'currency_symbol' => 'KD', 'currency_rate' => '0.3075'],
];
Yii::$app->db->createCommand()->insert('store_base_config', [
    'key' => 'currency',
    'value' => serialize($currencyList),
    'created_at' => time(),
    'updated_at' => time(),
])->execute();

$appfrontBaseTheme = $config['params']['appfrontBaseTheme'];
Yii::$app->db->createCommand()->insert('store_domain', [
    'key' => 'localhost',
    'app_name' => 'appfront',
    'lang' => 'en-US',
    'lang_name' => 'English',
    'local_theme_dir' => $appfrontBaseTheme,
    'currency' => 'USD',
    'status' => 1,
    'created_at' => time(),
    'updated_at' => time(),
])->execute();

Yii::$service = new \fecshop\services\Application($config['services']);
Yii::$service->store->bootstrap($app);
assertTest("Store service bootstrapped with USD default currency", Yii::$service->page->currency->defaultCurrency === 'USD');

// 3. Multi-Currency High Precision and Rounding Consistency
$usdPrice = 10.05;
$cnyPrice = Yii::$service->page->currency->getCurrencyPrice($usdPrice, 'CNY');
$invUsd = Yii::$service->page->currency->getBaseCurrencyPrice($cnyPrice, 'CNY');
assertTest("Currency conversion avoids truncation: 10.05 USD -> $cnyPrice CNY", $cnyPrice === '72.71');
assertTest("Currency reverse conversion rounds consistently: $cnyPrice CNY -> $invUsd USD", $invUsd === '10.05');

// Test special decimal precision
$jpyPrecision = Yii::$service->page->currency->getCurrencyDecimalPrecision('JPY');
$kwdPrecision = Yii::$service->page->currency->getCurrencyDecimalPrecision('KWD');
assertTest("JPY precision is 0 decimals", $jpyPrecision === 0);
assertTest("KWD precision is 3 decimals", $kwdPrecision === 3);
$jpyPrice = Yii::$service->page->currency->getCurrencyPrice($usdPrice, 'JPY');
assertTest("JPY price correctly formats with 0 decimals ($jpyPrice JPY)", $jpyPrice === '1558');
$kwdPrice = Yii::$service->page->currency->getCurrencyPrice($usdPrice, 'KWD');
assertTest("KWD price correctly formats with 3 decimals ($kwdPrice KWD)", $kwdPrice === '3.090');

// 4. ISO-3166 Country Helper Integration
$countryInfo = Yii::$service->helper->country->getIOS3166InfoByCountryCode('US');
assertTest("ISO-3166 country lookup works on PHP 8 (United States)", isset($countryInfo['name']) && strpos($countryInfo['name'], 'United States') !== false);

// 5. Payment Gateways Modernization
$paypalEndpoint = Yii::$service->payment->paypal->getPaypalSdkJsUrl('USD');
assertTest("PayPal service loaded and accessible", is_string($paypalEndpoint));

// 6. Frontend Layout & DOCTYPE Modernization
$view = Yii::$app->view;
$view->title = 'Modern Test Page';
$layouts = ['main.php', 'home.php', 'category_view.php', 'product_view.php', 'one_step_checkout.php'];
foreach ($layouts as $layout) {
    $path = Yii::getAlias('@fecshop/app/appfront/theme/base/front/layouts/' . $layout);
    $output = $view->renderFile($path, ['content' => '<div>Content for ' . $layout . '</div>']);
    $hasDoctype = (strpos($output, '<!DOCTYPE html>') !== false);
    $hasCsrf = (strpos($output, 'csrf-token') !== false);
    assertTest("Layout [$layout] renders modern HTML5 DOCTYPE & CSRF Token", $hasDoctype && $hasCsrf);
}

// 7. Security & Secret Sanitization Check
$paymentConfig = file_get_contents(Yii::getAlias('@fecshop/config/services/Payment.php'));
$emailConfig = file_get_contents(Yii::getAlias('@fecshop/config/services/Email.php'));
$migrationConfig = file_get_contents(Yii::getAlias('@fecshop/migrations/mysqldb/m190809_154516_fecshop_tables.php'));

assertTest("Payment.php has no real credentials", strpos($paymentConfig, 'HF4TNTTXUD6YQREH') === false && strpos($paymentConfig, 'YOUR_PAYPAL_API_ACCOUNT') !== false);
assertTest("Email.php has no real SMTP password", strpos($emailConfig, 'wffmbummgnhhcbbj') === false && strpos($emailConfig, 'YOUR_SMTP_PASSWORD') !== false);
assertTest("Migration has no real private key", strpos($migrationConfig, 'MIIEpAIBAAKCAQEApIw+Hsk65Z') === false);

// Flush buffer and print results
ob_end_clean();

echo "========================================================\n";
echo "   FECShop Modern (fecshop/yii2_fecshop) Self-Test Suite\n";
echo "========================================================\n\n";
echo "Installed Yii Version: " . Yii::getVersion() . "\n";
echo "Current PHP Version:   " . PHP_VERSION . "\n\n";

foreach ($logMessages as $msg) {
    echo $msg . "\n";
}

echo "\n--------------------------------------------------------\n";
echo "Self-Test Completed: $passed Passed, $failed Failed\n";
echo "--------------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
