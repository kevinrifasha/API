<?php
/**
 * Created by PhpStorm.
 * User: Belal
 * Date: 04/02/17
 * Time: 7:51 PM
 */

//importing required script
require_once '../includes/DbOperation.php';


$response = array();
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('id_transaksi','carts'), $data)) {
        //getting values
        
        //creating db operation object
        $db = new DbOperation();

        $carts = $data["carts"];

        $res = array();
        $a = array();
        $bool = false;
        $countFalse = 0;
        $i=0;
        foreach ($carts as $cart) {

            $id_menu = $cart['id_menu'];
            $qty = $cart['qty'];
            
            $a = $db->cekQtyDb($id_menu);

            
            if($a['stock'] < $qty){

                $bool = true;
                $res[$countFalse]["nama"] = $a['nama']; 
                $countFalse+=1;

            }
            $i+=1;
        }
        
        if($bool==false){
            $cekQty = true;
        }else{
            $cekQty = false;
        }
        
        if ($cekQty==true) {
            // adding user to database
            $result = $db->insertDetailTransaksi($data["id_transaksi"], $data["carts"]);
    
            //making the response accordingly
            if ($result == TRANSAKSI_CREATED) {
                $response['error'] = false;
                $response['message'] = 'Detil Transaksi Inserted Succesfully';
            } elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
                $response['error'] = true;
                $response['message'] = 'Failed To Insert Transaksi';
            }
        } else {
            $response['error'] = true;
            $response['meesage'] = "stock menu";
            $i = 1;
            $count = count($res);
            foreach($res as $nama){
                if ($i==1) {
                    $response['meesage'] .= " ".$nama['nama'];
                }else if($i==$count){
                    $response['meesage'] .= " dan ".$nama['nama'];
                }else{
                    $response['meesage'] .= ", ". $nama['nama'];
                }
                $i+=1;
            }
            $response['meesage'] .= " tidak mencukupi. Silahkan kurangi pesanan";

        }
        

    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameters are missing';
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $db = new DbOperation();
    $result = $db->getIDTransaksi();

        //making the response accordingly
        if ($result != FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = false;
            $response['id_transaksi'] = $result;
        } elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = true;
            $response['message'] = 'Failed To Insert Transaksi';
        }
}



//function to validate the required parameter in request
function verifyRequiredParams($required_fields, $datas)
{
    
    foreach ($required_fields as $fields)
    {
        if(in_array($fields, $datas))
        {
            return true;
        }
    }
    return false;
    
}
    
echo json_encode($response);

?>
