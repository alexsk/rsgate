<?php

chdir('..');

include_once('core/main.php');

$page = new SimpleTemplate();

session_start();

if (isset($_POST['auth'])) { // авторизация
    $_SESSION['admin_login'] = $sql->unescapeString($_POST['login']);
    $_SESSION['admin_password'] = md5($sql->unescapeString($_POST['password']));
    $_SESSION['admin_session'] = md5(session_id());
}

if (isset($_SESSION['admin_session'])) { // проверка наличия админ-сессии
    if (($_SESSION['admin_login'] != $adminLogin) ||
        ($_SESSION['admin_password'] != md5($adminPassword)) ||
        ($_SESSION['admin_session'] != md5(session_id()))) {
        $page->display('templates/admin_login.html');
        die;
    }
}
else {
    $page->display('templates/admin_login.html');
    die;
}

/***********************************************************************/

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ./');
    die;
}

if (isset($_GET['search'])) {
    if (isset($_POST['searchProcess'])) {
        // создаем строку критериев поиска
        $searchText = array();
        $fields = array('id','userid','payerip','gateway','payerinfo','datetype');
        foreach ($fields as $field) {
            if ($_POST[$field] != '') $searchText[] = $field.':'.$sql->unescapeString($_POST[$field]);
        }
        if ($_POST['datetype'] == '2') {
            $fields = array('fromday','frommonth','fromyear','today','tomonth','toyear');
            foreach ($fields as $field) {
                $searchText[] = $field.':'.$sql->unescapeString($_POST[$field]);
            }
        }
        else $searchText[] = 'days:'.$sql->unescapeString($_POST['fixeddays']);

        header('Location: ./?searchText='.urlencode(implode('|',$searchText)));
    }
    else {
        $paymentGatewaysNames = array_flip($paymentGateways);
        $gatewaysList = '';
        foreach ($paymentGatewaysNames as $gatewayName) {
            $gatewaysList .= '<option value="'.htmlspecialchars($gatewayName).'">'.htmlspecialchars($gatewayName).'</option>';
        }
        $page->assign('gatewaysList', $gatewaysList);

        $yearsList = '';
        for ($year=2008; $year<=date('Y'); $year++)
            $yearsList .= '<option value="'.$year.'">'.$year.'</option>';
        $page->assign('yearsList', $yearsList);

        $page->display('templates/admin_search.html');
    }
    die;
}

if (isset($_GET['stats'])) {
    $chartData1 = 'chs=580x170&cht=p3&chco=66b500,00cacc,ffae00,315dff,db31ff,00ccff,d70000';
    $result = $sql->query('SELECT gateway, COUNT(paid) as "count" FROM '.$sql->prefix.'payments WHERE paid IN ("Y","YP") GROUP BY gateway');
    $totalPayments = 0;
    $statsData = array();
    while ($row = $sql->fetchArray($result)) {
        $statsData[$row['gateway']] = $row['count'];
        $totalPayments += $row['count'];
    }

    $chd = $chl = array();
    $paymentGatewaysNames = array_flip($paymentGateways);
    foreach ($paymentGatewaysNames as $gatewayName) {
        if (isset($statsData[$gatewayName])) {
            $percent = $statsData[$gatewayName] * 100 / $totalPayments;
            $chd[] = round($percent, 1);
            $chl[] = urlencode(_toUTF8($gatewayName).' ('.round($percent).'%)');
        }
    }
    $chartData1 .= '&chd=t:' . implode(',', $chd) . '&chl=' . implode('|', $chl);
    $page->assign('chartData1', $chartData1);

    $chartData2 = 'chs=600x170&cht=lc&chco=004ece,66b500&chls=1,3,4&chxt=x,y&chxl=0:1|3|5|7|9|11|13|15|17|19|21|23|25|27|29|31&chxp=0,1,3,5,7,9,11,13,15,17,19,21,23,25,27,29,31&chg=6.6,33.3,1,5';

    $month = date('m'); $year = date('Y'); $day = date('d'); $prev_days = date('t',time()-($day+1)*86400);
    $chartData2 .= '&chdl='.sprintf('%02d',$month>1?$month-1:12).'/'.($month>1?$year:$year-1).'|'.sprintf('%02d',$month).'/'.$year;

    $thisMonthData = $prevMonthData = array(); $maxValue = 0;

    for ($i=1; $i<=$prev_days; $i++) $prevMonthData[] = 0;
    for ($i=($prev_days+1); $i<=31; $i++) $prevMonthData[] = -1;

    for ($i=1; $i<=$day; $i++) $thisMonthData[] = 0;
    for ($i=($day+1); $i<=31; $i++) $thisMonthData[] = -1;

    $result = $sql->query('SELECT DAYOFMONTH(`date`) as "day", SUM(sellingcurrencyamount) as "sum" FROM '.$sql->prefix.'payments WHERE paid IN ("Y","YP") AND DATE_FORMAT(`date`,"%Y-%m")="'.($month>1?$year:$year-1).'-'.sprintf('%02d',$month>1?$month-1:12).'" GROUP BY `day`');
    while ($row = $sql->fetchArray($result)) {
        $prevMonthData[$row['day']-1] = $row['sum'];
        if ($row['sum'] > $maxValue) $maxValue = $row['sum'];
    }

    $result = $sql->query('SELECT DAYOFMONTH(`date`) as "day", SUM(sellingcurrencyamount) as "sum" FROM '.$sql->prefix.'payments WHERE paid IN ("Y","YP") AND DATE_FORMAT(`date`,"%Y-%m")="'.$year.'-'.sprintf('%02d',$month).'" GROUP BY `day`');
    while ($row = $sql->fetchArray($result)) {
        $thisMonthData[$row['day']-1] = $row['sum'];
        if ($row['sum'] > $maxValue) $maxValue = $row['sum'];
    }

    $maxValue = round($maxValue);
    $chartData2 .= '&chxr=0,1,31|1,0,'.$maxValue;

    for ($i=0; $i<31; $i++) {
        if ($prevMonthData[$i] > 0) $prevMonthData[$i] = round($prevMonthData[$i] * 100 / $maxValue, 1);
        if ($thisMonthData[$i] > 0) $thisMonthData[$i] = round($thisMonthData[$i] * 100 / $maxValue, 1);
    }
    $chartData2 .= '&chd=t:' . implode(',',$prevMonthData) . '|' . implode(',',$thisMonthData);
    $page->assign('chartData2', $chartData2);

    $page->display('templates/admin_stats.html');
    die;
}

function _toUTF8($string) {
    if (function_exists('iconv'))
        return iconv('windows-1251', 'UTF-8', $string);
    else {
        // конвертируем символы кириллицы вручную
        $win = $utf = array();

        $win[] = chr(208); $utf[] = chr(208).chr(160);
        $win[] = chr(209); $utf[] = chr(208).chr(161);

        $win[] = chr(168); $utf[] = chr(208).chr(129);
        $win[] = chr(184); $utf[] = chr(209).chr(145);

        for ($i=128; $i<=143; $i++) {
            $win[] = chr($i+112); $utf[] = chr(209).chr($i);
        }
        for ($i=144; $i<=191; $i++)
        if ($i!=160 && $i!=161) {
            $win[] = chr($i+48); $utf[] = chr(208).chr($i);
        }

        return str_replace($win, $utf, $string);
    }
}

/***********************************************************************/

// кол-во записей в таблице на странице
$transactionsToShow = 30;

// фильтр показа
if (isset($_POST['filter'])) {
    $filter = $_POST['filter'];
    setcookie('adminFilter', implode('|', $filter), time()+60*60*24*30);
}
elseif (isset($_COOKIE['adminFilter'])) {
    $filter = explode('|', $_COOKIE['adminFilter']);
}
else {
    $filter = array('paid');
}

$sqlFilter = '';
if (in_array('paid', $filter)) { $sqlFilter .= ' OR paid="Y"'; $page->assign('paidChecked', 'checked="checked"'); }
if (in_array('unpaid', $filter)) { $sqlFilter .= ' OR (paid="N" AND gateway!="")'; $page->assign('unpaidChecked', 'checked="checked"'); }
if (in_array('unfinished', $filter)) { $sqlFilter .= ' OR (paid="N" AND gateway="")'; $page->assign('unfinishedChecked', 'checked="checked"'); }
if (in_array('processing', $filter)) { $sqlFilter .= ' OR paid="P" OR paid="YP"'; $page->assign('processingChecked', 'checked="checked"'); }
$sqlFilter = '(' . substr($sqlFilter,4) . ')';

// обработка параметров поиска
$sqlSearch = '';
if (isset($_GET['searchText'])) {
    $searchText = explode('|', $sql->unescapeString($_GET['searchText']));

    $ar = array();
    foreach ($searchText as $value) {
        $v = explode(':', $value);
        $ar[$v[0]] = $v[1];
    }
    $searchText = $ar;

    if (isset($searchText['id'])) {
        $sqlSearch .= ' AND id="'.$sql->escapeString($searchText['id']).'"';
    }

    if (isset($searchText['userid'])) {
        $ids = explode(',',$searchText['userid']);
        foreach ($ids as $i => $value) {
            $ids[$i] = intval($value);
        }
        $sqlSearch .= ' AND userid IN ('.implode(',',$ids).')';
    }

    if (isset($searchText['payerip'])) {
        $sqlSearch .= ' AND payerip LIKE "%'.$sql->escapeString($searchText['payerip']).'%"';
    }

    if (isset($searchText['gateway'])) {
        $sqlSearch .= ' AND gateway="'.$sql->escapeString($searchText['gateway']).'"';
    }

    if (isset($searchText['payerinfo'])) {
        $sqlSearch .= ' AND payerinfo LIKE "%'.$sql->escapeString($searchText['payerinfo']).'%"';
    }

    if ($searchText['datetype'] == '1') {
        $days = array(7, 30, 91, 182, 365);
        if (in_array(intval($searchText['fixeddays']), $days)) {
            $d = intval($searchText['fixeddays']) - 1;
            $sqlSearch .= ' AND (`date` BETWEEN "'.date('Y-m-d',time()-$d*86400).' 00:00:00" AND "'.date('Y-m-d').' 23:59:59")';
        }
    }
    elseif ($searchText['datetype'] == '2') {
        $sqlSearch .= ' AND (`date` BETWEEN "'.intval($searchText['fromyear']).'-'.intval($searchText['frommonth']).'-'.intval($searchText['fromday']).' 00:00:00" AND "'.intval($searchText['toyear']).'-'.intval($searchText['tomonth']).'-'.intval($searchText['today']).' 23:59:59")';
    }

    $page->assign('dropSearch', '<font color="White">(<a href="./">x</a>)</font>');
}

// разбивка на страницы
list($count) = $sql->fetchRow($sql->query('SELECT COUNT(id) FROM '.$sql->prefix.'payments WHERE '.$sqlFilter.$sqlSearch));
$pages = ceil($count / $transactionsToShow);

if (isset($_POST['page'])) $p = intval($_POST['page']); else $p = 1;
if (($p < 1) || ($p > $pages)) $p = 1; 

if ($p > 1) $page->assign('prevPageNumber', $p-1);
else $page->assign('prevButtonDisabled', 'disabled="disabled"');

if ($p < $pages) $page->assign('nextPageNumber', $p+1);
else $page->assign('nextButtonDisabled', 'disabled="disabled"');

$page->assign('currentPageNumber', $p);
$page->assign('totalPages', $pages);
$page->assign('totalPagesLength', strlen($pages));

// получаем и выводим нужные транзакции
$result = $sql->query('SELECT id,userid,`name`,payerip,sellingcurrencyamount,gateway,gatewayip,receivedamount,paid,payerinfo,DATE_FORMAT(`date`,"%d.%m.%Y %T") as "pdate" FROM '.$sql->prefix.'payments WHERE '.$sqlFilter.$sqlSearch.' ORDER BY `date` DESC LIMIT '.(($p-1)*$transactionsToShow).','.$transactionsToShow);
$data = '';
while ($row = $sql->fetchArray($result)) {
    $data .= '<tr>';
    $data .= '<td nowrap="nowrap"><a href="'.$resellerPanelURL.'servlet/ViewTransactionServlet?transid='.$row['id'].'" target="_blank">'.$row['id'].'</a></td>';
    $transidParts = explode('-', $row['id']);
    if ($transidParts[1] == 'C')
        $data .= '<td><a href="'.$resellerPanelURL.'servlet/ViewCustomerServlet?customerid='.$row['userid'].'" target="_blank">'.htmlspecialchars($row['name']).'</a></td>';
    else
        $data .= '<td><a href="'.$resellerPanelURL.'servlet/ViewResellerServlet?resellerid='.$row['userid'].'" target="_blank">'.htmlspecialchars($row['name']).'</a></td>';
    $data .= '<td nowrap="nowrap"><a href="http://ripe.net/fcgi-bin/whois?form_type=simple&searchtext='.urlencode($row['payerip']).'" target="_blank">'.htmlspecialchars($row['payerip']).'</a></td>';
    $data .= '<td>'.sprintf('%0.2f',floatval($row['sellingcurrencyamount'])).'</td>';

    if ($row['gateway'] != '') {
        $data .= '<td nowrap="nowrap">'.htmlspecialchars($row['gateway']).'</td>';
        if ($row['paid'] != 'N') {
            $data .= '<td nowrap="nowrap"><a href="http://ripe.net/fcgi-bin/whois?form_type=simple&searchtext='.urlencode($row['gatewayip']).'" target="_blank">'.htmlspecialchars($row['gatewayip']).'</a></td>';
            $data .= '<td>'.sprintf('%0.2f',floatval($row['receivedamount'])).'</td>';
            $data .= '<td width="25%">'.nl2br(preg_replace('/^WMID: (\d{12})/i','WMID: <a href="http://passport.webmoney.ru/asp/CertView.asp?wmid=$1" target="_blank">$1</a>',htmlspecialchars($row['payerinfo']))).'&nbsp;</td>';
        }
        else $data .= '<td colspan="3" align="center" nowrap="nowrap">Пользователь отказался от оплаты</td>';
    }
    else $data .= '<td colspan="4" align="center" nowrap="nowrap">Платеж не был завершен</td>';
    $data .= '<td nowrap="nowrap">'.$row['pdate'].'</td>';
    $data .= '</tr>';
}

$page->assign('tableData', $data); 

$page->display('templates/admin_index.html');
