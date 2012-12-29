<?php

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');

register_shutdown_function('shutdown');

require_once('configs/global.config.php');
require_once('core/mysql.class.php');
require_once('core/template.class.php');
require_once('core/paymentgateway.class.php');
require_once('core/logicboxes.class.php');

// подключение к БД
$sql = new DatabaseConnection();

$lb = new LogicBoxesAPI($gatewaySecretKey);

// если передано имя платежного шлюза, подключаем его
if (isset($_REQUEST['pg'])) {
    $pg = $_REQUEST['pg'] . '.class.php';
    includePaymentGateway($pg);
}

function includePaymentGateway($gatewayClassFileName) {
    global $paymentGateways, $pgw;

    $pgw = null;

    if (in_array($gatewayClassFileName, $paymentGateways))
    if (file_exists('gateways/'.$gatewayClassFileName)) {
        include('gateways/'.$gatewayClassFileName);
        $pgClassName = PaymentGateway::getClassName(str_replace('.class.php', '', $gatewayClassFileName));
        if (class_exists($pgClassName)) {
            $paymentGatewaysNames = array_flip($paymentGateways);
            eval('$pgw = new '.$pgClassName.'("'.$paymentGatewaysNames[$gatewayClassFileName].'", "'.str_replace('.class.php', '', $gatewayClassFileName).'");');
        }
    }
}

function shutdown() {
    global $sql;

    if (is_a($sql, 'DatabaseConnection'))
        $sql->closeConnection();
}
