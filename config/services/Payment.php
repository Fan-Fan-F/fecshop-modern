<?php
/**
 * FecShop file.
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */
return [
    'payment' => [
        'class' => 'fecshop\services\Payment',
        /*
        'noRelasePaymentMethod' => ['check_money'],  	# 不需要释放库存的支付方式。譬如货到付款，在系统中
                                                    # pending订单，如果一段时间未付款，会释放产品库存，但是货到付款类型的订单不会释放，
                                                    # 如果需要释放产品库存，客服在后台取消订单即可释放产品库存。
       'paymentConfig' => [        // 支付方式配置
            'standard' => [            // 标准支付类型：在购物车页面进入下单页面，填写支付信息，然后跳转到第三方支付网站的支付类型。
                'check_money' => [    // 货到付款类型。
                    'label'                => 'Check / Money Order',
                    //'image' => ['images/mastercard.png','common'] ,# 支付页面显示的图片。
                    'supplement'               => 'Off-line Money Payments', // 补充信息
                    'style'                    => '<style></style>',  // 补充css，您可以在这里填写一些css
                    'start_url'                => '@homeUrl/payment/checkmoney/start',    // 点击按钮后，跳转的url，在这个url里面写支付跳转前的提交信息。
                    'success_redirect_url'     => '@homeUrl/payment/success',            // 在支付平台支付成功后，返回的页面
                ],
                
                'paypal_standard' => [
                    // 订单生成后，跳转到支付开始页面的url
                    'start_url'            => '@homeUrl/payment/paypal/standard/start',
                    // 下面是沙盒地址，线上地址为：https://api-3t.paypal.com/nvp，
                    // 这个url的作用用于 Yii::$service->payment->paypal->PPHttpPost5 ，发起一些api请求
                    // 譬如获取token，获取paypal存储的address（购物车快捷支付），发起扣款请求
                    'nvp_url'  => 'https://api-3t.sandbox.paypal.com/nvp',
                    // 下面是沙盒地址，线上地址为：https://www.paypal.com/cgi-bin/webscr
                    // 获取token后，通过这个url跳转到paypal的url地址，另外paypal的IPN消息的合法性认证，也是使用的这个url
                    // 也就是  Yii::$service->payment->paypal->getVerifyUrl()
                    'webscr_url'  => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
                    'account'  => 'YOUR_PAYPAL_API_ACCOUNT',
                    'password' => 'YOUR_PAYPAL_API_PASSWORD',
                    'signature'=> 'YOUR_PAYPAL_API_SIGNATURE',
                    'label'=> 'PayPal Express Payments',
                    // 跳转到paypal确认后，跳转到fecshop的url
                    'return_url' => '@homeUrl/payment/paypal/standard/review',
                    // 取消支付后，返回fecshop的url
                    'cancel_url' => '@homeUrl/payment/paypal/standard/cancel',
                    // 支付成功后，fecshop跳转的url
                    'success_redirect_url'    => '@homeUrl/payment/success',
                    // paypal发送IPN，fecshop用于接收IPN消息的地址。
                    'ipn_url' => '@homeUrl/payment/paypal/standard/ipn',
                ],
                
                'alipay_standard' => [
                    'label'=> '支付宝支付',
                    // 跳转开始URL
                    'start_url'             => '@homeUrl/payment/alipay/standard/start',
                    // 支付完成后，跳转的地址。
                    'return_url'            => '@homeUrl/payment/alipay/standard/review',
                    // 支付宝发送消息，接收的地址。
                    'ipn_url'               => '@homeUrl/payment/alipay/standard/ipn',
                    'success_redirect_url'  => '@homeUrl/payment/success',
                ],
                
            ],
            'express' => [    // 在购物车页面直接跳转到支付平台，譬如paypal快捷支付方式。
                'paypal_express' => [
                    // 下面是沙盒地址，线上地址为：https://api-3t.paypal.com/nvp，
                    // 这个url的作用用于 Yii::$service->payment->paypal->PPHttpPost5 ，发起一些api请求
                    // 譬如获取token，获取paypal存储的address（购物车快捷支付），发起扣款请求
                    'nvp_url'  => 'https://api-3t.sandbox.paypal.com/nvp',
                    // 下面是沙盒地址，线上地址为：https://www.paypal.com/cgi-bin/webscr
                    // 获取token后，通过这个url跳转到paypal的url地址，另外paypal的IPN消息的合法性认证，也是使用的这个url
                    // 也就是  Yii::$service->payment->paypal->getVerifyUrl()
                    'webscr_url'  => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
                    'account'  => 'YOUR_PAYPAL_API_ACCOUNT',
                    'password' => 'YOUR_PAYPAL_API_PASSWORD',
                    'signature'=> 'YOUR_PAYPAL_API_SIGNATURE',
                    'label'=> 'PayPal Express Payments',
                    // 跳转到paypal确认后，跳转到fecshop的url
                    'return_url' => '@homeUrl/payment/paypal/express/review',
                    // 取消支付后，返回fecshop的url
                    'cancel_url' => '@homeUrl/payment/paypal/express/cancel',
                    // 支付成功后，fecshop跳转的url
                    'success_redirect_url'    => '@homeUrl/payment/success',
                    // paypal发送IPN，fecshop用于接收IPN消息的地址。
                    'ipn_url' => '@homeUrl/payment/paypal/express/ipn',
                ],
            ],
        ],
        */
        'childService' => [
            'paypalCart' => [
                'class'    => 'fecshop\services\payment\PaypalCart',
            ],
            'paypal' => [
                'class'    => 'fecshop\services\payment\Paypal',
                /*
                'express_payment_method' => 'paypal_express',
                'version' => '109.0',

                # 是否使用证书的方式进行paypal api对接（https ssl）
                # 如果配置为true，那么必须在crt_file中配置证书地址。
                # 默认不使用证书验证
                'use_local_certs' => false,
                'crt_file' 	=> [
                    'www.paypal.com' 	=>'@fecshop/services/payment/cert/paypal.crt',
                    'api-3t.paypal.com' =>'@fecshop/services/payment/cert/api-3tsandboxpaypalcom.crt',

                ],
                */
            ],
            'alipay' => [
                'class'         => 'fecshop\services\payment\Alipay',
                // 商家appId
                //'appId'       => 'YOUR_ALIPAY_APP_ID',
                // 应用私钥，可以在这里通过工具生成：https://docs.open.alipay.com/291/105971/
                //'rsaPrivateKey' => 'YOUR_ALIPAY_RSA_PRIVATE_KEY',
                // 支付宝公钥，注意，这里不是应用公钥，需要把应用公钥提交后获取的支付宝公钥
                // 对于沙盒账户的步骤可以参看：http://blog.csdn.net/terry_water/article/details/75258175
                //'alipayrsaPublicKey' => 'YOUR_ALIPAY_RSA_PUBLIC_KEY',
                'format'        => 'json',
                'charset'       => 'utf-8',
                'signType'      => 'RSA2',
                //'devide'        => 'pc' ,  // 填写pc或者wap，pc代表pc机浏览器支付类型，wap代表手机浏览器支付类型 
                // 下面是沙盒地址， 正式环境请改为：https://openapi.alipay.com/gateway.do
                //'gatewayUrl'    => 'https://openapi.alipaydev.com/gateway.do', 
            ],
            'wxpay' => [ //注意参数要与WxPay.Config中的一致
        		'class'         => 'fecshop\services\payment\Wxpay', 
        		'devide'        => 'pc',
                // 微信的授权信息在下面的文件中，因为微信支付php sdk的配置是写到常量文件中，因此只能这样了，无法通过注入的方式写入，具体的配置到下面的文件中进行配置。
                'configFile'    => '@common/config/payment/wxpay/lib/WxPay.Config.php',
                
            ],
            'wxpayJsApi' => [ //注意参数要与WxPay.Config中的一致
        		'class'         => 'fecshop\services\payment\WxpayJsApi', 
                'devide'        => 'wap',
                // 微信的授权信息在下面的文件中，因为微信支付php sdk的配置是写到常量文件中，因此只能这样了，无法通过注入的方式写入，具体的配置到下面的文件中进行配置。
                'configFile'    => '@common/config/payment/wxpay/lib/WxPay.Config.php',
                
            ],
            
            'wxpayH5' => [ //注意参数要与WxPay.Config中的一致
        		'class'         => 'fecshop\services\payment\WxpayH5', 
                'devide'        => 'wap',
                // 微信的授权信息在下面的文件中，因为微信支付php sdk的配置是写到常量文件中，因此只能这样了，无法通过注入的方式写入，具体的配置到下面的文件中进行配置。
                'configFile'    => '@common/config/payment/wxpay/lib/WxPay.Config.php',
                
            ],
            
            'wxpayMicro' => [ //注意参数要与WxPay.Config中的一致
        		'class'         => 'fecshop\services\payment\WxpayMicro', 
                'devide'        => 'wap',
                // 微信的授权信息在下面的文件中，因为微信支付php sdk的配置是写到常量文件中，因此只能这样了，无法通过注入的方式写入，具体的配置到下面的文件中进行配置。
                'configFile'    => '@common/config/payment/wxpay/lib/WxPay.Micro.Config.php',
                
            ],
        ],
    ],
];
