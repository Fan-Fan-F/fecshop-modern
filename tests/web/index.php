<?php
/**
 * FECShop Modern - Lightweight Test Entry
 */
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../yii/Yii.php';

Yii::setAlias('@fecshop', dirname(dirname(__DIR__)));
Yii::setAlias('@webroot', __DIR__);

$baseConfig = require Yii::getAlias('@fecshop/config/fecshop.php');
$appfrontConfig = require Yii::getAlias('@fecshop/app/appfront/config/appfront.php');
$config = yii\helpers\ArrayHelper::merge($baseConfig, $appfrontConfig);

$appConfig = [
    'id' => 'fecshop-modern-demo',
    'basePath' => dirname(dirname(__DIR__)),
    'params' => $config['params'],
    'components' => array_merge($config['components'], [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite::memory:',
        ],
        'request' => [
            'class' => 'yii\web\Request',
            'cookieValidationKey' => 'modern_fecshop_demo_cookie_key_32bytes!',
        ],
    ]),
];

$app = new yii\web\Application($appConfig);

// Initialize test database schema
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

Yii::$app->db->createCommand()->insert('store_base_config', [
    'key' => 'currency',
    'value' => serialize([
        ['currency_code' => 'USD', 'currency_symbol' => '$', 'currency_rate' => '1.0000'],
        ['currency_code' => 'CNY', 'currency_symbol' => '¥', 'currency_rate' => '7.2350'],
        ['currency_code' => 'JPY', 'currency_symbol' => '¥', 'currency_rate' => '155.0000'],
    ]),
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

$view = Yii::$app->view;
$view->title = 'FECShop Modern - Welcome';
$layoutPath = Yii::getAlias('@fecshop/app/appfront/theme/base/front/layouts/main.php');

$content = '<div class="fecshop-modern-welcome" style="padding: 20px; font-family: sans-serif;">'
    . '<h2>Welcome to FECShop Modern</h2>'
    . '<p>Cross-border multi-currency ecommerce engine powered by modern PHP 8.x and Yii2.</p>'
    . '<div class="rates">USD/CNY: ' . Yii::$service->page->currency->getCurrencyPrice(10, 'CNY') . ' | USD/JPY: ' . Yii::$service->page->currency->getCurrencyPrice(10, 'JPY') . '</div>'
    . '</div>';

echo $view->renderFile($layoutPath, ['content' => $content]);
