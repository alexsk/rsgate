<?php

$_REQUEST['pg'] = 'wire';

include_once('core/main.php');

$page = new SimpleTemplate();

if (isset($_GET['cp'])) { // верхний фрейм - кнопки управления
    $page->assign('paymentID', htmlspecialchars(DatabaseConnection::unescapeString($_GET['payment_id'])));
    $page->assign('redirectURL', htmlspecialchars(DatabaseConnection::unescapeString($_GET['redirecturl'])));

    $page->display('templates/wire_cp.html');
}
elseif (isset($_GET['invoice'])) { // нижний фрейм - счет
    $payment_id = isset($_GET['payment_id']) ? DatabaseConnection::unescapeString($_GET['payment_id']) : '';
    $payment_information = explode('-', trim($payment_id));

    $page->assign('payerName', htmlspecialchars(DatabaseConnection::unescapeString($_GET['payer_name'])));
    $page->assign('invoiceNumber', $payment_information[3]);
    $page->assign('invoiceDate', date('d.m.Y'));

    $payment_amount = isset($_GET['payment_amount']) ? DatabaseConnection::unescapeString($_GET['payment_amount']) : 0;
    $page->assign('paymentAmount', sprintf('%0.2f',$payment_amount));

    if ($payment_information[0] == 'AddFund')
        $payment_description = 'Добавление средств на счет ' . $payment_information[2];
    else
        $payment_description = 'Информационные услуги по счету ' . $payment_information[3];
    $page->assign('paymentDescription', $payment_description);

    $page->display('templates/wire_invoice.html');
}
else {

    $payment_id = isset($_POST['payment_id']) ? DatabaseConnection::unescapeString($_POST['payment_id']) : '';
    $payment_amount = isset($_POST['payment_amount']) ? DatabaseConnection::unescapeString($_POST['payment_amount']) : 0;
    $payment_description = isset($_POST['payment_description']) ? DatabaseConnection::unescapeString($_POST['payment_description']) : '';
    $payer_name = isset($_POST['payerName']) ? urlencode(DatabaseConnection::unescapeString($_POST['payerName'])) : '';

    $sql->query('UPDATE '.$sql->prefix.'payments
                SET gateway="'.$sql->escapeString($pgw->getName()).'",
                    receivedamount='.floatval($payment_amount).',
                    `date`=NOW()
                WHERE id="'.$sql->escapeString($payment_id).'"');

    if (mysql_affected_rows($sql->cl) != 1) die;

    $page->assign('paymentID', urlencode($payment_id));
    $page->assign('redirectURL', urlencode(DatabaseConnection::unescapeString(@$_POST['redirecturl'])));

    $page->assign('payerName', $payer_name);
    $page->assign('paymentAmount', urlencode($payment_amount));

    $page->display('templates/wire_index.html');
}
