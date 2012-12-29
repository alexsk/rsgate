<?php

require_once('gateways/webmoney.class.php');

class WebMoneyWMR extends WebMoney {
    
    function WebMoneyWMR($gatewayName, $configFileName = null) {
        if (!empty($configFileName))
            parent::WebMoney($gatewayName, $configFileName);
        else
            parent::WebMoney($gatewayName, __CLASS__);
        
        $configFile = $this->getConfigFileName();
        if (empty($configFile)) die("Error: Can't find ".__CLASS__." configuration file.");
        
        include($configFile);
        $this->setFormParameter('LMI_PAYEE_PURSE', $purse);
        $this->secretKey = $secretKey;
        $this->setRate(floatval($rate));
        
        $this->setAllowedIP($allowedIPs);
    }
}
