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
$total = 0;
$sorted = [];
$arr = [];
$all = "0";
//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$idRole = $token->role_id;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $arr = [];
    $i=0;
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $total = 0;
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    $qPermission = "
    SELECT
        roles.id,
        roles.is_owner,
        roles.department_access
    FROM
        roles
    WHERE
        roles.id = '$idRole'
    ";
    
    $permissions = mysqli_query($db_conn, $qPermission);
    $permissions = mysqli_fetch_assoc($permissions);
    $is_owner = $permissions['is_owner'];
    $permissions = json_decode($permissions['department_access']);
    
    if($all == "1") {
        $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 AND departments.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY departments.name";
    } else {
        // $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND departments.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
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
            if($add){
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
        $status = 204;
        $msg = "Data Not Found";
    }
    // if(mysqli_num_rows($sqlGetSales) > 0) {
    //     while($row2=mysqli_fetch_assoc($sqlGetSales)){
    //         $namaMenu2 = $row2['nama'];
    //         $qty2 = $row2['qty'];
    //         array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
    //         $total+= $qty2;
    //     }
    //     $success = 1;
    //     $status = 200;
    //     $msg = "Success";
    //     $sorted = array();
    //     $sorted = array_column($arr, 'sales');
    //     array_multisort($sorted, SORT_DESC, $arr);
    // }else{
    //     $success = 0;
    //     $status = 200;
    //     $msg = "Data Not Found";
    // }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "sales"=>$arr, "total"=>$total]);

?>