<?php

error_reporting(E_ALL);

define('DB_HOST', "127.0.0.1");
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
define('BASE_URL', 'https://local.inderes.fi');
define('REST_ENDPOINT', 'https://local.inderes.fi/ic');
define('REST_USER', '');
define('REST_PASSWD', '');

migrate();

/**
 * Get transactions for company.
 */
function get_transactions($company_id, $breaking_date) {
  $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';', DB_USER, DB_PASS);

  $q = $db->prepare("SELECT r.mvid, r.uaid, r.saved, r.reclevel, r.target_price, c.name as company, c.isin as isin FROM rec_history r JOIN companies c ON c.id = r.company_id WHERE company_id=:company_id AND saved >= :breaking_date GROUP BY saved, company_id ORDER BY saved");
  $q->bindValue(':company_id', $company_id, PDO::PARAM_INT);
  $q->bindValue(':breaking_date', $breaking_date, PDO::PARAM_STR);
  $q->execute();

  $result = $q->fetchAll(PDO::FETCH_ASSOC);

  $result_processed = array();

  foreach ($result as $value) {
    $result_processed[date('Y-m-d', strtotime($value['saved']))] = $value;
  }

  return $result_processed;
}

/**
 * Get numbers for transaction.
 */
function get_transaction_numbers($tid) {
  $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';', DB_USER, DB_PASS);

  $q = $db->prepare("SELECT d.pos, d.value, v.name FROM data_values d JOIN varnames v ON v.id=d.varid WHERE d.mvid=:transaction_id AND d.pos != 0");
  $q->bindValue(':transaction_id', $tid, PDO::PARAM_INT);
  $q->execute();

  $result = $q->fetchAll(PDO::FETCH_ASSOC);

  return $result;
}

/**
 * Get divisions.
 */
function get_divisions($isin) {
  $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';', DB_USER, DB_PASS);

  $q = $db->prepare("SELECT `div`, `name` FROM divisions WHERE isin=:isin");
  $q->bindValue(':isin', $isin, PDO::PARAM_STR);
  $q->execute();

  $result = $q->fetchAll(PDO::FETCH_ASSOC);

  return $result;
}

/**
 * Migrate values.
 */
function migrate() {
  $data = array(
    'username' => REST_USER,
    'password' => REST_PASSWD,
  );

  $headers = array(
    'Content-Type: application/json;',
    'Accept: application/json;',
  );

  $response = http_call(REST_ENDPOINT . '/user/login', $data, $headers);
  $d = json_decode($response, TRUE);

  // Check if login was successful.
  if (empty($d['user'])) {
    print_r($d);
    die('Auth Fail');
  }

  // Make authenticated calls.
  $headers[] = 'Cookie: ' . $d['session_name'] . '=' . $d['sessid'];

  // Get and add x-csrf header.
  $xcsrf = http_call(BASE_URL . '/services/session/token', array(), $headers, 'GET');
  $headers[] = 'X-CSRF-Token: ' . $xcsrf;

  // Neste.
  $company_id = 351;
  $breaking_date = '2013-09-11 00:00:00';

  /*
  // Innofactor.
  $company_id = 115;
  $breaking_date = '2013-02-26 00:00:00';*/

  // Aspo.
  /*$company_id = 107;
  $breaking_date = '2013-05-22 09:05:00';*/

  print 'Migrating company: ' . $company_id . "\n";

  $transactions = get_transactions($company_id, $breaking_date);

  $failed_transactions = array();

  foreach ($transactions as $transaction) {
    print 'Saving mvid: ' . $transaction['mvid'] . "\n";
    // Migrate transaction.
    $divs = get_divisions($transaction['isin']);

    foreach ($divs as $div) {
      $div_key = 'div' . str_pad($div['div'], 2, '0', STR_PAD_LEFT);
      $transaction[$div_key] = $div['name'];
    }

    // Create transaction and check response status.
    if (!$t_response = http_call(REST_ENDPOINT . '/inderes-core-transaction/' . $transaction['isin'] . '/' . $transaction['saved'], $transaction, $headers)) {
      $failed_transactions[] = $transaction['mvid'];
      continue;
    }

    $transaction_id = json_decode($t_response)->transaction_id;

    print 'Numbers for tid: ' . $transaction_id . "\n";

    // Get numbers for transaction.
    $numbers = get_transaction_numbers($transaction['mvid']);

    $ts = time();
    // Split numbers into quarters/years.
    $numbers_by_quarter = array();
    foreach ($numbers as $number) {
      // Check pos.
      if (!$match = preg_match('/(\d{4})(.*)/', $number['pos'], $matches)) {
        continue;
      }

      $numbers_by_quarter[$matches[1]][!empty($matches[2]) ? $matches[2] : 0][$number['name']] = $number['value'];
    }
    // Migrate numbers for transaction.
    foreach ($numbers_by_quarter as $year => $year_values) {
      foreach ($year_values as $quarter => $quarter_values) {
        $quarter_values['year'] = $year;
        $quarter_values['quarter'] = $quarter;
        if (!$response = http_call(REST_ENDPOINT . '/inderes-core-number/' . $transaction_id, $quarter_values, $headers)) {
          $failed_transactions[] = $transaction['mvid'];
          continue 3;
        }
      }
    }

    // Finalize transaction.
    print 'Finalizing transaction: ' . $transaction_id . "\n";
    $t_response = http_call(REST_ENDPOINT . '/inderes-core-transaction/' . $transaction_id . '/' . 'finalize', $transaction, $headers, 'PUT');
    print 'Transaction completed: ' . $transaction_id . " (" . ((int) time() - (int) $ts) . "s) \n";
  }

  if (!empty($failed_transactions)) {
    print 'Failed(uncompleted) transactions: ' . count($failed_transactions) . "\n";
    print_r($failed_transactions);
  }

}

/**
 * Http call.
 */
function http_call($url, $data, $headers = array(), $method = 'POST') {
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

  if (!empty($headers)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  }

  switch ($method) {
    case 'POST':
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      break;

    case 'PUT':
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      break;

    default:
  }

  $result = curl_exec($ch);

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ((int) $httpcode !== 200) {
    return FALSE;
  }

  return $result;
}
