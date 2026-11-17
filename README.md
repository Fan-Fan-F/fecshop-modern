# FECShop Modern

> 现代化 PHP 8.x 与 Yii2 跨境电商商城核心引擎，具备高精度多货币舍入换算、现代化支付网关规范、HTML5 响应式前端及渐进增强。  
> Modern PHP 8.x & Yii2 cross-border ecommerce engine featuring high-precision multi-currency rounding, modernized payment gateway specifications, responsive HTML5 frontend, and progressive enhancements.

---

[中文说明](#fecshop-modern-中文) | [English Description](#fecshop-modern-english)

---

<a name="fecshop-modern-中文"></a>
## 中文说明

### 项目介绍
**FECShop Modern** 是基于著名开源跨境电商框架 [FECShop](https://github.com/fecshop/yii2_fecshop) 进行二次开发的现代化升级版本。原项目是一款功能完备的跨境电商系统（涵盖 PC 端 `appfront`、移动端 `apphtml5`、管理后台 `appadmin`、RESTful API `appserver` 与后台脚本 `console`），但在现代 PHP 8.x 运行时、外币截断舍入、旧版支付规范及前端标准上存在已知不足。

本项目对核心服务层、支付网关、前端布局与运行环境兼容性进行了系统性重构，提供生产就绪的高精度外币结算、更安全的支付集成、HTML5/CSRF 强化的前端体验，以及开箱即用的 PHP 8.0~8.3 独立引导与自测能力。

### 功能与改进点（逐条对应 CHANGES.md）

1. **多货币转换高精度计算与四舍五入一致性增强 (Multi-Currency Rounding Consistency & Precision)**
   - 彻底修复 `services/page/Currency.php` 中 `bcmul`/`bcdiv` 指定 scale 导致的截断丢分（Truncation）缺陷，采用中间高精度结合半数舍入（Half-Up Rounding），保证多货币正反向换算的一致性。
   - 新增国际 ISO-4217 货币精度支持函数 `getCurrencyDecimalPrecision($currencyCode)`，原生适配 JPY/KRW 等 0 位小数货币与 KWD/BHD 等 3 位小数货币。
   - 修复 `appserverSetCurrentCurrency` 中对 HeaderCollection 的数组形式访问，适配 PHP 8.x 严格规范。

2. **支付通道更新与安全性/规范性现代化 (Payment Gateway Modernization & Security)**
   - PayPal：IPN 校验端点升级为官方标准 `https://ipnpb.paypal.com/cgi-bin/webscr`（沙盒为 `https://ipnpb.sandbox.paypal.com/cgi-bin/webscr`），启用 HTTPS 证书主机校验，修复 `curlGet` 中未定义变量 `$req`。
   - 微信支付：重构回调与统一下单金额换算，使用高精度整型分值转换 `bcmul((string)$order_total_amount, '100', 0)`，消除浮点乘法精度误差；移除 PHP 8 废弃的 `final private` 方法签名。
   - 支付宝：增加多币种换算拦截，非 CNY 订单安全转换为基准 CNY 金额后再发起支付与回调签名核对。

3. **前端现代化与渐进增强 (Frontend Modernization & Progressive Enhancement)**
   - 布局现代化：将全部 5 个核心前台 Layouts（`main.php`, `home.php`, `category_view.php`, `product_view.php`, `one_step_checkout.php`）从 XHTML 1.0 Strict 升级为 HTML5 标准（`<!DOCTYPE html>`），增加 UTF-8 声明与响应式视口。
   - 安全性与 CSRF：全局头部注入标准 `csrfMetaTags()` 与安全 Referrer 策略头；重构 `js.js`，自动在 `doPost` 中注入 CSRF Token，消除全局命名空间污染，将货币切换改造为异步 Promise 驱动更新。

4. **PHP 8.x 现代运行环境兼容性与独立引导支持 (PHP 8.x Modern Runtime & Standalone Bootstrap)**
   - 拓宽 `composer.json` 中 `league/iso3166` 依赖范围至 `^2.1 || ^3.0 || ^4.0`，配置 Composer 2.x `allow-plugins` 与 asset-packagist 仓库，全面支持 PHP 8.0~8.3。
   - 增强 `yii/Yii.php` 支持双模路径探测，不仅支持作为 vendor 子依赖运行，亦支持独立检出时加载自身 vendor，使单元测试、CLI 命令及轻量内置 Web 服务器可直接启动。

5. **敏感信息清除与配置安全占位 (Security & Secret Sanitization)**
   - 全面排查并清除了配置文件与迁移脚本中的真实第三方 API 账密、支付宝私钥及微信密钥，全部替换为标准环境变量/占位符格式。
   - 完善 `.gitignore`，防止临时数据库、日志、动态资产及本地凭据泄露。

### 快速开始

#### 1. 系统要求
- PHP 8.0 ~ 8.3（需开启 `bcmath`, `curl`, `mbstring`, `pdo_sqlite` 或 `pdo_mysql`, `openssl` 扩展）
- Composer 2.x
- SQLite3 或 MySQL 5.7+ / MariaDB 10.3+

#### 2. 安装依赖
```bash
composer install --no-interaction
```

#### 3. 运行自测套件
本项目包含完整的独立自测套件，覆盖核心服务引导、多币种换算精度、支付通道、HTML5 布局渲染及敏感信息脱敏校验：
```bash
php tests/self_test.php
```

#### 4. 启动轻量演示服务器
使用 PHP 内置 Web 服务器即可直接体验现代化的 FECShop 页面渲染与货币转换：
```bash
php -S 127.0.0.1:8080 -t tests/web
```
在浏览器访问 `http://127.0.0.1:8080/index.php`，即可查看已注入 HTML5 DOCTYPE、CSRF Meta 与多货币汇率换算的欢迎页面。

#### 5. 配置说明
生产环境下集成支付或邮件服务时，请在配置文件中填入您的真实凭证（切勿直接提交到版本库）：
- 支付配置：`config/services/Payment.php`（或通过后台 Store Config 配置）
  - PayPal: `YOUR_PAYPAL_API_ACCOUNT`, `YOUR_PAYPAL_PASSWORD`, `YOUR_PAYPAL_SIGNATURE`
  - Alipay: `YOUR_ALIPAY_APP_ID`, `YOUR_ALIPAY_RSA_PRIVATE_KEY`
  - Wxpay: `YOUR_WXPAY_SERVICE_APP_ID`, `YOUR_WXPAY_MERCHANT_KEY`
- 邮件配置：`config/services/Email.php`

### 来源与致谢
本项目基于 [FECShop](https://github.com/fecshop/yii2_fecshop) 二次开发，原项目由 fecshop 以 BSD-3-Clause 许可证开源。

### 许可证
本项目沿用 BSD-3-Clause 许可证，完整文本请参阅根目录 [LICENSE](LICENSE) 文件。

---

<a name="fecshop-modern-english"></a>
## English Description

### Overview
**FECShop Modern** is a modernized secondary development of the well-known open-source cross-border ecommerce engine [FECShop](https://github.com/fecshop/yii2_fecshop). The original project is a feature-rich ecommerce solution covering PC storefront (`appfront`), mobile web (`apphtml5`), admin dashboard (`appadmin`), RESTful API server (`appserver`), and console automation (`console`). However, it suffered from known issues in modern PHP 8.x runtime environments, multi-currency truncation errors, deprecated payment gateway protocols, and legacy frontend doctypes.

This rework systematically revamps core services, payment gateways, frontend templates, and standalone runtime bootstrapping, delivering production-ready precision currency calculations, hardened payment security, responsive HTML5/CSRF protection, and out-of-the-box PHP 8.0–8.3 support.

### Key Features & Improvements (Mapped to CHANGES.md)

1. **Multi-Currency Rounding Consistency & Precision**
   - Eliminated precision loss caused by `bcmul`/`bcdiv` truncation in `services/page/Currency.php`. Implemented high-precision intermediate calculation (`scale + 4`) paired with half-up rounding (`number_format`/`round`) to maintain exact bidirectional currency conversions.
   - Added `getCurrencyDecimalPrecision($currencyCode)` supporting ISO-4217 zero-decimal currencies (JPY, KRW, VND) and three-decimal currencies (KWD, BHD, OMR).
   - Fixed array-access notice on HeaderCollection in `appserverSetCurrentCurrency` under PHP 8.x.

2. **Payment Gateway Modernization & Security**
   - **PayPal**: Updated IPN verification endpoints to modern PayPal standards (`https://ipnpb.paypal.com/cgi-bin/webscr` and sandbox `https://ipnpb.sandbox.paypal.com/cgi-bin/webscr`), enforced SSL verification, and resolved undefined variable `$req` in `curlGet()`.
   - **WeChat Pay**: Standardized amount conversions to exact integer cents via `bcmul((string)$order_total_amount, '100', 0)`, eliminating floating-point rounding mismatches during callbacks; eliminated PHP 8 deprecated `final private` method modifiers.
   - **Alipay**: Enforced multi-currency conversion to baseline CNY before payment request dispatch and callback verification.

3. **Frontend Modernization & Progressive Enhancement**
   - **HTML5 Layouts**: Upgraded all 5 core PC storefront layouts (`main.php`, `home.php`, `category_view.php`, `product_view.php`, `one_step_checkout.php`) from legacy XHTML 1.0 Strict to modern HTML5 (`<!DOCTYPE html>`) with UTF-8 charset and responsive viewports.
   - **Security & CSRF**: Injected standard `csrfMetaTags()` and modern referrer policies into `head.php`. Refactored `js.js` to eliminate global namespace pollution, automated CSRF token injection in `doPost()`, and transitioned currency switching to asynchronous Promises.

4. **PHP 8.x Modern Runtime & Standalone Bootstrap**
   - Expanded `league/iso3166` constraints to `^2.1 || ^3.0 || ^4.0` in `composer.json`, added Composer 2.x `allow-plugins` configuration, and integrated `asset-packagist` repository.
   - Enhanced `yii/Yii.php` with dual-mode path resolution, enabling the repository to run standalone tests and built-in web servers directly without requiring an enclosing wrapper project.

5. **Security & Secret Sanitization**
   - Scrubbed all real third-party API credentials, sandbox passwords, Alipay RSA keys, and WeChat secrets from configurations and migration scripts, replacing them with clear placeholders.
   - Hardened `.gitignore` to prevent runtime artifacts, temporary databases, and local credentials from leaking.

### Quick Start

#### 1. Prerequisites
- PHP 8.0 ~ 8.3 (with `bcmath`, `curl`, `mbstring`, `pdo_sqlite` or `pdo_mysql`, `openssl` extensions enabled)
- Composer 2.x
- SQLite3 or MySQL 5.7+ / MariaDB 10.3+

#### 2. Install Dependencies
```bash
composer install --no-interaction
```

#### 3. Run Self-Test Suite
Run the built-in self-test suite validating bootstrap, multi-currency rounding, payment gateways, HTML5 layout rendering, and security sanitization:
```bash
php tests/self_test.php
```

#### 4. Launch Lightweight Demo Server
Test the modern frontend rendering and currency conversions using PHP's built-in web server:
```bash
php -S 127.0.0.1:8080 -t tests/web
```
Navigate to `http://127.0.0.1:8080/index.php` in your browser to view the modernized demo page.

#### 5. Configuration Notes
When configuring payment or email services for production, populate your credentials in the respective config files or admin store configuration:
- Payment config: `config/services/Payment.php`
  - PayPal: `YOUR_PAYPAL_API_ACCOUNT`, `YOUR_PAYPAL_PASSWORD`, `YOUR_PAYPAL_SIGNATURE`
  - Alipay: `YOUR_ALIPAY_APP_ID`, `YOUR_ALIPAY_RSA_PRIVATE_KEY`
  - Wxpay: `YOUR_WXPAY_SERVICE_APP_ID`, `YOUR_WXPAY_MERCHANT_KEY`
- Email config: `config/services/Email.php`

### Sources & Acknowledgments
This project is a secondary development based on [FECShop](https://github.com/fecshop/yii2_fecshop), originally open-sourced by fecshop under the BSD-3-Clause License.

### License
This project is licensed under the BSD-3-Clause License. See the root [LICENSE](LICENSE) file for the full text.
