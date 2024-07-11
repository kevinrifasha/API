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
    
    if($all == "1") {
        $addQuery1 = "p.id_master='$idMaster'";
        $addQuery2 = "JOIN partner p ON p.id = d.partner_id";
    } else {
        $addQuery1 = "d.partner_id='$id'";
        $addQuery2 = "";
    }
    
    $qDepts = mysqli_query($db_conn,"SELECT d.id, d.name, d.partner_id FROM departments d ". $addQuery2 ." WHERE ". $addQuery1 ." AND d.deleted_at IS NULL");
    
    if(mysqli_num_rows($qDepts) > 0) {
        $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
        $i=0;
        foreach($depts as $x){
            $total=0;
            $deptID = $x['id'];
            $data[$i]['deptName']=$x['name'];
            $partnerID = $x['partner_id'];
            $getSales = mysqli_query($db_conn, "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$partnerID' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$partnerID' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC");
            $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
            $data[$i]['details']=$sales;
            foreach($sales as $x){
                $total+=(double)$x['sales'];
            }
            $data[$i]['total']=$total;
            $j=0;
            $i++;
            
            $result = [];
            if ($all == "1") {
                $result = array_reduce($data, function ($carry, $item) {
                    if (!isset($carry[$item['deptName']])) {
                        $carry[$item['deptName']] = [
                            'deptName' => $item['deptName'], 
                            'details' => $item['details'],
                            'total' => $item['total']
                            ];
                    } else {
                        array_push($carry[$item['deptName']]['details'], ...$item['details']);
                        $carry[$item['deptName']]['total'] += $item['total'];
                    }
                    return $carry;
                });
                $data = [];
                foreach ($result as $obj) {
                    array_push($data, $obj);
                }
            }
            
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "breakdowns"=>$data, "data"=>$depts]);

?>