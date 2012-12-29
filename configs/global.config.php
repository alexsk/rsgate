<?php

// 32-байтный ключ платежного интерфейса из панели управления
$gatewaySecretKey = '';

// настройки подключения к БД
$dbHost = 'localhost';    // сервер БД
$dbUser = 'root';         // имя пользователя
$dbPassword = '';         // пароль
$dbName = '';             // название БД
$dbPrefix = '';           // префикс таблиц

// доступные платежные шлюзы
$paymentGateways = array(
    // шаблон заполнения:
    // 'Название платежного шлюза' => 'имя файла-обработчика платежей',

    //'WebMoney WMZ' => 'webmoney.wmz.class.php',
    //'WebMoney WMR' => 'webmoney.wmr.class.php',
    //'WebMoney WMU' => 'webmoney.wmu.class.php',
    //'WebMoney WME' => 'webmoney.wme.class.php',
    //'WebMoney WMB' => 'webmoney.wmb.class.php',
    //'RBKMoney' => 'rbkmoney.class.php',
    //'USD E-Gold' => 'e-gold.class.php',
    //'ROBOKASSA' => 'robokassa.class.php',
    //'UkrMoney UAH' => 'ukrmoney.uah.class.php',
    //'UkrMoney USD' => 'ukrmoney.usd.class.php',
    //'UkrMoney EUR' => 'ukrmoney.eur.class.php',
    //'Z-PAYMENT' => 'z-payment.class.php',
    //'Безнал. оплата' => 'wire.class.php',
    //'Prochange' => 'prochange.class.php',
);

// логин и пароль для входа в раздел для администратора
$adminLogin = 'admin';
$adminPassword = 'superpass';

// адрес вашей реселлерской панели, обязательно начиная с http:// (или https://) и заканчивая слешем /
$resellerPanelURL = 'http://control-panel.com/';
