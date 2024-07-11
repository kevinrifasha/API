<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
//init var
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
  if (preg_match($rx_http, $key)) {
    $arh_key = preg_replace($rx_http, '', $key);
    $rx_matches = array();
    // do some nasty string manipulations to restore the original letter case
    // this should work in most cases
    $rx_matches = explode('_', $arh_key);
    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
      foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
      $arh_key = implode('-', $rx_matches);
    }
    $headers[$arh_key] = $val;
  }
}
$tokenizer = new Token();
$token = '';
$res = array();
$test = array();

//get token
foreach ($headers as $header => $value) {
  if ($header == "Authorization" || $header == "AUTHORIZATION") {
    $token = substr($value, 7);
  }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
  $status = $tokenValidate['status'];
  $msg = $tokenValidate['msg'];
  $success = 0;
} else {
  $id = $token->id;
  $salesID = $_GET['id'];
  $from = $_GET['from'];
  $to = $_GET['to'];
  $type = $_GET['type'];
  if (isset($salesID) && !empty($salesID) && $salesID != "null") {
    if(!$type){
        $query = "SELECT p.id, p.name, p.address, DATE(p.created_at) AS joinDate, IFNULL(s.name,'-') AS salesName,COUNT(t.id) AS trxCount, e.nama AS picName, e.phone AS picPhone FROM partner p LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5) LEFT JOIN sa_users s ON p.referral=s.id LEFT JOIN employees e ON e.id_partner = p.id WHERE p.referral='$salesID' AND p.deleted_at IS NULL AND p.status=1 AND DATE(p.created_at) BETWEEN '$from' AND '$to' GROUP BY p.id ORDER BY joinDate DESC";
    } else if ($type == "visitation") {
            $query = "SELECT
                count(v.id) as visitCount
              FROM
                sa_visitations v
              WHERE
                DATE(v.created_at) BETWEEN '$from' AND '$to'
              ";
    }
  } else {
    if (!empty($type)) {
      // pecah per type
      if ($type == "active") {
        $query = "SELECT
                p.id,
                p.name,
                p.address,
                DATE(p.created_at) AS joinDate,
                IFNULL(s.name, '-') AS salesName,COUNT(t.id) AS trxCount, 
                (SELECT t.paid_date FROM transaksi t WHERE t.id_partner = p.id ORDER BY t.paid_date DESC LIMIT 1 ) as lastTrx ,e.nama AS picName, e.phone AS picPhone
              FROM
                partner p
                JOIN sa_users s ON p.referral = s.id
                LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id
              WHERE
                p.deleted_at IS NULL
                AND DATE(p.created_at) BETWEEN '$from'
                AND '$to'
                AND DATEDIFF(
                  DATE(
                    t.paid_date
                  ),
                  DATE(p.created_at)
                ) > 3
              GROUP BY
                p.id
              ORDER BY
                t.id DESC
              ";
      } else if ($type == "paid") {
        $query = "SELECT p.id, p.name, p.address, DATE(p.created_at) AS joinDate, '-' AS salesName,COUNT(t.id) AS trxCount, e.nama AS picName, e.phone AS picPhone FROM partner p LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id WHERE p.referral='$id' AND p.deleted_at IS NULL AND p.status=1 AND DATE(p.subscription_paid_date) BETWEEN '$from' AND '$to' GROUP BY p.id ORDER BY joinDate DESC";
      } else if ($type == "new") {
        $query = "SELECT p.id, p.name, p.address, DATE(p.created_at) AS joinDate, '-' AS salesName,COUNT(t.id) AS trxCount, e.nama AS picName, e.phone AS picPhone FROM partner p LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id WHERE p.deleted_at IS NULL AND p.status=1 AND DATE(p.created_at) BETWEEN '$from' AND '$to' AND p.referral REGEXP '^[0-9]+$' GROUP BY p.id ORDER BY joinDate DESC";
      } else if ($type == "activeAll"){
          $query = "
          SELECT
                p.id,
                p.name,
                p.address,
                DATE(p.created_at) AS joinDate,
                COUNT(t.id) AS trxCount, 
                (SELECT t.paid_date
               	FROM transaksi t
                WHERE t.id_partner = p.id
                ORDER BY t.paid_date
                DESC
                LIMIT 1
                ) as lastTrx ,
                 e.nama AS picName, e.phone AS picPhone
              FROM
                partner p
                LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id
              WHERE
                p.deleted_at IS NULL
                AND DATE(p.created_at) BETWEEN '$from'
                AND '$to'
                AND (SELECT t.paid_date
               	FROM transaksi t
                WHERE t.id_partner = p.id
                ORDER BY t.paid_date
                DESC
                LIMIT 1
                ) IS NOT NULL
                AND DATEDIFF(
                  DATE(
                    t.paid_date
                  ),
                  DATE(p.created_at)
                ) > 3
              GROUP BY
                p.id  
			ORDER BY `lastTrx` DESC";
      } else if($type == "all"){
            $query = "
            SELECT
                p.id,
                p.name,
                p.address,
                DATE(p.created_at) AS joinDate,
                COUNT(t.id) AS trxCount, 
                (SELECT t.paid_date
               	FROM transaksi t
                WHERE t.id_partner = p.id
                ORDER BY t.paid_date
                DESC
                LIMIT 1
                ) as lastTrx ,e.nama AS picName, e.phone AS picPhone
              FROM
                partner p
                LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id
              WHERE
                p.deleted_at IS NULL
                AND DATE(p.created_at) BETWEEN '$from'
                AND '$to'
              GROUP BY
                p.id  
			ORDER BY `lastTrx` DESC";
      } else if ($type == "countAll"){
            $query = "
            SELECT
                COUNT(p.id) as countAll
              FROM
                partner p
              WHERE
                p.deleted_at IS NULL
                AND DATE(p.created_at) BETWEEN '$from'
                AND '$to'";
      } else if ($type == "allPerSales"){
          $query = "SELECT
                p.id,
                p.name,
                p.address,
                DATE(p.created_at) AS joinDate,
                IFNULL(s.name, '-') AS salesName,COUNT(t.id) AS trxCount, 
                (SELECT t.paid_date FROM transaksi t WHERE t.id_partner = p.id ORDER BY t.paid_date DESC LIMIT 1 ) as lastTrx ,e.nama AS picName, e.phone AS picPhone
              FROM
                partner p
                JOIN sa_users s ON p.referral = s.id
                LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id
              WHERE
                p.deleted_at IS NULL
                AND DATE(p.created_at) BETWEEN '$from'
                AND '$to'
              GROUP BY
                p.id
              ORDER BY
                t.id DESC
              ";
      } else if ($type == "allTrxWithReferral"){
            $query = "
               SELECT
                COUNT(t.id) AS trxCount
              FROM
                transaksi t
                LEFT JOIN partner p ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
              WHERE
                p.deleted_at IS NULL
                AND p.referral IS NOT NULL
                AND DATE(p.created_at) BETWEEN '$from'
                AND '$to'";
      }
    } else {
      if ($token->roleID == "5") {
        //buat sales
        $query = "SELECT p.id, p.name, p.address, DATE(p.created_at) AS joinDate, '-' AS salesName,COUNT(t.id) AS trxCount, e.nama AS picName, e.phone AS picPhone FROM partner p LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id WHERE p.referral='$id' AND p.deleted_at IS NULL AND p.status=1 GROUP BY p.id ORDER BY joinDate DESC";
      } else {
        //buat arif
        $query = "SELECT p.id, p.name, p.address, IFNULL(s.name,'-') AS salesName, DATE(p.created_at) AS joinDate,COUNT(t.id) AS trxCount, e.nama AS picName, e.phone AS picPhone FROM partner p LEFT JOIN sa_users s ON p.referral=s.id LEFT OUTER JOIN transaksi t ON p.id = t.id_partner AND t.status IN (0, 1, 2, 5)
                LEFT JOIN employees e ON e.id_partner = p.id WHERE p.deleted_at IS NULL AND DATE(p.created_at)>'2022-07-01' AND p.id NOT IN ('000310','000276','000262','000343','000313', '000309', '000343', '000314', '000295', '000266','000241','000444','000443','000171','000162','000336','000231','000202','000401','000701') GROUP BY p.id
                ORDER BY joinDate DESC";
      }
    }
  }

  $sql = mysqli_query($db_conn, $query);

  if (mysqli_num_rows($sql) > 0) {
    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
    $success = 1;
    $status = 200;
    $msg = "Success";
  } else {
    $success = 0;
    $status = 204;
    $msg = "Data Not Found";
  }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $data, "test"=>$query]);
