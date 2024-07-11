<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$all_users = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $master_id = $_GET['master_id'];
    $query = "SELECT t.phone, users.name, t.trx_count AS count, t.sum, fc.menu_name AS favorite, fc.favorite_count, t.jam
            FROM (
                SELECT t.phone, COUNT(t.id) as trx_count, SUM(t.total) AS sum, MAX(t.jam) AS jam
             	FROM transaksi AS t
                LEFT JOIN partner AS p ON p.id = t.id_partner
                LEFT JOIN master AS ms ON ms.id = p.id_master
                WHERE ms.id = '$master_id'
                GROUP BY t.phone
            ) AS t
            RIGHT JOIN (
            	SELECT phone, name, menu_name, MAX(uh.count) AS favorite_count
                FROM (
                    SELECT t.phone AS phone, t.customer_name AS name, SUM(dt.qty) as count, dt.id_menu as id_menu, m.nama as menu_name
                        FROM transaksi AS t
                        RIGHT JOIN detail_transaksi AS dt ON t.id = dt.id_transaksi
                        LEFT JOIN menu AS m ON m.id = dt.id_menu
                    	LEFT JOIN partner AS p ON p.id = t.id_partner
                    	LEFT JOIN master AS ms ON ms.id = p.id_master
                    WHERE ms.id = '$master_id'
                    GROUP BY t.phone, dt.id_menu
                    ORDER BY SUM(dt.qty) DESC
                ) AS uh
                GROUP BY phone
            ) AS fc ON fc.phone = t.phone
            LEFT JOIN users ON users.phone = t.phone
            WHERE fc.phone != 'POS/PARTNER'
            ORDER BY t.sum DESC";
    $users = mysqli_query($db_conn, $query);
    if(mysqli_num_rows($users) > 0) {
        $all_users1 = mysqli_fetch_all($users, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        // $status = 204;
        $msg = "Data Not Found";
    }
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "customers"=>$all_users1]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>