<?php

error_reporting(E_ALL);

$host = "localhost:32783";
$username = "root";
$password = "";
$db_name = "valuatum_data";
$con = mysqli_connect("$host", "$username", "$password") or die("cannot connect");

mysqli_select_db($con, $db_name) or die("cannot select DB");

$tbl_name = "varnames";
$file = getcwd() . '/varnames.csv';

mysqli_query($con, "CREATE TABLE IF NOT EXISTS $tbl_name (
  `id` int(11) NOT NULL, 
  `name` varchar(255) NOT NULL default '', 
  `description` varchar(255) NOT NULL default ''
  )"
) or die(mysqli_error($con));
mysqli_query($con, "TRUNCATE TABLE $tbl_name");
mysqli_query($con, "LOAD DATA LOCAL INFILE '$file' INTO TABLE $tbl_name IGNORE 1 LINES") or die(mysqli_error($con));

$tbl_name = "rec_history";
$file = getcwd() . '/recommendation_history.csv';
mysqli_query($con, "CREATE TABLE IF NOT EXISTS $tbl_name (
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
mysqli_query($con, "TRUNCATE TABLE $tbl_name");
mysqli_query($con, "LOAD DATA LOCAL INFILE '$file' INTO TABLE $tbl_name IGNORE 1 LINES") or die(mysqli_error($con));
print 'daaa';
$tbl_name = "data_values";
mysqli_query($con, "CREATE TABLE IF NOT EXISTS $tbl_name (
  `mvid` mediumint(11) NOT NULL, 
  `varid` smallint(11) NOT NULL, 
  `pos` smallint(11) NOT NULL, 
  `value` float,
  INDEX(`mvid`, `varid`)
  )"
) or die(mysqli_error($con));
mysqli_query($con, "TRUNCATE TABLE $tbl_name");
$dir = new DirectoryIterator(getcwd() . "/modeldata");
foreach ($dir as $fileinfo) {
  if (!$fileinfo->isDot()) {
    $file = $fileinfo->getPathname();
    print 'Importing: ' . $fileinfo->getFilename() . "\n";
    mysqli_query($con, "LOAD DATA LOCAL INFILE '$file' INTO TABLE $tbl_name IGNORE 1 LINES") or die(mysqli_error($con));
  }
}

mysqli_close($con);

?>