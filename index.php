<?php

include_once('core/main.php');

$page = new SimpleTemplate();

if (isset($_REQUEST['success']) || isset($_REQUEST['fail'])) { // успешное или неуспешное завершение платежа
    if (!isset($pgw)) die("No payment gateway selected.");

    if (isset($_REQUEST['success'])) {
        $pgw->paymentSucceeded();
        if ($pgw->getPaymentStatus($pgw->getPaymentID()) == 'Y') {
            $lb->generateFormData($pgw->getPaymentID(), 'Y');
        }
        elseif ($pgw->processingSupport()) {
            $lb->generateFormData($pgw->getPaymentID(), 'P');
        }
        else {
            $lb->generateFormData($pgw->getPaymentID(), 'N');
        }
    }
    else {
        $pgw->paymentFailed();
        $lb->generateFormData($pgw->getPaymentID(), 'N');
    }
    $lb->setFormURL($pgw->returnFormParameter('redirecturl'));
    $formData = $lb->getFormData();

    $fields = '';
    foreach ($formData['fields'] as $name => $value) {
        $fields .= '<input type="hidden" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" />';
    }

    $page->assign('FormURL', $formData['url']);
    $page->assign('FormMethod', $formData['method']);
    $page->assign('HiddenFields', $fields);

    $page->display('templates/finish.html');

    die;
}

if (isset($_REQUEST['result'])) { // оповещение от платежной системы

    if (!isset($pgw)) die("No payment gateway selected.");

    $pgw->paymentNotification();
    die;
}

if (!$lb->verifyChecksum()) die("Nothing here. Access allowed only from domain control panel.");

$lb->createPayment();

$page->assign('TransID', $lb->getPaymentID());
$page->assign('Description', $lb->getDescription());

foreach ($paymentGateways as $gatewayName => $gatewayClassFileName) {
    includePaymentGateway($gatewayClassFileName);

    if (isset($pgw)) {
        $pgw->setPaymentID($lb->getPaymentID());
        $pgw->setPaymentAmount($pgw->calculateAmount($lb->getAmount()));
        $pgw->setPaymentDescription($lb->getPaymentID());
        $pgw->addFormParameter('redirecturl', $lb->getRedirectUrl());

        $formData = $pgw->getFormData();

        $fields = '';
        foreach ($formData['fields'] as $name => $value) {
            $fields .= '<input type="hidden" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" />';
        }

        $s = str_replace('.class.php', '', $gatewayClassFileName);
        $page->assign($s.'.FormURL', $formData['url']);
        $page->assign($s.'.FormMethod', $formData['method']);
        $page->assign($s.'.HiddenFields', $fields);
        $page->assign($s.'.Amount', $pgw->calculateAmount($lb->getAmount()));
    }
}

$page->display('templates/index.html');
