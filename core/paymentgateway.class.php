<?php

class PaymentGateway {

    var $gatewayName = '';

    var $formParameters = array();
    var $formActionURL = array('url' => '', 'method' => '');

    var $allowedIPs = array();

    var $configFileName = '';

    var $paymentSystemRate = 1;

    var $NonAutomaticPaymentsProcessing = false;

    function PaymentGateway($gatewayName, $configFileName = null) {

        // config-file including
        if (!empty($configFileName)) {
            if (!preg_match('#/|\\\#', $configFileName)) { // no "\" and "/" chars in filename
                $fullPath = realpath(dirname(__FILE__) . '/../configs/' . $configFileName . '.config.php');
                if (file_exists($fullPath))
                    $this->configFileName = $fullPath;
            }
        }

        $this->gatewayName = $gatewayName;

    }

    function getName() {
        return $this->gatewayName;
    }

    function getClassName($filename) {
        return preg_replace('/\W/m','',$filename);
    }

    function getConfigFileName() {
        return $this->configFileName;
    }

    function getScriptURL() {
        $url = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']));
        if (substr($url,strlen($url)-1) != '/') $url .= '/';

        return $url;
    }

    function setFormParameter($paramName, $paramValue = '') {
        $this->formParameters[$paramName] = $paramValue;
    }

    function getFormParameter($paramName) {
        return isset($this->formParameters[$paramName])?$this->formParameters[$paramName]:'';
    }

    function setFormURL($url, $method = 'post') {
        $this->formActionURL = array(
            'url' => $url,
            'method' => $method
        );
    }

    function getFormData() {
        $formData = array(
            'url' => $this->formActionURL['url'],
            'method' => $this->formActionURL['method'],
            'fields' => $this->formParameters
        );

        return $formData;
    }

    function setAllowedIP($ipArray) {
        if (is_array($ipArray) && !empty($ipArray))
            $this->allowedIPs = $ipArray;
    }

    function setRate($newRate) {
        $this->paymentSystemRate = $newRate;
    }

    function getRate() {
        return $this->paymentSystemRate;
    }

    function checkRequestIP() {
        if (empty($this->allowedIPs)) return true;

        return in_array($_SERVER['REMOTE_ADDR'], $this->allowedIPs);
    }

    function getAmountFromDB($paymentID) {
        global $sql;

        list($amount) = $sql->fetchRow($sql->query('SELECT sellingcurrencyamount FROM '.$sql->prefix.'payments WHERE id="'.$sql->escapeString($paymentID).'"'));
        return floatval($amount);
    }

    function calculateAmount($sellingCurrencyAmount) {
        return ceil($sellingCurrencyAmount*$this->getRate()*100)/100;
    }

    function confirmPayment($paymentID, $amount, $payerInfo) {
        global $sql;

        list($paid) = $sql->fetchRow($sql->query('SELECT paid FROM '.$sql->prefix.'payments WHERE id="'.$sql->escapeString($paymentID).'"'));
        if ($paid == 'P') $paid = 'YP';
        else $paid = 'Y';

        $sql->query('UPDATE '.$sql->prefix.'payments
        SET gateway="'.$sql->escapeString($this->gatewayName).'",
            gatewayip="'.$sql->escapeString($_SERVER['REMOTE_ADDR']).'",
            receivedamount='.floatval($amount).',
            paid="'.$paid.'",
            payerinfo="'.$sql->escapeString($payerInfo).'",
            `date`=NOW()
        WHERE id="'.$sql->escapeString($paymentID).'"');
    }

    function getPaymentStatus($paymentID) {
        global $sql;

        list($paid) = $sql->fetchRow($sql->query('SELECT paid FROM '.$sql->prefix.'payments WHERE id="'.$sql->escapeString($paymentID).'"'));

        return $paid;
    }

    function processingSupport() {
        return $this->NonAutomaticPaymentsProcessing;
    }

}
