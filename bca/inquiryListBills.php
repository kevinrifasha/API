<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../v2/db_connection.php';
$timestamp = new DateTime();
$time = $timestamp->format('Y-m-d\TG:i:s.vP');
// $headers = apac?he_request_headers();
$idsecret = '';
$bcaKey ='';
$bcasig = '';
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

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $idsecret=substr($value,7);
    }
    if($header=="X-BCA-KEY" || $header=="X-BCA-Key"){
        $bcaKey=$value;
    }
    if($header=="X-BCA-Signature" || $header=="X-BCA-SIGNATURE"){
        $bcaSig=$value;
    }
}

$validate = "";
$getData = mysqli_query($db_conn, "SELECT * FROM `token` WHERE token.token='$idsecret' ");
if(mysqli_num_rows($getData)>0) {
    $data = mysqli_fetch_all($getData, MYSQLI_ASSOC);
    foreach($data as $dt){
        $expired = $dt['expires_in'];
        $created_at = $dt['created_at'];
        $expire_datetime = strtotime($created_at);
        $expire_datetime = $expire_datetime+$expired;

        $timeNow = strtotime("now");
        if ($timeNow<=$expire_datetime) {
            $relativeUrl = '/qr/bca/inquiryListBills.php';
            $httpMethod = "POST";

            $getClient = mysqli_query($db_conn, "SELECT * FROM `client` WHERE api_key='$bcaKey' ");
            if (mysqli_num_rows($getClient)>0) {
                
                $apiScrt = "l8xggcyh-yahd-peey-weco-3gvlsvd3bh90";
                $stringToSign = $httpMethod.":".$relativeUrl.":".$idsecret;
                $sig = hash_hmac('sha256', $stringToSign, $apiScrt);
                if($bcaSig == $sig){

                    $boolRes = true;
                    $result = array();
                    $data = json_decode(file_get_contents('php://input'), true);

                    if(!isset($data['CompanyCode'])
                    && empty($data['CompanyCode'])
                    && $boolRes==true
                    || $data['CompanyCode']==''){
                        $boolRes = false;
                        $result['TotalAmount'] = '';
                        $result['CompanyCode'] = '';
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['InquiryStatus'] = "01";
                        $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"CompanyCode Kosong","English"=>"CompanyCode's Null"]));
                        $result['CustomerName'] ="";
                        $result['CurrencyCode'] = "IDR";
                        $result['SubCompany'] = "00000";
                        $result['DetailBills'] = [];
                        $result['FreeTexts'] = [];
                        $result['AdditionalData'] = '';

                    }

                    if(!isset($data['CustomerNumber'])
                    && empty($data['CustomerNumber'])
                    && $boolRes==true
                    || $data['CustomerNumber']==''){
                        $boolRes = false;
                        $result['TotalAmount'] = '';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = "";
                        $result['RequestID'] = $data['RequestID'];
                        $result['InquiryStatus'] = "01";
                        $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"CustomerNumber Kosong","English"=>"CustomerNumber's Null"]));
                        $result['CustomerName'] ="";
                        $result['CurrencyCode'] = "IDR";
                        $result['SubCompany'] = "00000";
                        $result['DetailBills'] = [];
                        $result['FreeTexts'] = [];
                        $result['AdditionalData'] = '';

                    }

                    if(!isset($data['RequestID'])
                    && empty($data['RequestID'])
                    && $boolRes==true
                    || $data['RequestID']==''){
                        $boolRes = false;
                        $result['TotalAmount'] = '';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = "";
                        $result['InquiryStatus'] = "01";
                        $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"RequestID Kosong","English"=>"RequestID's Null"]));
                        $result['CustomerName'] ="";
                        $result['CurrencyCode'] = "IDR";
                        $result['SubCompany'] = "00000";
                        $result['DetailBills'] = [];
                        $result['FreeTexts'] = [];
                        $result['AdditionalData'] = '';

                    }

                    if(!isset($data['ChannelType'])
                    && empty($data['ChannelType'])
                    && $boolRes==true
                    || $data['ChannelType']==''){
                        $boolRes = false;
                        $result['TotalAmount'] = '';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['InquiryStatus'] = "01";
                        $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"ChannelType Kosong","English"=>"ChannelType's Null"]));
                        $result['CustomerName'] ="";
                        $result['CurrencyCode'] = "IDR";
                        $result['SubCompany'] = "00000";
                        $result['DetailBills'] = [];
                        $result['FreeTexts'] = [];
                        $result['AdditionalData'] = '';

                    }

                    if(!isset($data['TransactionDate'])
                    && empty($data['TransactionDate'])
                    && $boolRes==true
                    || $data['TransactionDate']==''){
                        $boolRes = false;
                        $result['TotalAmount'] = '';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['InquiryStatus'] = "01";
                        $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"TransactionDate Kosong","English"=>"TransactionDate's Null"]));
                        $result['CustomerName'] ="";
                        $result['CurrencyCode'] = "IDR";
                        $result['SubCompany'] = "00000";
                        $result['DetailBills'] = [];
                        $result['FreeTexts'] = [];
                        $result['AdditionalData'] = '';

                    }


                    $format = 'd/m/Y H:i:s';
                    $date = $data['TransactionDate'];
                    $d = DateTime::createFromFormat($format, $date);
                    if($d && $d->format($format) === $date){
                    }else{
                        $boolRes = false;
                        $result['TotalAmount'] = '';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['InquiryStatus'] = "01";
                        $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"TransactionDate Salah Format","English"=>"Wrong TransactionDate's Format"]));
                        $result['CustomerName'] ="";
                        $result['CurrencyCode'] = "IDR";
                        $result['SubCompany'] = "00000";
                        $result['DetailBills'] = [];
                        $result['FreeTexts'] = [];
                        $result['AdditionalData'] = '';

                    }
                    
                    if($boolRes){
                        $cc = $data["CompanyCode"];
                        $cn = $data["CustomerNumber"];
                        $getBill = mysqli_query($db_conn, "SELECT * FROM `bills` WHERE company_code='$cc' AND customer_number='$cn'");
                        $data_result = mysqli_fetch_all($getBill, MYSQLI_ASSOC);
                        
                        $id_bill = $data_result[0]['id'];
                        $getDetailBill = mysqli_query($db_conn, "SELECT * FROM `details_bill` WHERE id_bill='$id_bill'");
                        $result['CompanyCode'] = $data["CompanyCode"];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        
                        $is_bils = count($data_result);
                        if ($is_bils>0) {
                            $getBill1 = mysqli_query($db_conn, "SELECT * FROM `bills` WHERE company_code='$cc' AND customer_number='$cn' AND paid=0");
                            $data_result1 = mysqli_fetch_all($getBill1, MYSQLI_ASSOC);

                            if(count($data_result1)==0){
                                $result['InquiryStatus'] = "01";
                                $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"Sudah Dibayar","English"=>"Already Paid"]));
                                $result['CustomerName'] = '';
                                $result['CurrencyCode'] = 'IDR';
                                $result['SubCompany'] = "00000";
                                $result['TotalAmount'] = "0.00";
                                $result['DetailBills'] = [];
                                $result['FreeTexts'] =[];
                                $result['AdditionalData'] = "";
                            }else{
                                $result['InquiryStatus'] = "00";
                                $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"Sukses","English"=>"Success"]));
                                $result['CustomerName'] = $data_result[0]['customer_name'];
                                $result['CurrencyCode'] = $data_result[0]['currency_code'];
                                $result['SubCompany'] = $data_result[0]['sub_company'];
                                $result['TotalAmount'] = $data_result[0]['total_amount'];
                                
                                $detail_result = mysqli_fetch_all($getDetailBill, MYSQLI_ASSOC);
                                $length_detail = count($detail_result);
                                if ($length_detail>0) {
                                    for ($i=0; $i < $length_detail; $i++) { 
                                        $result['DetailBills'][$i]["BillDescription"] = $detail_result[$i]['description'];
                                        $result['DetailBills'][$i]["BillAmount"] = $detail_result[$i]['amount'];
                                        $result['DetailBills'][$i]["BillNumber"] = $detail_result[$i]['number'];
                                        $result['DetailBills'][$i]["BillSubCompany"] = $detail_result[$i]['sub_company'];
                                    }
                                    
                                }else{
                                    $result['DetailBills'] = [];
                                }
                                $result['FreeTexts'] = [];
                                $result['AdditionalData'] = "";
                                $rid = $data['RequestID'];
                                $ad = "";
                                $update = mysqli_query($db_conn, "UPDATE `bills` SET `request_id`='$rid', `additional_data`='$ad' WHERE company_code='$cc' AND customer_number='$cn'");
                            }
                        }
                        else{
                            $result['InquiryStatus'] = "01";
                            $result['InquiryReason'] = json_decode(json_encode(["Indonesian"=>"Tidak Terdaftar","English"=>"Not Listed"]));
                            $result['CustomerName'] = '';
                            $result['CurrencyCode'] = 'IDR';
                            $result['SubCompany'] = "00000";
                            $result['TotalAmount'] = "0.00";
                            $result['DetailBills'] = [];
                            $result['FreeTexts'] =[];
                            $result['AdditionalData'] = "";
                        }
                    }

                    echo json_encode($result);
                    
                }else{
                    echo json_encode(["success"=>0,"msg"=>"Wrong Signature"]);
                }
                
            }else{
                echo json_encode(["success"=>0,"msg"=>"Wrong API KEY"]);
            }
            
        } else {
            echo json_encode(["success"=>0,"msg"=>"Expired"]);
        }
        
    }
}else{
    echo json_encode(["success"=>0,"msg"=>"Wrong Authorization"]);
}
echo $validate;
?>
