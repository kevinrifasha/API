<?php
/**
 * Created by PhpStorm.
 * User: Belal
 * Date: 04/02/17
 * Time: 7:51 PM
 */

//importing required script
require_once '../includes/DbOperation.php';
require '../v2/db_connection.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('phone', 'idpartner', 'status', 'total', 'tipebayar','promo', 'is_queue'))) {
        //getting values
        $phone = $_POST['phone'];
        $idpartner = $_POST['idpartner'];
        $nomeja = $_POST['nomeja'];
        $statusTransaksi = $_POST['status'];
        $total = $_POST['total'];
        $tipebayar = $_POST['tipebayar'];
        $id_transaksi = $_POST['id_transaksi'];
        $promo = $_POST['promo'];
        $point = 0;
        if(isset($_POST['point']) && !empty($_POST['point'])){
            $point = $_POST['point'];
        }
        $is_queue = $_POST['is_queue'];
        $takeaway = $_POST['takeaway'];
        $notes = $_POST['notes'];
        $id_foodcourt = $_POST['id_foodcourt'];
        //creating db operation object
        $db = new DbOperation();

        //adding user to database
        // public function createTransaksi($id, $phone, $idpartner, $nomeja, $status, $total, $tipebayar){
        $service = $db->getService($idpartner);
        $tax = $db->getTaxEnabled($idpartner);
        $charge_ewallet = $db->getChargeEwallet();    
        $charge_xendit = $db->getChargeXendit();
        $hide = $db->getHideCharge($idpartner);
        // echo($hide);
        $status = $db->getStatus($idpartner);
        // echo($statusTransaksi);
        $charge_ur =(int)$db->getChargeUr($status, $hide);
        $result = $db->createTransaksi($id_transaksi, $phone, $idpartner, $nomeja, $statusTransaksi, $total ,$tipebayar, $promo, $point, $is_queue, $takeaway, $notes, $id_foodcourt, $tax, $service,$charge_ewallet,$charge_xendit,$charge_ur);

        $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, u.name AS uname FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar JOIN users u ON t.phone=u.phone WHERE t.id='$id_transaksi'");
        $order = mysqli_fetch_assoc($allTrans);
    

        //making the response accordingly
        if ($result == TRANSAKSI_CREATED) {
            if($tipebayar==5 || $tipebayar=="5" || $tipebayar==7 || $tipebayar=="7" || $tipebayar==8 || $tipebayar=="8" || $tipebayar==9 || $tipebayar=="9" ){
                $devTokens = $db->getPartnerDeviceTokens($idpartner);
                foreach ($devTokens as $val) {
                    $mID = $db->getMembership($idpartner, $phone);
                    if($mID==0){
                        $isMembership = false;
                    }else{
                        $isMembership = true;
                    }
                    $birth_date = $db->getBirthdate($phone);
                    $gender = $db->getGender($phone);
                    $notif = $db->pushPaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Baru di Meja '.$nomeja, $nomeja, 'order', $tipebayar, 0, '', $id_transaksi, $idpartner, null, $order, $gender, $birth_date, $isMembership, "employee");
                }
            }
            $response['error'] = false;
            $response['message'] = 'Transaksi Created Succesfully';
        } elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = true;
            $response['message'] = 'Failed To Create Transaksi';
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
    if ($result != USER_ALREADY_EXIST) {
        $response['error'] = false;
        $response['hasil'] = $result;
    }
    else {
        $response['error'] = true;
        $response['message'] = 'Failed To Insert Transaksi';
    }
}



//function to validate the required parameter in request
function verifyRequiredParams($required_fields)
{

    //Getting the request parameters
    $request_params = $_REQUEST;

    //Looping through all the parameters
    foreach ($required_fields as $field) {
        //if any requred parameter is missing
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {

            //returning true;
            return true;
        }
    }
    return false;
}
    
echo json_encode($response);

?>
