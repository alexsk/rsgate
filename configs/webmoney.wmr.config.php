<?php

// кошелек для приема платежей
$purse = 'R123456789012';

// секретный ключ для кошелька
$secretKey = '';

// курс данной платежной системы к валюте в панели управления доменами (целая и дробная части разделяются точкой)
// к оплате = стоимость * курс
$rate = 30;

// список IP-адресов, с которых разрешен прием подтверждений об оплате от платежной системы
// пример перечисления адресов:
// $allowedIPs = array('12.34.56.78','10.10.10.10','87.65.43.21');
// пустой список - доступ разрешен со всех адресов
$allowedIPs = array();
