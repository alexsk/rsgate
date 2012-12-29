<?php

class RBKMoney extends PaymentGateway {
    
    var $secretKey;
    var $userFields = array();
    
    function RBKMoney($gatewayName, $configFileName = null) {
        if (!empty($configFileName))
            parent::PaymentGateway($gatewayName, $configFileName);
        else
            parent::PaymentGateway($gatewayName, __CLASS__);
        
        $this->NonAutomaticPaymentsProcessing = true;
            
        $configFile = $this->getConfigFileName();
        if (empty($configFile)) die("Error: Can't find ".__CLASS__." configuration file.");
        
        include($configFile);
        $scriptURL = $this->getScriptURL();
        $this->setFormParameter('eshopId', $eshopID);
        $this->setFormParameter('recipientCurrency', 'RUR');
        $this->setFormParameter('successUrl', $scriptURL.'?pg=rbkmoney&success');
        $this->setFormParameter('failUrl', $scriptURL.'?pg=rbkmoney&fail');
        $this->secretKey = $secretKey;
        $this->setRate(floatval($rate));
        
        $this->setAllowedIP($allowedIPs);
        
        $this->setFormURL('https://rbkmoney.ru/acceptpurchase.aspx');
    }
    
    function addFormParameter($paramName, $paramValue = '') {
        $this->userFields[$paramName] = $paramValue;
    }
    
    function returnFormParameter($paramName) {
        return (isset($_REQUEST[$paramName])?DatabaseConnection::unescapeString($_REQUEST[$paramName]):'');
    }
    
    function getFormData() {
        $additionalURL = '&orderId='.urlencode($this->getFormParameter('orderId'));
        foreach ($this->userFields as $paramName => $paramValue) {
            $additionalURL .= '&'.urlencode($paramName).'='.urlencode($paramValue);
        }
        $this->setFormParameter('successUrl', $this->getFormParameter('successUrl').$additionalURL);
        $this->setFormParameter('failUrl', $this->getFormParameter('failUrl').$additionalURL);
        return parent::getFormData();
    }
    
    function paymentNotification() {
        
        $hash = $_POST['eshopId'].'::'.$_POST['orderId'].'::'.$_POST['serviceName'].'::'.$_POST['eshopAccount'].'::'.$_POST['recipientAmount'].'::'.$_POST['recipientCurrency'].'::'.$_POST['paymentStatus'].'::'.$_POST['userName'].'::'.$_POST['userEmail'].'::'.$_POST['paymentData'].'::'.$this->secretKey;
        $hash = md5($hash);
        if (strtoupper($hash) != strtoupper($_POST['hash'])) return false; // проверка контрольной подписи не прошла
        
        if ($this->getFormParameter('eshopId') != $_POST['eshopId']) return false; // неверный номер сайта-получателя
        if ($this->getFormParameter('recipientCurrency') != $_POST['recipientCurrency']) return false; // неверная валюта платежа
        
        $amount = $this->calculateAmount($this->getAmountFromDB(DatabaseConnection::unescapeString($_POST['orderId'])));
        if (floatval($amount) != floatval($_POST['recipientAmount'])) return false; // неверная сумма платежа
        
        if ('5' != $_POST['paymentStatus']) return false; // статус=5 - платеж зачислен
        
        if (!$this->checkRequestIP()) return false; // не пройдена проверка IP-адреса платежного интерфейса

        // информация о покупателе
        $payerInfo = $_POST['userName']."\nE-mail: ".$_POST['userEmail'];
        
        // подтверждаем платеж
        $this->confirmPayment(DatabaseConnection::unescapeString($_POST['orderId']), $amount, $payerInfo);
        print 'OK';
        return true;
    }
    
    function paymentSucceeded() {
        return true;
    }
    
    function paymentFailed() {
        global $sql;
        
        $sql->query('UPDATE '.$sql->prefix.'payments
        SET gateway="'.$sql->escapeString($this->getName()).'",
            `date`=NOW()
        WHERE id="'.$sql->escapeString(DatabaseConnection::unescapeString($_REQUEST['orderId'])).'"');
        return true;
    }
    
    function getPaymentID() {
        return DatabaseConnection::unescapeString($_REQUEST['orderId']);
    }
    
    function setPaymentID($paymentID) {
        $this->setFormParameter('orderId', $paymentID);
    }
    
    function setPaymentAmount($paymentAmount) {
        $this->setFormParameter('recipientAmount', floatval($paymentAmount));
    }
    
    function setPaymentDescription($paymentDescription) {
        $this->setFormParameter('serviceName', $paymentDescription);
    }

}
