<?php

class Robokassa extends PaymentGateway {
    
    var $merchantPass1;
    var $merchantPass2;
    var $additionalFields = array();
    
    function Robokassa($gatewayName, $configFileName = null) {
        if (!empty($configFileName))
            parent::PaymentGateway($gatewayName, $configFileName);
        else
            parent::PaymentGateway($gatewayName, __CLASS__);
            
        $configFile = $this->getConfigFileName();
        if (empty($configFile)) die("Error: Can't find ".__CLASS__." configuration file.");
        
        include($configFile);
        $scriptURL = $this->getScriptURL();
        $this->setFormParameter('MrchLogin', $roboxLogin);
        $this->setFormParameter('InvId', '0');
        $this->merchantPass1 = $roboxMerchantPass1;
        $this->merchantPass2 = $roboxMerchantPass2;
        $this->setRate(floatval($rate));
        
        $this->setAllowedIP($allowedIPs);
        
        $this->setFormURL('https://merchant.roboxchange.com/Index.aspx');
    }
    
    function addFormParameter($paramName, $paramValue = '') {
        $this->additionalFields['shp_'.$paramName] = $paramValue;
        $this->setFormParameter('shp_'.$paramName, $paramValue);
    }
    
    function returnFormParameter($paramName) {
        return $this->getFormParameter('shp_'.$paramName);
    }
    
    function getFormData() {
        $crc = $this->getFormParameter('MrchLogin').':'.$this->getFormParameter('OutSum').':'.$this->getFormParameter('InvId').':'.$this->merchantPass1;
        ksort($this->additionalFields);
        foreach ($this->additionalFields as $paramName => $paramValue) {
            $crc .= ':'.$paramName.'='.$paramValue;
        }
        $this->setFormParameter('SignatureValue', md5($crc));
        return parent::getFormData();
    }
    
    function paymentNotification() {
        
        foreach ($_POST as $key => $value) {
            if (substr($key,0,3) == 'shp')
                $this->additionalFields[$key] = DatabaseConnection::unescapeString($value);
        }
        
        $hash = $_POST['OutSum'].':'.$_POST['InvId'].':'.$this->merchantPass2;
        ksort($this->additionalFields);
        foreach ($this->additionalFields as $paramName => $paramValue) {
            $hash .= ':'.$paramName.'='.$paramValue;
        }
        $hash = md5($hash);
        if (strtoupper($hash) != strtoupper($_POST['SignatureValue'])) return false; // проверка контрольной подписи не прошла
        
        $amount = $this->calculateAmount($this->getAmountFromDB(urldecode(DatabaseConnection::unescapeString($_POST['shp_transid']))));
        if (floatval($amount) != floatval($_POST['OutSum'])) return false; // неверная сумма платежа
        
        if (!$this->checkRequestIP()) return false; // не пройдена проверка IP-адреса платежного интерфейса

        // никакой информации о покупателе робокс не дает :(
        $payerInfo = '';
        
        // подтверждаем платеж
        $this->confirmPayment(urldecode(DatabaseConnection::unescapeString($_POST['shp_transid'])), $amount, $payerInfo);
        print 'OK'.$_POST['InvId'];
        return true;
    }
    
    function paymentSucceeded() {
        foreach ($_POST as $key => $value) {
            $this->setFormParameter($key, urldecode(DatabaseConnection::unescapeString($value)));
        }
        return true;
    }
    
    function paymentFailed() {
        global $sql;
        
        foreach ($_POST as $key => $value) {
            $this->setFormParameter($key, urldecode(DatabaseConnection::unescapeString($value)));
        }
        
        $sql->query('UPDATE '.$sql->prefix.'payments
        SET gateway="'.$sql->escapeString($this->getName()).'",
            `date`=NOW()
        WHERE id="'.$sql->escapeString(urldecode(DatabaseConnection::unescapeString($_POST['shp_transid']))).'"');
        return true;
    }
    
    function getPaymentID() {
        return urldecode(DatabaseConnection::unescapeString($_POST['shp_transid']));
    }
    
    function setPaymentID($paymentID) {
        $this->addFormParameter('transid', $paymentID);
    }
    
    function setPaymentAmount($paymentAmount) {
        $this->setFormParameter('OutSum', floatval($paymentAmount));
    }
    
    function setPaymentDescription($paymentDescription) {
        $this->setFormParameter('Desc', $paymentDescription);
    }

}
