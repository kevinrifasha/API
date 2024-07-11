<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./../tokenModels/tokenManager.php"); 
// require_once("../connection.php");
// require '../../db_connection.php';
// require  __DIR__ . '/../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';
    
// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $value = array();
// $success=0;
// $msg = 'Failed';
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;
    
// }else{
//     $arr = [];
//     $i=0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $total = 0;
//     $i=0;
//     $trxID="";
//     $subtotal=0;
//     $service=0;
//     $serviceandCharge=0;
//     $tax=0;
//     $grandTotal=0;
//     $sum=0;
//     $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL ORDER BY id ASC";
//     $sqlGetDepts = mysqli_query($db_conn, $query);
//     if(mysqli_num_rows($sqlGetDepts) > 0) {
//         $resDepts = mysqli_fetch_all($sqlGetDepts, MYSQLI_ASSOC);
//         foreach($resDepts as $x){
//             $deptID = $x['id'];
//             $deptName = $x['name'];
//             $getTransactions = mysqli_query($db_conn, "SELECT dt.harga,t.id,
//             t.program_discount,
//             t.promo,
//             t.diskon_spesial,
//             t.employee_discount,
//             t.service,
//             t.tax,
//             t.charge_ur FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu JOIN categories c ON m.id_category=c.id JOIN departments d ON d.id=c.department_id JOIN transaksi t ON t.id=dt.id_transaksi WHERE DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND dt.deleted_at IS NULL AND d.id='$deptID' AND t.status IN(1,2) AND t.deleted_at IS NULL");
//             while($trx = mysqli_fetch_assoc($getTransactions)){
//                 $trxID = $trx['id'];
//                 $subtotal = $trx['harga']-$trx['program_discount']-$trx['promo']-$trx['diskon_spesial']-$trx['employee_discount'];
//                 $service = ceil($subtotal*$trx['service']/100);
//                 $serviceandCharge = $service + $trx['charge_ur'];
//                 $tax = ceil(($subtotal+$serviceandCharge)*$trx['tax']/100);
//                 $grandTotal = $subtotal+$serviceandCharge+$tax;
//                 $sum += $grandTotal;
//             }
//             $res[$i]['id']=$deptID;
//             $res[$i]['name']=$deptName;
//             $res[$i]['netSales']+=$sum;
//             $i++;
//         }
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     }else{
//         $success = 0;
//         $status = 204;
//         $msg = "Data Not Found";
//     }
    
// }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "sales"=>$res]);  

?>