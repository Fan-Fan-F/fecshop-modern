<?php

/*
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\services\page;

use fecshop\services\Service;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Page Currency services 货币部分
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class Currency extends Service
{
    const CURRENCY_CURRENT = 'currency_current';

    /**
     * 该变量用于：在配置文件中，配置所有的货币参数。
     * 格式如下：
     * [
     * 		'USD' => [
     * 			'rate' 		=> 1,
     * 			'symbol' 	=> '$',
     * 		],
     * 		'RMB' => [
     * 			'rate' 		=> 6.3,
     * 			'symbol' 	=> 'гд',
     * 		],
     * ].
     */
    public $currencys;

    /**
     * 基础货币，产品的价格，填写的都是基础货币的价格。
     * 该值需要在配置文件中进行配置.
     */
    public $baseCurrecy;

    /**
     * 网站的默认货币，需要注意的是，默认货币不要和基础货币混淆，举例：
     * 后台产品统一使用的美元填写产品价格，但是我的网站前端的默认货币为人民币。
     * 该值需要在配置文件中进行配置.
     */
    public $defaultCurrency;

    /**
     * 当前的货币简码
     */
    protected $_currentCurrencyCode;

    /**
     * 根据配置，保存所有货币的配置信息。
     */
    protected $_currencys;
    
    public function init()
    {
        parent::init();
        // init default and base currency
        $this->defaultCurrency = Yii::$app->store->get('base_info', 'default_currency');
        $this->baseCurrecy = Yii::$app->store->get('base_info', 'base_currency');
        // init all currency
        $currencys = Yii::$app->store->get('currency');
        if (is_array($currencys)) {
            foreach ($currencys as $currency) {
                $currency_code = $currency['currency_code'];
                $currency_symbol = $currency['currency_symbol'];
                $currency_rate = $currency['currency_rate'];
                $this->currencys[$currency_code] = [
                    'rate' => $currency_rate,
                    'symbol' => $currency_symbol
                ];
            }
        }
    }
    
    /**
     * @param $currencyCode | string 货币简码，譬如USD,RMB等
     * @return array
     *               如果不传递参数，得到所有的货币
     *               如果传递参数，得到的是当前货币的信息。
     */
    public function getCurrencys($currencyCode = '')
    {
        if (!$this->_currencys) {
            foreach ($this->currencys as $code => $info) {
                $this->_currencys[$code] = [
                    'code'        => $code,
                    'rate'        => $info['rate'],
                    'symbol'    => $info['symbol'],
                ];
            }
        }
        if ($currencyCode) {
            if (isset($this->_currencys[$currencyCode])) {
                
                return $this->_currencys[$currencyCode];
            } else {
                $currencyCode = $this->defaultCurrency;
                
                return $this->_currencys[$currencyCode];
            }
        }

        return $this->_currencys;
    }

    /**
     * 得到当前货币的符号，譬如￥ $ 等。
     * 如果当前的货币在配置中找不到，则会强制改成默认货币
     */
    public function getCurrentSymbol()
    {
        if (isset($this->currencys[$this->getCurrentCurrency()]['symbol'])) {
            
            return $this->currencys[$this->getCurrentCurrency()]['symbol'];
        }
    }

    /**
     * @param $currencyCode | 货币简码
     * 得到货币的符号，譬如￥ $ 等。
     */
    public function getSymbol($currencyCode)
    {
        if (isset($this->currencys[$currencyCode]['symbol'])) {
            
            return $this->currencys[$currencyCode]['symbol'];
        }
    }
    /**
     * 得到默认的货币符号，譬如： ￥, $
     */
    public function getBaseSymbol()
    {
        if (isset($this->currencys[$this->baseCurrecy]['symbol'])) {
            
            return $this->currencys[$this->baseCurrecy]['symbol'];
        }
    }
    /**
     * 得到默认的货币code，譬如： USD, CNY
     */
    public function getBaseCurrecyCode()
    {
        
        return $this->baseCurrecy;
    }
    
    
    /**
     * property $price|Float ，默认货币的价格
     * Get current currency price.  price format is two decimal places,
     * if current currency is not find in object variable $currencys(maybe change config in online shop,but current user session is effective),
     * current currency will set defaultCurrency, origin price will be return.
     * 通过传递默认货币的价格，得到当前货币的价格。
     */
    public function getCurrentCurrencyPrice($price)
    {
        $currencyCode  = $this->getCurrentCurrency();
        $currencyPrice = $this->getCurrencyPrice($price, $currencyCode);
        if ($currencyPrice !== null) {
            
            return $currencyPrice;
        }
        /*
         * 如果上面出现错误，当前的货币在货币配置中找不到，则会使用默认货币
         * 这种情况可能出现在货币配置调整的过程中，找不到则会被强制改成默认货币。
         */
        $this->setCurrentCurrency($this->baseCurrecy);

        return $price;
    }

    /**
     * ISO-4217 特殊小数位数货币映射配置（0位小数与3位小数等）
     */
    public $currencyDecimalPrecision = [
        'JPY' => 0,
        'KRW' => 0,
        'VND' => 0,
        'CLP' => 0,
        'PYG' => 0,
        'ISK' => 0,
        'BHD' => 3,
        'KWD' => 3,
        'OMR' => 3,
        'JOD' => 3,
        'TND' => 3,
    ];

    /**
     * 获取指定货币的小数位数精度（默认2位，支持日元/韩元0位、科威特第纳尔3位等）
     * @param string $currencyCode
     * @return int
     */
    public function getCurrencyDecimalPrecision($currencyCode = '')
    {
        if (!$currencyCode) {
            $currencyCode = $this->getCurrentCurrency();
        }
        $currencyCode = strtoupper(trim((string)$currencyCode));
        if (isset($this->currencyDecimalPrecision[$currencyCode])) {
            return (int)$this->currencyDecimalPrecision[$currencyCode];
        }
        return 2;
    }

    /**
     * property $price|Float ，默认货币的价格
     * property $currencyCode|String，货币简码,譬如 USD
     * property $scale|Int|null，小数位数精度，默认自动根据目标货币决定
     * 根据基础货币，得到相应货币的价格（采用高精度四舍五入，消除截断误差）
     */
    public function getCurrencyPrice($price, $currencyCode, $scale = null)
    {
        if (isset($this->currencys[$currencyCode]['rate'])) {
            $rate = $this->currencys[$currencyCode]['rate'];
            if ($rate) {
                if ($scale === null) {
                    $scale = $this->getCurrencyDecimalPrecision($currencyCode);
                }
                // 使用高精度中间位计算后四舍五入，避免 bcmul 直接截断导致的分角误差
                $calcScale = $scale + 4;
                $raw = bcmul((string)$price, (string)$rate, $calcScale);
                return number_format((float)$raw, $scale, '.', '');
            }
        }
        
        return null;
    }

    /**
     * @param $current_price | Float 当前货币下的价格
     * @param $current_currency | String 当前货币简码
     * @param $scale | Int|null 目标小数位数
     * @return 基础货币下的价格
     *         通过当前的货币价格得到基础货币的价格，反推计算采用四舍五入保证汇率转换一致性。
     */
    public function getBaseCurrencyPrice($current_price, $current_currency = '', $scale = null)
    {
        if (!$current_currency) {
            $current_currency = $this->getCurrentCurrency();
        }
        if (isset($this->currencys[$current_currency]['rate'])) {
            $rate = $this->currencys[$current_currency]['rate'];
            if ($rate) {
                if ($scale === null) {
                    $scale = $this->getCurrencyDecimalPrecision($this->baseCurrecy);
                }
                $calcScale = $scale + 4;
                $raw = bcdiv((string)$current_price, (string)$rate, $calcScale);
                return number_format((float)$raw, $scale, '.', '');
            }
        }
    }

    /**
     * @param $currencyCode | 货币简码
     * 初始化货币信息，在service Store bootstrap(Yii::$app->store->bootstrap()), 中会被调用
     * 1. 如果 $this->defaultCurrency 和 $this->baseCurrecy 没有设置，将会报错。
     * 2. 如果 传递参数$currencyCode为空，则会使用默认货币
     */
    public function initCurrency($currencyCode = '')
    {
        if (!$this->defaultCurrency) {
            
            throw new InvalidConfigException('defautlt currency must config');
        }
        if (!$this->baseCurrecy) {
            
            throw new InvalidConfigException('base currency must config');
        }
        if (!$this->getCurrentCurrency()) {
            if (!$currencyCode) {
                $currencyCode = $this->defaultCurrency;
            }
            $this->setCurrentCurrency($currencyCode);
        }
    }

    /**
     * @param $currencyCode | String ， 货币简码，如果参数$currencyCode为空，则取当前的货币简码
     * @return array
     *               得到货币的详细信息,数据格式如下：
     *               [
     *               'code' 		=> $code ,
     *               'rate' 		=> $rate ,
     *               'symbol' 	=> $symbol ,
     *               ]
     */
    public function getCurrencyInfo($currencyCode = '')
    {
        if (!$currencyCode) {
            $currencyCode = $this->getCurrentCurrency();
        }

        if (!$this->getCurrencys($currencyCode)) {
            $currencyCode = $this->defaultCurrency;
            $this->setCurrentCurrency($currencyCode);
        }
        
        return $this->getCurrencys($currencyCode);
    }

    /**
     * 得到当前的货币。
     */
    public function getCurrentCurrency()
    {
        if (!$this->_currentCurrencyCode) {
            $this->_currentCurrencyCode = Yii::$service->session->get(self::CURRENCY_CURRENT);
        }

        return $this->_currentCurrencyCode;
    }

    /**
     * @param $currencyCode | String， 当前的货币简码
     * 设置当前的货币。
     */
    public function setCurrentCurrency($currencyCode)
    {
        if (!$this->isCorrectCurrency($currencyCode)) {
            $currencyCode = $this->defaultCurrency;
        }
        if ($currencyCode) {
            if (!Yii::$service->store->isAppserver()) {
                Yii::$service->session->set(self::CURRENCY_CURRENT, $currencyCode);
            }
            $this->_currentCurrencyCode = $currencyCode;
            
            return true;
        }
    }
    
    protected $appserverCurrencyHeaderName = 'fecshop-currency';
    /**
     * appserver端初始化currency
     * 初始化货币services，直接从headers中取出来currency。进行set，这样currency就不会从session中读取
     * fecshop-2版本对于appserver已经抛弃session servcies
     */
    public function appserverSetCurrentCurrency()
    {
        if ($this->_currentCurrencyCode) {
            
            return true;
        }
        $header = Yii::$app->request->getHeaders();
        $currentCurrencyCode = $header->get($this->appserverCurrencyHeaderName);
        
        if (!$currentCurrencyCode) {
            $currentCurrencyCode = $this->defaultCurrency;
        }
        if (!$this->isCorrectCurrency($currentCurrencyCode)) {
            $currentCurrencyCode = $this->defaultCurrency;
        }
        $this->_currentCurrencyCode = $currentCurrencyCode;
        
        Yii::$app->response->getHeaders()->set($this->appserverCurrencyHeaderName, $this->_currentCurrencyCode);
    }

    /**
     * @param $currency | String 货币简码
     * @return bool
     *              检测当前传递的货币简码，是否在配置中存在，如果存在则返回true
     */
    protected function isCorrectCurrency($currencyCode)
    {
        if (isset($this->currencys[$currencyCode])) {
            
            return true;
        } else {
            
            return false;
        }
    }

    public function setCurrentCurrency2CNY()
    {
        return $this->setCurrentCurrency('CNY');
    }
}
