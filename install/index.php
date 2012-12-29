<?php

chdir('..');

include_once('core/main.php');

$queries = array();
$queries[] = "DROP TABLE IF EXISTS `".$sql->prefix."payments`";
$queries[] = "CREATE TABLE `".$sql->prefix."payments` (
  `id` varchar(255) NOT NULL default '',
  `userid` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `payerip` varchar(32) NOT NULL default '',
  `sellingcurrencyamount` decimal(8,3) unsigned NOT NULL default '0.000',
  `accountingcurrencyamount` decimal(8,3) unsigned NOT NULL default '0.000',
  `gateway` varchar(255) NOT NULL default '',
  `gatewayip` varchar(16) NOT NULL default '',
  `receivedamount` decimal(8,2) unsigned NOT NULL default '0.00',
  `paid` enum('N','Y','P','YP') NOT NULL default 'N',
  `payerinfo` text NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `paid` (`paid`)
) ENGINE=MyISAM;";

foreach ($queries as $query) {
    $sql->query($query) or die('Возникла ошибка в процессе создания базы данных. Подробную информацию смотрите в логах.');
}

print 'База данных успешно создана.<br/>
<font color="Red"><b>Не забудьте удалить папку install !</b></font><br/>
Успешной работы!';
