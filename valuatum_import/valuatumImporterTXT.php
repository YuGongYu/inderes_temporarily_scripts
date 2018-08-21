<?php

error_reporting(E_ALL);

$host = "127.0.0.1";
$username = "";
$password = "";
$db_name = "valuatum";

$con = mysql_connect("$host", "$username", "$password")or die("cannot connect");
//$con2 = mysql_connect("$host", "$username", "$password", TRUE)or die("cannot connect");
mysql_select_db($db_name, $con) or die("cannot select DB");
//mysql_select_db('drupal', $con2) or die("cannot select DB");

/*$q = mysql_query('select n.nid, n.title, i.field_isin_value from node n join field_data_field_isin i on i.entity_id=n.nid where n.type="company_page"', $con2);

while ($res = mysql_fetch_assoc($q)) {
  print_r($res);
  mysql_query('UPDATE companies set isin="' . $res['field_isin_value'] . '" WHERE name="' . $res['title'] . '"', $con);
}*/


/*$tbl_name = "varnames";
$file = '/valuatum/varnames.csv';

mysql_query("CREATE TABLE IF NOT EXISTS $tbl_name (
  `id` int(11) NOT NULL, 
  `name` varchar(255) NOT NULL default '', 
  `description` varchar(255) NOT NULL default ''
  )"
) or die(mysql_error());

mysql_query("TRUNCATE TABLE $tbl_name");
mysql_query("LOAD DATA LOCAL INFILE '$file' INTO TABLE $tbl_name IGNORE 1 LINES") or die(mysql_error());*/

/*$tbl_name = "rec_history";
$file = '/valuatum/recommendation_history.csv';

mysql_query("CREATE TABLE IF NOT EXISTS $tbl_name (
  `mvid` int(11) NOT NULL, 
  `username` varchar(255) NOT NULL default '', 
  `firstname` varchar(255) NOT NULL default '',
  `lastname` varchar(255) NOT NULL default '',
  `uaid` int(11) NOT NULL, 
  `company_name` varchar(255) NOT NULL default '',
  `company_id` int(11) NOT NULL, 
  `fmodel_id` int(11) NOT NULL, 
  `uaid2` int(11) NOT NULL, 
  `orderno` int(11) NOT NULL, 
  `saved` DATETIME NOT NULL, 
  `price_updated` DATETIME NOT NULL, 
  `reclevel` smallint(11) NOT NULL, 
  `fairvalue` float NOT NULL, 
  `target_price` float NOT NULL
  )"
) or die(mysql_error());
mysql_query("TRUNCATE TABLE $tbl_name");
mysql_query("LOAD DATA LOCAL INFILE '$file' INTO TABLE $tbl_name IGNORE 1 LINES") or die(mysql_error());*/

/*$tbl_name = "data_values";

mysql_query("CREATE TABLE IF NOT EXISTS $tbl_name (
  `mvid` mediumint(11) NOT NULL, 
  `varid` smallint(11) NOT NULL, 
  `pos` smallint(11) NOT NULL, 
  `value` float,
  INDEX(`mvid`)
  )"
) or die(mysql_error());
mysql_query("TRUNCATE TABLE $tbl_name");

$dir = new DirectoryIterator("/valuatum/modeldata");
foreach ($dir as $fileinfo) {
  if (!$fileinfo->isDot()) {
    $file = $fileinfo->getPathname();
    print 'Importing: ' . $fileinfo->getFilename() . "\n";
    mysql_query("LOAD DATA LOCAL INFILE '$file' INTO TABLE $tbl_name IGNORE 1 LINES") or die(mysql_error());
  }
}*/
