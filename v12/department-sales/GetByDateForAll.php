// <?php
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
// $idMaster = $tokenDecoded->masterID;
// $value = array();
// $success=0;
// $msg = 'Failed';
// $array = [];

// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;
    
// }else{
//     $i=0;
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
    
//     $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
//     if(mysqli_num_rows($sqlPartner) > 0) {
//         $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
//         foreach($getPartners as $partner) {
//             $id = $partner['partner_id'];
//             $arr = [];
//             $data = [];
//             $result = [];
//             $total = 0;
            
//             // get salesnya
//             $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
    
//             $sqlGetSales = mysqli_query($db_conn, $query);
//             if(mysqli_num_rows($sqlGetSales) > 0) {
//                 while($row2=mysqli_fetch_assoc($sqlGetSales)){
//                     $namaMenu2 = $row2['nama'];
//                     $qty2 = (int) $row2['qty'];
//                     $i=0;
//                     $add = true;
//                     foreach ($arr as $value) {
//                         if($value['name']==$namaMenu2){
//                             $arr[$i]['sales']+= $qty2;
//                             $add = false;
//                             // $total+= $qty2;
//                         }
//                         $i+=1;
//                     }
//                     if($add==true){
//                         array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
//                     }
//                     $total+= $qty2;
//                 }
                
//                 $sorted = array();
//                 $sorted = array_column($arr, 'sales');
//                 array_multisort($sorted, SORT_DESC, $arr);
//             }
//             // get salesnya end
            
//             // get breakdownnya
//             // $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL";
//             $query = "SELECT id, name FROM departments WHERE master_id='$idMaster' AND deleted_at IS NULL";
    
//             $qDepts = mysqli_query($db_conn, $query);
//             if (mysqli_num_rows($qDepts) > 0) {
//                 $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
//                 $i = 0;
//                 $id_partner = $id;
//                 foreach ($depts as $x) {
//                     $deptID = $x['id'];
//                     $data[$i]['deptName'] = $x['name'];
//                     if ($all == "1") {
//                         $id_partner = $x['id_partner'];
//                     }
//                     $qSales = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$id_partner' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC";
//                     $getSales = mysqli_query($db_conn, $qSales);
//                     $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
//                     $data[$i]['details'] = $sales;
//                     $j = 0;
//                     $i++;
//                     $result = [];
//                 }
//             }
//             // get breakdownnya end

//             $arrData = [];
//             foreach($data as $val) {
//                 if(count($val['details']) > 0) {
//                     array_push($arrData, $val);
//                 }
//             }
            
//             $partner['sales'] = $arr;
//             $partner['total'] = $total;
//             // $partner['breakdowns'] = $data;
//             $partner['breakdowns'] = $arrData;
            
//             if(count($arr) > 0) {
//                 array_push($array, $partner);
//             }
//         }
        
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     } else {
//         $success = 0;
//         $status = 203;
//         $msg = "Data Not Found";
//     }
    
// }

// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);  

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
$value = array();
$success=0;
$msg = 'Failed';
$array = [];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $i=0;
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if($newDateFormat == 1)
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $arr = [];
                $data = [];
                $result = [];
                $total = 0;
                
                // get salesnya
                $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
        
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
                    
                    $sorted = array();
                    $sorted = array_column($arr, 'sales');
                    array_multisort($sorted, SORT_DESC, $arr);
                }
                // get salesnya end
                
                // get breakdownnya
                // $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL";
                $query = "SELECT id, name FROM departments WHERE master_id='$idMaster' AND deleted_at IS NULL";
        
                $qDepts = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($qDepts) > 0) {
                    $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
                    $i = 0;
                    $id_partner = $id;
                    foreach ($depts as $x) {
                        $deptID = $x['id'];
                        $data[$i]['deptName'] = $x['name'];
                        if ($all == "1") {
                            $id_partner = $x['id_partner'];
                        }
                        $qSales = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$id_partner' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC";
                        $getSales = mysqli_query($db_conn, $qSales);
                        $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                        $data[$i]['details'] = $sales;
                        $j = 0;
                        $i++;
                        $result = [];
                    }
                }
                // get breakdownnya end
    
                $arrData = [];
                foreach($data as $val) {
                    if(count($val['details']) > 0) {
                        array_push($arrData, $val);
                    }
                }
                
                $partner['sales'] = $arr;
                $partner['total'] = $total;
                // $partner['breakdowns'] = $data;
                $partner['breakdowns'] = $arrData;
                
                if(count($arr) > 0) {
                    array_push($array, $partner);
                }
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 203;
            $msg = "Data Not Found";
        }
    }
    else
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $arr = [];
                $data = [];
                $result = [];
                $total = 0;
                
                // get salesnya
                $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
        
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
                    
                    $sorted = array();
                    $sorted = array_column($arr, 'sales');
                    array_multisort($sorted, SORT_DESC, $arr);
                }
                // get salesnya end
                
                // get breakdownnya
                // $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL";
                $query = "SELECT id, name FROM departments WHERE master_id='$idMaster' AND deleted_at IS NULL";
        
                $qDepts = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($qDepts) > 0) {
                    $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
                    $i = 0;
                    $id_partner = $id;
                    foreach ($depts as $x) {
                        $deptID = $x['id'];
                        $data[$i]['deptName'] = $x['name'];
                        if ($all == "1") {
                            $id_partner = $x['id_partner'];
                        }
                        $qSales = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$id_partner' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC";
                        $getSales = mysqli_query($db_conn, $qSales);
                        $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                        $data[$i]['details'] = $sales;
                        $j = 0;
                        $i++;
                        $result = [];
                    }
                }
                // get breakdownnya end
    
                $arrData = [];
                foreach($data as $val) {
                    if(count($val['details']) > 0) {
                        array_push($arrData, $val);
                    }
                }
                
                $partner['sales'] = $arr;
                $partner['total'] = $total;
                // $partner['breakdowns'] = $data;
                $partner['breakdowns'] = $arrData;
                
                if(count($arr) > 0) {
                    array_push($array, $partner);
                }
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 203;
            $msg = "Data Not Found";
        }

    }

    
}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);  
