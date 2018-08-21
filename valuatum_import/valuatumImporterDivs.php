<?php

error_reporting(E_ALL);

define('DB_HOST', "127.0.0.1");
define('DB_USER', '');
define('DB_PASS', '');
define('DB1_NAME', 'valuatum');
define('DB2_NAME', 'drupal');

$db1 = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB1_NAME . ';', DB_USER, DB_PASS);
$db2 = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB2_NAME . ';', DB_USER, DB_PASS);

$q = $db2->prepare("SHOW TABLES WHERE Tables_in_drupal27 REGEXP '^field_data_div[0-9]'");
$q->execute();

$result = $q->fetchAll();

$tables = array();

$cnt = 1;
foreach ($result as $key => $value) {
  $q = $db2->prepare("SELECT * FROM " . $value[0] . " as d JOIN eck_inderes_numbers ein on ein.id=d.entity_id WHERE ein.type=:type");
  $q->bindValue(':type', 'basic', PDO::PARAM_STR);
  $q->execute();

  $result = $q->fetchAll(PDO::FETCH_ASSOC);

  $value_field_name = str_replace('field_data_', '', $value[0]) . '_value';

  $q2 = $db1->prepare("INSERT INTO divisions(`isin`, `div`, `name`) VALUES(:isin, :div, :name)");
  foreach ($result as $row) {
    try {
      $q2->execute(array(
        ':isin' => $row['isin'],
        ':div' => $cnt,
        ':name' => $row[$value_field_name],
      ));
      $affected_rows = $q2->rowCount();
    }
    catch (Exception $e) {
      print_r($e);
    }
  }

  $cnt++;
}
