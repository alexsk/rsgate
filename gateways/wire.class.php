<?php

class Wire extends PaymentGateway {
    
    function Wire($gatewayName, $configFileName = null) {
        if (!empty($configFileName))
            parent::PaymentGateway($gatewayName, $configFileName);
        else
            parent::PaymentGateway($gatewayName, __CLASS__);
            
        $this->NonAutomaticPaymentsProcessing = true;
            
        $configFile = $this->getConfigFileName();
        if (empty($configFile)) die("Error: Can't find ".__CLASS__." configuration file.");
        
        include($configFile);
        $this->setRate(floatval($rate));
        
        $this->setFormURL($this->getScriptURL().'wiretransfer.php');
    }
    
    function addFormParameter($paramName, $paramValue = '') {
        $this->setFormParameter($paramName, $paramValue);
    }
    
    function returnFormParameter($paramName) {
        return $this->getFormParameter($paramName);
    }
    
    function getFormData() {
        global $lb;
        $this->setFormParameter('payerName', $lb->name);
        return parent::getFormData();
    }
    
    function paymentNotification() {
        return true;
    }
    
    function paymentSucceeded() {
        foreach ($_POST as $key => $value) {
            $this->setFormParameter($key, DatabaseConnection::unescapeString($value));
        }
        return true;
    }
    
    function paymentFailed() {
        return true;
    }
    
    function getPaymentID() {
        return $this->getFormParameter('payment_id');
    }
    
    function setPaymentID($paymentID) {
        $this->setFormParameter('payment_id', $paymentID);
    }
    
    function setPaymentAmount($paymentAmount) {
        $this->setFormParameter('payment_amount', floatval($paymentAmount));
    }
    
    function setPaymentDescription($paymentDescription) {
        $this->setFormParameter('payment_description', $paymentDescription);
    }
    
}
