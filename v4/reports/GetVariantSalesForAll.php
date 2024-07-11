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
$arr = [];
$arr2 = [];

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$data = [];

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $i=0;
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
        
    $most = false;
    $least = false;
    $type = $_GET['type'];
    
    if(isset($_GET['type'])){
        if($type == "most"){
            $most = true;
            $least = false;
        }
        if($type == "least"){
            $most = false;
            $least = true;
        }
        if($type == "mix"){
            $most = true;
            $least = true;
        }
    }

    $arr = [];
    $i=0;
    $total = 0;
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            $reportV = array();
            $totalQty = 0;
            
            $addQuery1 = "partner.id='$id'";
    
            $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, detail_transaksi.qty, menu.nama FROM menu JOIN detail_transaksi ON menu.id=detail_transaksi.id_menu JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id JOIN partner ON transaksi.id_partner=partner.id WHERE ". $addQuery1 ." AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.status!=4 AND transaksi.status IN(1,2)");
            
            if(mysqli_num_rows($fav)>0){
                while ($rowMenu = mysqli_fetch_assoc($fav)) {
                    $variant = $rowMenu['variant'];
                    $namaMenu = $rowMenu['nama'];
                    $qty = $rowMenu['qty'];
                    $variant = substr($variant, 1, -1);
                    $var = "{" . $variant . "}";
                    $var = str_replace("'", '"', $var);
                    $var1 = json_decode($var, true);
        
                    if (
                    isset($var1['variant'])
                    && !empty($var1['variant'])
                    ) {
                    $arrVar = $var1['variant'];
                    foreach ($arrVar as $arr) {
                        $v_id=0;
                        $vg_name = $arr['name'];
                        $detail = $arr['detail'];
                        $v_qty = 0;
                        foreach ($detail as $det) {
                        $v_name =  $det['name'];
                        $v_qty += (int) $qty;
                        $idx = 0;
                        foreach ($reportV as $value) {
                            if($value['id']==$det['id'] && $value['name']==$v_name && $value['vg_name']==$vg_name && $value['menu_name']==$namaMenu){
                                $idx=$idx;
                                break;
                            }else{
                                $idx+=1;
                            }
                        }
                        $v_id=$idx;
                        $dic[$v_id]=$det['id'];
                        $reportV[$v_id]['id'] = $det['id'];
                        
                        // kalo pake cara ini tidak akan error ketika response di browser, tapi akan ada error di log server
                        // $reportV[$v_id]['qty'] ? $reportV[$v_id]['qty'] += $v_qty : $reportV[$v_id]['qty'] = $v_qty;
                        
                        // pakai cara yang ini supaya tidak error di log server
                        ($reportV[$v_id]['qty'] ?? $reportV[$v_id]['qty'] = 0) > 0 ? $reportV[$v_id]['qty'] += $v_qty : $reportV[$v_id]['qty'] = $v_qty;
                        
                        $reportV[$v_id]['name'] = $v_name;
                        $reportV[$v_id]['vg_name'] = $vg_name;
                        $reportV[$v_id]['menu_name'] = $namaMenu;
                        $reportV[$v_id]['reportName'] = $namaMenu."-".$v_name;
                        $totalQty+=$v_qty;
                        }
                    }
                    foreach ($reportV as $key => $row) {
                        $qty[$key]  = $row['qty'];
                        $menu_name[$key] = $row['menu_name'];
                    }
        
                    // you can use array_column() instead of the above code
                    $qty  = array_column($reportV, 'qty');
                    $menu_name = array_column($reportV, 'menu_name');
        
                    // Sort the data with qty descending, menu_name ascending
                    // Add $data as the last parameter, to sort by the common key
                    array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                    }
        
                }
            }
            
            $count = count($reportV);
            if($most == false && $least==false){
                $reportV = $reportV;
            }else{
                if($most == true && $least == false){
                    $reportV = array_slice($reportV, 0, 10);
                } else if($least == true && $most == false){
                    $reportV = array_slice($reportV, -10, 10);
                } else if ($most == true && $least == true){ 
                    $reportMost = array_slice($reportV, 0, 10);
                    $reportLeast = array_slice($reportV, -10, 10);
                    $reportV = array_merge($reportMost, $reportLeast);
                }
                $totalQty = 0;
                foreach($reportV as $item){
                    $totalQty += $item["qty"];
                }
            } 
            
            
            $partner['variantSales'] = $reportV;
            $partner['totalQty'] = $totalQty;
            
            if(count($reportV) > 0) {
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