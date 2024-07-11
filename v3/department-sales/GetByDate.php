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
$idMaster = $tokenDecoded->masterID;
$idEmployee = $tokenDecoded->id;
$value = array();
$success=0;
$msg = 'Failed';
$arr = [];
$total = 0;
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $i=0;
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $newDateFormat = 0;
    
    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    $query = "
    SELECT
        roles.id,
        roles.is_owner,
        roles.w47,
        roles.department_access
    FROM
        employees
    LEFT JOIN roles ON roles.id = employees.role_id
    WHERE
        employees.id = '$idEmployee'
    ";
    
    $permissions = mysqli_query($db_conn, $query);
    $permissions = mysqli_fetch_assoc($permissions);
    $is_owner = $permissions['is_owner'];
    $w47 = $permissions['w47'];
    $permissions = json_decode($permissions['department_access']);
    
    if($newDateFormat == 1){
    
        if($all == "1") {
            $query ="SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id AS id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2,3,4) AND detail_transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY departments.id";
        } else {
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id AS id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2,3,4) AND detail_transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
        }
    
        $sqlGetSales = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($sqlGetSales) > 0) {
            while($row2=mysqli_fetch_assoc($sqlGetSales)){
                $idDepartment = $row2['id'];
                $namaMenu2 = $row2['nama'];
                $qty2 = (int) $row2['qty'];
                $i=0;
                $add = true;
                if ($w47 == '0'){
                    foreach ($arr as $value) {
                        if($value['name']==$namaMenu2){
                            $arr[$i]['sales']+= $qty2;
                            $add = false;
                            // $total+= $qty2;
                        }
                        $i+=1;
                    }
                    $total+= $qty2;
                } else {
                    $flag = false;
                    foreach ($permissions as $permission) {
                        if ($permission->id == $idDepartment){
                            foreach ($arr as $value) {
                                if($value['name']==$namaMenu2){
                                    $arr[$i]['sales']+= $qty2;
                                    $add = false;
                                    // $total+= $qty2;
                                }
                                $i+=1;
                            }
                            $flag = true;
                            $total+= $qty2;
                            break;
                        }
                    }
                    $add = $add && $flag;
                }
                if($add==true){
                    array_push($arr, array("id" => "$idDepartment", "name" => "$namaMenu2", "sales" => $qty2));
                }
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
            $sorted = array();
            $sorted = array_column($arr, 'sales');
            array_multisort($sorted, SORT_DESC, $arr);
        }else{
            $success = 0;
            $status = 203;
            $msg = "Data Not Found";
        }
    }
    else{

        if($all == "1") {
            $query ="SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id AS id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2,3,4) AND detail_transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN DATE('$dateFrom') AND DATE('$dateTo') GROUP BY departments.id";
        } else {
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id AS id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2,3,4) AND detail_transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN DATE('$dateFrom') AND DATE('$dateTo')  GROUP BY departments.id";
        }
    
        $sqlGetSales = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($sqlGetSales) > 0) {
            while($row2=mysqli_fetch_assoc($sqlGetSales)){
                $idDepartment = $row2['id'];
                $namaMenu2 = $row2['nama'];
                $qty2 = (int) $row2['qty'];
                $i=0;
                $add = true;
                if ($is_owner == '1'){
                    foreach ($arr as $value) {
                        if($value['name']==$namaMenu2){
                            $arr[$i]['sales']+= $qty2;
                            $add = false;
                            // $total+= $qty2;
                        }
                        $i+=1;
                    }
                    $total+= $qty2;
                } else {
                    $flag = false;
                    foreach ($permissions as $permission) {
                        if ($permission->id == $idDepartment){
                            foreach ($arr as $value) {
                                if($value['name']==$namaMenu2){
                                    $arr[$i]['sales']+= $qty2;
                                    $add = false;
                                    // $total+= $qty2;
                                }
                                $i+=1;
                            }
                            $flag = true;
                            $total+= $qty2;
                            break;
                        }
                    }
                    $add = $add && $flag;
                }
                if($add==true){
                    array_push($arr, array("id" => "$idDepartment", "name" => "$namaMenu2", "sales" => $qty2));
                }
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
            $sorted = array();
            $sorted = array_column($arr, 'sales');
            array_multisort($sorted, SORT_DESC, $arr);
        }else{
            $success = 0;
            $status = 203;
            $msg = "Data Not Found";
        }
    }
    

}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "sales"=>$arr, "total"=>$total, "test"=>$permissions]);
