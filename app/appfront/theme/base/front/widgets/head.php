<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */
?>
<meta charset="<?= Yii::$app->charset ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="referrer" content="strict-origin-when-cross-origin" />
<?= \yii\helpers\Html::csrfMetaTags() ?>
<title><?= \yii\helpers\Html::encode(\Yii::$app->view->title); ?></title>
<link rel="shortcut icon" href="<?=  Yii::$service->url->getUrl('favicon.ico'); ?>">
<link rel="apple-touch-icon" href="<?=  Yii::$service->url->getUrl('apple-touch-icon.png'); ?>">
<meta name="robots" content="INDEX,FOLLOW" />
<?php $parentThis->head() ?>
