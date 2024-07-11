<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
//init var
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id=$_GET['id'];
    $x = "SELECT
  p.id,
  p.name as partnerName,
  DATE(
    MAX(t.paid_date)
  ) AS lastTransaction,
  DATE(p.created_at) AS joinDate,
  IFNULL(
    DATEDIFF(
      DATE(
        MAX(t.paid_date)
      ),
      DATE(p.created_at)
    ),
    '-'
  ) AS duration,
  IFNULL(s.name, '-') AS salesName,
  COUNT(t.id) AS trxCount,
  e.nama AS picName,
  e.phone AS picPhone
FROM
  partner p
  LEFT OUTER JOIN transaksi t ON p.id = t.id_partner
  AND t.status IN (0, 1, 2, 5)
  LEFT JOIN sa_users s ON s.id = p.referral
  LEFT JOIN employees e ON e.id_partner = p.id
WHERE
  p.id = '$id'
";
    $sql = mysqli_query($db_conn, $x);
    $getOmzet = mysqli_query($db_conn, "SELECT
  date_format(t.jam, '%M %Y') AS month,
  CASE WHEN (
    sum(
    t.total +(t.total * t.service / 100)
  )> 30000000
  ) THEN '1' ELSE '0' END AS isShouldUpgrade
FROM
  transaksi t
WHERE
  t.id_partner = '$id'
  AND t.status IN(0, 1, 2, 5)
  AND t.deleted_at IS NULL
GROUP BY
  date_format(t.jam, '%M %Y')
ORDER BY
  `month` DESC
");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $omzet = mysqli_fetch_all($getOmzet, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "omzet"=>$omzet]);

?>
