<?php

error_reporting(E_ALL);

define('DB_HOST', "127.0.0.1");
define('DB_USER', '');
define('DB_PASS', '');
define('DB1_NAME', '');
define('DB2_NAME', '');

$db1 = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB1_NAME . ';', DB_USER, DB_PASS);
$db2 = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB2_NAME . ';', DB_USER, DB_PASS);

$q = $db2->prepare('SELECT fi.field_isin_value as isin, f.field_tags_reference_tid, MIN(n.created) as created FROM node n 
JOIN field_data_field_tags_reference f ON n.nid=f.entity_id
JOIN field_data_field_tags_reference f2 ON f2.field_tags_reference_tid=f.field_tags_reference_tid
JOIN node n2 ON n2.nid=f2.entity_id 
JOIN field_data_field_isin fi ON fi.entity_id=n2.nid 
AND n.type=:report
GROUP BY f.field_tags_reference_tid
');
$q->bindValue(':report', 'company_report', PDO::PARAM_STR);
$q->execute();

$results = $q->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $result) {
  $q = $db1->prepare('UPDATE companies set breaking_date="' . date_format(date_create('@' . $result['created']), 'Y-m-d h:m:s') . '" WHERE isin="' . $result['isin'] . '"');
  $q->execute();
}
