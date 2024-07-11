<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
$sorted = [];

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $i=0;
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $data = [];
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            $arr = [];
            $total = 0;
            
             $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
    
            $sqlGetSales = mysqli_query($db_conn, $query);
            if(mysqli_num_rows($sqlGetSales) > 0) {
                while($row2=mysqli_fetch_assoc($sqlGetSales)){
                    $namaMenu2 = $row2['nama'];
                    $qty2 = (int) $row2['qty'];
                    $i=0;
                    $add = true;
                    foreach ($arr as $value) {
                        if($value['name']==$namaMenu2){
                            $arr[$i]['sales']+= $qty2;
                            $add = false;
                            // $total+= $qty2;
                        }
                        $i+=1;
                    }
                    if($add==true){
                        array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
                    }
                    $total+= $qty2;
                }
                $success = 1;
                $status = 200;
                $msg = "Success";
                $sorted = array();
                $sorted = array_column($arr, 'sales');
                array_multisort($sorted, SORT_DESC, $arr);
            }
            
            $partner['sales'] = $arr;
            $partner['total'] = $total;
            
            if(count($arr) > 0) {
                array_push($data, $partner);
            }
        }
        
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);

?>