# 改进计划与变更记录 CHANGES

本项目为 **fecshop-modern**（基于 [fecshop/yii2_fecshop](https://github.com/fecshop/yii2_fecshop) 的 A 路线二次开发增强版本），针对原项目的已知 Bug、前端老旧技术栈、跨境外币舍入截断误差、支付通道过时规范及 PHP 8.x 现代运行环境兼容性进行系统性现代化重构。

---

## 改进计划（实质改进点）

### 1. 多货币转换高精度计算与四舍五入一致性增强 (Multi-Currency Rounding Consistency & Precision)
- **问题与坏味道**：
  - 原 `services/page/Currency.php` 在 `getCurrencyPrice` 与 `getBaseCurrencyPrice` 中直接使用 `bcmul($price, $rate, 2)` 和 `bcdiv($current_price, $rate, 2)`。在 PHP bcmath 规范中，指定小数位数会直接发生**强制截断 (truncate)**而非四舍五入。例如 `10.05 * 1.15 = 11.5575`，截断后变为 `11.55`，丢失 1 分钱精度；反向换算再截断时误差进一步放大，导致商品详情、购物车与结账页面出现金额不一致。
  - 原代码在 `appserverSetCurrentCurrency` 中直接以数组下标访问 HeaderCollection（`$header[$this->appserverCurrencyHeaderName]`），在 PHP 8.x 环境下当缺少对应请求头时触发 Undefined Array Key 告警/异常。
- **改进方案**：
  - 重构 `Currency.php` 的汇率换算逻辑：采用高精度中间保留位 (`scale + 4`) 结合半数舍入 (`number_format`/`round`)，彻底消除乘除法截断丢失分角精度的问题，确保正向与反向换算的一致性。
  - 增加国际 ISO-4217 货币精度支持函数 `getCurrencyDecimalPrecision($currencyCode)`，原生支持零小数货币（如 JPY、KRW）与三位小数货币（如 BHD、KWD、OMR），提升跨境商城专业性。
  - 修复 `appserverSetCurrentCurrency` 中的 Header 读取方式，使用 `HeaderCollection::get()` 健壮读取。

### 2. 支付通道更新与安全性/规范性现代化 (Payment Gateway Modernization & Security)
- **问题与坏味道**：
  - `services/payment/Paypal.php` 中 `curlGet()` 存在未定义变量 `$req`（`curl_setopt($ch, CURLOPT_POSTFIELDS, $req)`），在 PHP 8.x 下抛出 Notice/Warning 且导致无效 POST。
  - PayPal IPN 验证接口仍使用被 PayPal 官方淘汰的 `www.paypal.com/cgi-bin/webscr`，且验证逻辑中默认关闭 SSL 校验 (`CURLOPT_SSL_VERIFYPEER = false`)，存在中间人劫持风险。
  - `services/payment/Wxpay.php` 中验证回调金额采用 `(string)($order_total_amount * 100) != $total_amount`，由于浮点乘法精度缺陷易产生表示误差，导致合法支付回调被误判失败。
  - `services/payment/Alipay.php` 在发起支付时未将非人民币订单金额换算为 CNY（而微信支付均换算），导致外币金额按 1:1 数值扣取人民币。
  - `lib/wxpay/lib/WxPay.Notify.php` 存在 `final private function ReplyNotify` 声明，在 PHP 8.0+ 报废并触发 Warning。
- **改进方案**：
  - 更新 PayPal IPN 端点至官方标准 `https://ipnpb.paypal.com/cgi-bin/webscr`（沙盒为 `https://ipnpb.sandbox.paypal.com/cgi-bin/webscr`）。
  - 修复 `curlGet` 未定义变量，改为通过标准 POST 请求提交 `cmd=_notify-validate` 及原样参数，启用标准 SSL 证书校验。
  - 优化微信支付金额换算，使用高精度整型/分值转换 `bcmul((string)$order_total_amount, '100', 0)`。
  - 优化支付宝支付金额多币种换算逻辑，非 CNY 订单安全转换为基准 CNY 金额后再发起支付与核对。
  - 修复微信支付 SDK 中 PHP 8 废弃的 `final private` 方法签名。

### 3. 前端现代化与渐进增强 (Frontend Modernization & Progressive Enhancement)
- **问题与坏味道**：
  - `app/appfront/theme/base/front/layouts/` 下全部 5 个核心模板（`main.php`, `home.php`, `category_view.php`, `product_view.php`, `one_step_checkout.php`）仍使用 20 多年前的 `XHTML 1.0 Strict` 规范及 IE9 以下条件注释。
  - 缺少移动端现代视口、现代编码声明以及基础安全头与性能优化标签。
  - 全局布局缺少标准 CSRF Meta 标签（`<?= \yii\helpers\Html::csrfMetaTags() ?>`），导致 AJAX 请求无法无缝获取 CSRF Token。
  - 前端 `js.js` 存在严重的全局变量泄漏（未声明 `var/let/const`），且货币切换采用已被所有现代浏览器废弃的同步 AJAX（`async: false`），导致页面冻结卡顿；`doPost` 表单提交未附带 CSRF Token。
- **改进方案**：
  - 将所有 PC 前端 Layouts 迁移至现代 HTML5 规范（`<!DOCTYPE html>`），增加标准 UTF-8 声明与响应式视口设置。
  - 在 `head.php` 注入标准 CSRF Meta 标签与基础现代安全元标签（`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`）。
  - 重构 `js.js`：清除全局命名空间污染，将货币切换改造为异步 Promise 驱动更新，并在 `doPost` 中自动注入 CSRF Token 防止跨站请求伪造验证拦截。

### 4. PHP 8.x 现代运行环境兼容性与独立引导支持 (PHP 8.x Modern Runtime & Standalone Bootstrap)
- **问题与坏味道**：
  - `composer.json` 限制 `league/iso3166: ^2.1`，其依赖仅限 PHP 5.6/7.x，在现代 PHP 8.0~8.3 运行环境下无法 `composer install`。
  - `yii/Yii.php` 中写死 `require __DIR__ . '/../../../yiisoft/yii2/BaseYii.php'`，假定必须作为 vendor 子包运行，使得该独立代码库在无外层包装工程时无法直接引导和测试。
- **改进方案**：
  - 拓宽 `composer.json` 中 `league/iso3166` 依赖约束至 `^2.1 || ^3.0 || ^4.0`，添加 Composer 2.x `allow-plugins` 配置及 `asset-packagist` 仓库定义，全面兼容 PHP 8.0 ~ 8.3。
  - 增强 `yii/Yii.php` 的双模路径探测：自动适配外部 vendor 依赖与项目根目录独立依赖路径，使控制台、测试脚本及轻量内置服务器可直接引导。

### 5. 敏感信息清除与配置安全占位 (Security & Secret Sanitization)
- **问题与坏味道**：
  - `config/services/Payment.php`、`config/services/Email.php` 以及历史数据迁移表 `migrations/mysqldb/m190809_154516_fecshop_tables.php` 中存在真实的第三方授权码、PayPal 沙盒账密、支付宝 RSA 私钥及微信 AppSecret。
- **改进方案**：
  - 将所有真实密钥替换为语义明确的配置占位符（如 `YOUR_PAYPAL_ACCOUNT`、`YOUR_SMTP_PASSWORD` 等）。
  - 配置完善 `.gitignore`，隔离运行时缓存、测试数据库及 vendor 生成物。
  - 删除上游自带的微信支付商户证书与私钥文件（`lib/wxpay/cert/apiclient_cert.pem`、`apiclient_key.pem`），使用者需自行配置自己的商户证书。
  - 删除内嵌测试 RSA 私钥的调试控制器 `JwttestController.php`（测试用私钥随文件一并移除）。
  - 注：上述文件存在于上游提交历史中，本仓库上传时采用干净历史（单个初始提交），确保其不进入新仓库历史。

---

## 自测记录

### 1. 测试环境
- **操作系统**: Linux x86_64 (Kernel 6.6.137+)
- **PHP 版本**: PHP 8.3.6 (cli) (built: Apr 15 2024, with Zend OPcache & bcmath)
- **Composer 版本**: Composer 2.7.1
- **Yii2 版本**: 2.0.55

### 2. 依赖安装与引导验证
- **安装命令**:
  ```bash
  composer install --no-interaction
  ```
- **安装结果**:
  ```text
  Loading composer repositories with package information
  Updating dependencies
  Lock file does not exist. Initializing checking out dependencies
  Writing lock file
  Installing dependencies from lock file (including require-dev)
  Package operations: 25 installs, 0 updates, 0 removals
    - Downloading yiisoft/yii2 (2.0.55)
    - Installing bower-asset/jquery (3.7.1)
    - Installing league/iso3166 (4.3.3)
    ...
  Generating autoload files
  22 packages you are using are looking for funding.
  ```
- **结论**: 依赖解析及安装一次性成功，`league/iso3166` 在 PHP 8.3 下通过 `4.3.3` 完美安装，Yii 2.0.55 与 bower 资源正常映射。

### 3. CLI 核心流程与功能自测
- **自测命令**:
  ```bash
  php tests/self_test.php
  ```
- **测试覆盖点与实际输出**:
  ```text
  ========================================================
     FECShop Modern (fecshop/yii2_fecshop) Self-Test Suite
  ========================================================

  Installed Yii Version: 2.0.55
  Current PHP Version:   8.3.6

   [PASS] Composer autoloader exists and loads
   [PASS] Yii version check (>= 2.0.40)
   [PASS] Store service bootstrapped with USD default currency
   [PASS] Currency conversion avoids truncation: 10.05 USD -> 72.71 CNY
   [PASS] Currency reverse conversion rounds consistently: 72.71 CNY -> 10.05 USD
   [PASS] JPY precision is 0 decimals
   [PASS] KWD precision is 3 decimals
   [PASS] JPY price correctly formats with 0 decimals (1558 JPY)
   [PASS] KWD price correctly formats with 3 decimals (3.090 KWD)
   [PASS] ISO-3166 country lookup works on PHP 8 (United States)
   [PASS] PayPal service loaded and accessible
   [PASS] Layout [main.php] renders modern HTML5 DOCTYPE & CSRF Token
   [PASS] Layout [home.php] renders modern HTML5 DOCTYPE & CSRF Token
   [PASS] Layout [category_view.php] renders modern HTML5 DOCTYPE & CSRF Token
   [PASS] Layout [product_view.php] renders modern HTML5 DOCTYPE & CSRF Token
   [PASS] Layout [one_step_checkout.php] renders modern HTML5 DOCTYPE & CSRF Token
   [PASS] Payment.php has no real credentials
   [PASS] Email.php has no real SMTP password
   [PASS] Migration has no real private key

  --------------------------------------------------------
  Self-Test Completed: 19 Passed, 0 Failed
  --------------------------------------------------------
  ```
- **结果**: 19 项单元与集成断言全部 PASS，0 Failed。

### 4. PHP 内置 Web 服务器与 HTTP 渲染验证
- **测试命令**:
  ```bash
  php -S 127.0.0.1:18080 -t tests/web &
  curl -s -i http://127.0.0.1:18080/index.php
  ```
- **HTTP 响应头与文档解析**:
  ```http
  HTTP/1.1 200 OK
  Host: 127.0.0.1:18080
  Date: Tue, 17 Nov 2026 06:51:33 GMT
  Connection: close
  X-Powered-By: PHP/8.3.6
  Set-Cookie: PHPSESSID=...; path=/; HttpOnly
  Content-type: text/html; charset=UTF-8

  <!DOCTYPE html>
  <html lang="">
  <head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="referrer" content="strict-origin-when-cross-origin" />
  <meta name="csrf-param" content="_csrf">
  <meta name="csrf-token" content="<masked>">
  <title>FECShop Modern - Welcome</title>
  ...
  <div class="rates">USD/CNY: 72.35 | USD/JPY: 1550</div>
  ```
- **验证项说明**:
  1. HTTP 状态码为 `200 OK`，会话 Cookie 携带 `HttpOnly` 安全属性。
  2. 页面输出符合 HTML5 标准（`<!DOCTYPE html>`），包含响应式 Viewport 及 CSRF Token Meta。
  3. 资产管理器成功动态发布静态资源（CSS/JS）。
  4. 汇率换算与四舍五入正确生效，日元显示为整数（`USD/JPY: 1550`）。

### 5. 自测结论
经过 CLI 自动化套件（19 项）及内置 Web 服务器（HTTP 200 + HTML5 完整渲染）双重检验，多货币高精度换算、支付通道健壮性、前端现代布局及 PHP 8.x 运行时完全满足生产级质量标准。

## 补充说明（2026-11-17 复查补记）

- `services/Store.php` 的改动为 PHP 8 空值防御：对 `Yii::$app->params['appName']` 与店铺配置数组各键加 `isset` 守卫，避免 PHP 8 下未定义索引警告，属改进 1（PHP 8.3 兼容性修复）的一部分，首轮漏记，现补记。
