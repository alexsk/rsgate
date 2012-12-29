<?php

class Prochange extends PaymentGateway {

    var $secretKey;
    var $userFields = array();

    function Prochange($gatewayName, $configFileName = null) {
        if (!empty($configFileName))
            parent::PaymentGateway($gatewayName, $configFileName);
        else
            parent::PaymentGateway($gatewayName, __CLASS__);

        $this->NonAutomaticPaymentsProcessing = true;

        $configFile = $this->getConfigFileName();
        if (empty($configFile)) die("Error: Can't find ".__CLASS__." configuration file.");

        include($configFile);
        $scriptURL = $this->getScriptURL();
        $this->setFormParameter('PRO_CLIENT', $proClient);
        $this->setFormParameter('PRO_RA', $proRa);
        $this->secretKey = $secretKey;
        $this->setRate(floatval($rate));

        $this->setAllowedIP($allowedIPs);

        $this->setFormURL('http://merchant.prochange.ru/pay.pro');
    }

    function addFormParameter($paramName, $paramValue = '') {
        $this->userFields[$paramName] = $paramValue;
        $this->setFormParameter('PRO_FIELD_'.count($this->userFields), $paramValue);
    }

    function returnFormParameter($paramName) {
        return $this->getFormParameter('PRO_FIELD_1');
    }

    function paymentNotification() {

        if ($this->secretKey != $_POST['PRO_SECRET_KEY']) return false; // проверка secretKey не прошла

        $amount = $this->calculateAmount($this->getAmountFromDB(DatabaseConnection::unescapeString($_POST['PRO_FIELD_2'])));
        if (floatval($amount) != floatval($_POST['PRO_SUMMA_OUT'])) return false; // неверная сумма платежа

        if (!$this->checkRequestIP()) return false; // не пройдена проверка IP-адреса платежного интерфейса

        // информация о покупателе
        $payerInfo = "Кошелек плательщика: ".$_POST['PRO_PAYER_PURSE'];

        // подтверждаем платеж
        $this->confirmPayment(DatabaseConnection::unescapeString($_POST['PRO_FIELD_2']), $amount, $payerInfo);
        print 'OK';
        return true;
    }

    function paymentSucceeded() {
        foreach ($_POST as $key => $value) {
            $this->setFormParameter($key, DatabaseConnection::unescapeString($value));
        }
        return true;
    }

    function paymentFailed() {
        global $sql;

        foreach ($_POST as $key => $value) {
            $this->setFormParameter($key, DatabaseConnection::unescapeString($value));
        }

        $sql->query('UPDATE '.$sql->prefix.'payments
        SET gateway="'.$sql->escapeString($this->getName()).'",
            `date`=NOW()
        WHERE id="'.$sql->escapeString(DatabaseConnection::unescapeString($_POST['PRO_FIELD_2'])).'"');
        return true;
    }

    function getPaymentID() {
        return DatabaseConnection::unescapeString($_POST['PRO_FIELD_2']);
    }

    function setPaymentID($paymentID) {
        $this->setFormParameter('PRO_FIELD_2', $paymentID);
    }

    function setPaymentAmount($paymentAmount) {
        $this->setFormParameter('PRO_WMZ', floatval($paymentAmount));
    }

    function setPaymentDescription($paymentDescription) {
        $this->setFormParameter('PRO_PAYMENT_DESC', $paymentDescription);
    }

}
