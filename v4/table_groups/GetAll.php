<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
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
    $partner_id = $_GET['partner_id'];
    $q = mysqli_query($db_conn, "SELECT `table_groups`.`id`, `table_groups`.`partner_id`, `table_groups`.`name`, `table_group_details`.`table_id`, `table_group_details`.`id` AS `table_detail_group_id`, `meja`.`idmeja` FROM `table_groups` LEFT JOIN `table_group_details` ON `table_groups`.`id`=`table_group_details`.`table_group_id`  LEFT JOIN `meja` ON `table_group_details`.`table_id`=`meja`.`id` WHERE `table_groups`.`partner_id`='$partner_id' AND `table_groups`.`deleted_at` IS NULL AND `table_group_details`.`deleted_at` IS NULL ORDER BY `table_groups`.`id`, meja.idmeja");

    if (mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $compareID = 0;
        $i=0;
        $j=0;
        foreach ($data as $value) {
            if($compareID==$value['id']){
                $j+=1;
            }else{
                if($compareID!=0){
                    $i+=1;
                    $j=0;
                }
            }
            $res[$i]['id']=$value['id'];
            $res[$i]['ip']="0";
            $res[$i]['partner_id']=$value['partner_id'];
            $res[$i]['name']=$value['name'];
            $res[$i]['details'][$j]['table_id']=$value['table_id'];
            $res[$i]['details'][$j]['id']=$value['table_detail_group_id'];
            $res[$i]['details'][$j]['idmeja']=$value['idmeja'];
            $compareID = $value['id'];
        }

        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "tables_groups"=>$res]);
?>