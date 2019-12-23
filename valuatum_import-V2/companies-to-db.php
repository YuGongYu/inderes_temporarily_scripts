<?php

error_reporting(E_ALL);

$host = "localhost:32782";
$username = "root";
$password = "";
$db_name = "valuatum_data";
$con = mysqli_connect("$host", "$username", "$password") or die("cannot connect");

mysqli_select_db($con, $db_name) or die("cannot select DB");

// Create table (name, isin, ticker, valuatum id).
$tbl_name = "companies";
mysqli_query($con, "CREATE TABLE IF NOT EXISTS $tbl_name (
  `v_id` int(11), 
  `ticker` varchar(11),
  `isin` varchar(11) NOT NULL,
  `name` varchar(40) NOT NULL,
  INDEX(`ticker`)
  )"
) or die(mysqli_error($con));
mysqli_query($con, "TRUNCATE TABLE $tbl_name");

mysqli_close($con);

// Fetch companies from inderes.fi.
$companies = json_decode(file_get_contents('https://www.inderes.fi/fi/rest/inderes_numbers_recommendations.json'));

// Try to fetch valuatum id by company name.
$db = new PDO('mysql:host=' . $host . ';dbname=' . $db_name . ';', $username, $password);
  
foreach ($companies as &$company) {
  $q = $db->prepare("SELECT DISTINCT r.company_id as company FROM rec_history r WHERE r.company_name=:company_name");
  $q->bindValue(':company_name', $company->name, PDO::PARAM_STR);
  $q->execute();
  $result = $q->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($result)) {
    $company->v_id = $result[0]['company'];
  }

  // Try to fetch ticker from eod (best guess). Diddi Dadda.
  $eod_res = json_decode(file_get_contents('https://eodhistoricaldata.com/api/querysearch/?q=' . urlencode($company->name)));
  foreach ($eod_res as $item) {
    $splitted_ticker = explode('.', explode(' - ', $item)[0]);
    if ($splitted_ticker[1] === 'HE') {
      $company->ticker = $splitted_ticker[0] . '.' . $splitted_ticker[1];
    }
  }

  // Save to company table.
  $q = $db->prepare("INSERT INTO $tbl_name VALUES(:v_id, :ticker, :isin, :name)");
  $q->bindValue(':v_id', isset($company->v_id) ? $company->v_id : 0, PDO::PARAM_INT);
  $q->bindValue(':ticker', isset($company->ticker) ? $company->ticker: '', PDO::PARAM_STR);
  $q->bindValue(':isin', $company->isin, PDO::PARAM_STR);
  $q->bindValue(':name', $company->name, PDO::PARAM_STR);
  $q->execute();
}

?>