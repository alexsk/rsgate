<?php

class WebMoney extends PaymentGateway {
    
    var $secretKey;

    function WebMoney($gatewayName, $configFileName = null) {
        if (!empty($configFileName))
            parent::PaymentGateway($gatewayName, $configFileName);
        else
            parent::PaymentGateway($gatewayName, __CLASS__);
            
        $this->setFormURL('https://merchant.webmoney.ru/lmi/payment.asp');
    }
    
    function addFormParameter($paramName, $paramValue = '') {
        $this->setFormParameter($paramName, $paramValue);
    }
    
    function returnFormParameter($paramName) {
        return $this->getFormParameter($paramName);
    }
    
    function paymentNotification() {
        
        if (isset($_POST['LMI_PREREQUEST'])) return true; // предварительный запрос не обрабатываем

        $hash = $_POST['LMI_PAYEE_PURSE'].$_POST['LMI_PAYMENT_AMOUNT'].$_POST['LMI_PAYMENT_NO'].$_POST['LMI_MODE'].$_POST['LMI_SYS_INVS_NO'].$_POST['LMI_SYS_TRANS_NO'].$_POST['LMI_SYS_TRANS_DATE'].$this->secretKey.$_POST['LMI_PAYER_PURSE'].$_POST['LMI_PAYER_WM'];
        $hash = md5($hash);
        if (strtoupper($hash) != strtoupper($_POST['LMI_HASH'])) return false; // проверка контрольной подписи не прошла

        if (strtoupper($this->getFormParameter('LMI_PAYEE_PURSE')) != strtoupper($_POST['LMI_PAYEE_PURSE'])) return false; // неверный кошелек получателя

        $amount = $this->calculateAmount($this->getAmountFromDB(DatabaseConnection::unescapeString($_POST['transid'])));
        if (floatval($amount) != floatval($_POST['LMI_PAYMENT_AMOUNT'])) return false; // неверная сумма платежа
        
        if (!$this->checkRequestIP()) return false; // не пройдена проверка IP-адреса платежного интерфейса

        // оплата производилась через Paymer или WM-картой
        if (isset($_POST['LMI_PAYMER_NUMBER'])) {
            $payerInfo = 'Номер чека Paymer: '.$_POST['LMI_PAYMER_NUMBER']."\nE-mail: ".$_POST['LMI_PAYMER_EMAIL'];
        }
        // оплата производилась через Telepat
        elseif (isset($_POST['LMI_TELEPAT_PHONENUMBER'])) {
            $payerInfo = 'WMID: '.$_POST['LMI_PAYER_WM']."\nКошелек: ".$_POST['LMI_PAYER_PURSE']."\nНомер телефона: ".$_POST['LMI_TELEPAT_PHONENUMBER']."\nНомер платежа в Телепате: ".$_POST['LMI_TELEPAT_ORDERID'];
        }
        // оплата производилась через WM Keeper Classic/Light
        else {
            $payerInfo = 'WMID: '.$_POST['LMI_PAYER_WM']."\nКошелек: ".$_POST['LMI_PAYER_PURSE'];
        } 
        // подтверждаем платеж
        $this->confirmPayment(DatabaseConnection::unescapeString($_POST['transid']), $amount, $payerInfo);
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
        WHERE id="'.$sql->escapeString(DatabaseConnection::unescapeString($_POST['transid'])).'"');
        return true;
    }
    
    function getPaymentID() {
        return DatabaseConnection::unescapeString($_POST['transid']);
    }
    
    function setPaymentID($paymentID) {
        $this->setFormParameter('transid', $paymentID);
    }
    
    function setPaymentAmount($paymentAmount) {
        $this->setFormParameter('LMI_PAYMENT_AMOUNT', floatval($paymentAmount));
    }
    
    function setPaymentDescription($paymentDescription) {
        $this->setFormParameter('LMI_PAYMENT_DESC', $paymentDescription);
    }

}
