<?php

class LogicBoxesAPI {

    var $key;

    var $formParameters = array();
    var $formActionURL = array('url' => '', 'method' => '');

    var $transid = '';
    var $userid = 0;
    var $name = '';
    var $emailAddr = '';
    var $sellingcurrencyamount = 0;
    var $accountingcurrencyamount = 0;
    var $redirectUrl = '';
    var $description = '';

    function LogicBoxesAPI($gatewayKey) {
        $this->key = $gatewayKey;
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

    function generateFormData($paymentID, $paymentStatus = 'N') {
        global $sql, $pgw;

        $this->setFormParameter('transid', $paymentID);
        $this->setFormParameter('status', $paymentStatus);

        list($sellingcurrencyamount, $accountingcurrencyamount) = $sql->fetchRow($sql->query('SELECT sellingcurrencyamount,accountingcurrencyamount FROM '.$sql->prefix.'payments WHERE id="'.$sql->escapeString($paymentID).'"'));
        $this->setFormParameter('sellingamount', ((fmod($sellingcurrencyamount,1) == 0) ? sprintf('%0.1f',$sellingcurrencyamount) : floatval($sellingcurrencyamount)));
        $this->setFormParameter('accountingamount', ((fmod($accountingcurrencyamount,1) == 0) ? sprintf('%0.1f',$accountingcurrencyamount) : floatval($accountingcurrencyamount)));

        $sql->query('UPDATE '.$sql->prefix.'payments SET gateway="'.$sql->escapeString($pgw->getName()).'", paid="'.$sql->escapeString($paymentStatus).'" WHERE id="'.$sql->escapeString($paymentID).'"');
    }

    function getFormData() {
        $this->setFormParameter('rkey', $this->generateRKey());
        $this->setFormParameter('checksum', $this->generateChecksum());

        $formData = array(
            'url' => $this->formActionURL['url'],
            'method' => $this->formActionURL['method'],
            'fields' => $this->formParameters
        );

        return $formData;
    }

    function generateRKey() {
        srand((double)microtime()*1000000);
        return rand();
    }

    function generateChecksum() {
        $fields = array(
            $this->getFormParameter('transid'),
            $this->getFormParameter('sellingamount'),
            $this->getFormParameter('accountingamount'),
            $this->getFormParameter('status'),
            $this->getFormParameter('rkey'),
            $this->key
        );
        $str = implode('|', $fields);
        return md5($str);
    }

    function verifyChecksum() {

        $this->transid = DatabaseConnection::unescapeString($_GET['transid']);
        $this->userid = intval($_GET['userid']);
        $this->name = DatabaseConnection::unescapeString($_GET['name']);
        $this->emailAddr = DatabaseConnection::unescapeString($_GET['emailAddr']);
        $this->sellingcurrencyamount = floatval($_GET['sellingcurrencyamount']);
        $this->accountingcurrencyamount = floatval($_GET['accountingcurrencyamount']);
        $this->redirectUrl = DatabaseConnection::unescapeString($_GET['redirecturl']);
        $this->description = DatabaseConnection::unescapeString($_GET['description']);

        $fields = array(
            intval($_GET['paymenttypeid']),
            $this->transid,
            $this->userid,
            DatabaseConnection::unescapeString($_GET['usertype']),
            DatabaseConnection::unescapeString($_GET['transactiontype']),
            DatabaseConnection::unescapeString($_GET['invoiceids']),
            DatabaseConnection::unescapeString($_GET['debitnoteids']),
            $this->description,
            DatabaseConnection::unescapeString($_GET['sellingcurrencyamount']),
            DatabaseConnection::unescapeString($_GET['accountingcurrencyamount']),
            $this->key
        );
        $str = implode('|', $fields);
        $generatedCheckSum = md5($str);
        $checksum = DatabaseConnection::unescapeString($_GET['checksum']);

        return ($generatedCheckSum == $checksum);
    }

    function getPaymentID() {
        return $this->transid;
    }

    function getRedirectUrl() {
        return $this->redirectUrl;
    }

    function getAmount() {
        return $this->sellingcurrencyamount;
    }

    function getDescription() {
        return (trim($this->description)!='' ? $this->description : 'Добавление средств на счет '.$this->userid);
    }

    function createPayment() {
        global $sql;

        $result = $sql->query('INSERT INTO '.$sql->prefix.'payments VALUES ("'.$sql->escapeString($this->transid).'", '.intval($this->userid).', "'.$sql->escapeString($this->name.' <'.$this->emailAddr.'>').'", "'.$sql->escapeString($_SERVER['REMOTE_ADDR']).'", '.floatval($this->sellingcurrencyamount).', '.floatval($this->accountingcurrencyamount).', "", "", 0.00, "N", "", NOW())', false);

        if ($result === false) {
            if (PaymentGateway::getPaymentStatus($this->transid) == 'N')
                $sql->query('UPDATE '.$sql->prefix.'payments SET userid='.intval($this->userid).', name="'.$sql->escapeString($this->name.' <'.$this->emailAddr.'>').'", payerip="'.$sql->escapeString($_SERVER['REMOTE_ADDR']).'", sellingcurrencyamount='.floatval($this->sellingcurrencyamount).', accountingcurrencyamount='.floatval($this->accountingcurrencyamount).', gateway="", gatewayip="", receivedamount=0.00, paid="N", payerinfo="", `date`=NOW() WHERE id="'.$sql->escapeString($this->transid).'"');
            else die('This transaction is already finished!');
        }
    }
}
