<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../v2/db_connection.php';
// $headers = apache_request_headers();
$timestamp = new DateTime();
$time = $timestamp->format('Y-m-d\TG:i:s.vP');
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
            $relativeUrl = '/qr/bca/payments.php';
            $httpMethod = "POST";

            $getClient = mysqli_query($db_conn, "SELECT * FROM `client` WHERE api_key='$bcaKey' ");
            if (mysqli_num_rows($getClient)>0) {

                $stringToSign = $httpMethod.":".$relativeUrl.":".$idsecret;
                $apiScrt = "l8xggcyh-yahd-peey-weco-3gvlsvd3bh90";
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
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = '';
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"CompanyCode Kosong","English"=>"CompanyCode Null"]));
                    }

                    if(!isset($data['CustomerNumber'])
                    && empty($data['CustomerNumber'])
                    && $boolRes==true
                    || $data['CustomerNumber']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = "";
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"CustomerNumber Kosong","English"=>"CustomerNumber Null" ]));
                    }

                    if(!isset($data['RequestID'])
                    && empty($data['RequestID'])
                    && $boolRes==true
                    || $data['RequestID']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = "";
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"RequestID Kosong","English"=>"RequestID Null"]));
                    }

                    if(!isset($data['ChannelType'])
                    && empty($data['ChannelType'])
                    && $boolRes==true
                    || $data['ChannelType']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"ChannelType Kosong","English"=>"ChannelType Null"]));
                    }

                    if(!isset($data['TransactionDate'])
                    && empty($data['TransactionDate'])
                    && $boolRes==true
                    || $data['TransactionDate']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"TransactionDate Kosong","English"=>"TransactionDate Null"]));
                    }

                    if(!isset($data['CustomerName'])
                    && empty($data['CustomerName'])
                    && $boolRes==true
                    || $data['CustomerName']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"CustomerName Kosong","English"=>"CustomerName Null"]));
                    }

                    if(!isset($data['CurrencyCode'])
                    && empty($data['CurrencyCode'])
                    && $boolRes==true
                    || $data['CurrencyCode']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"CurrencyCode Kosong","English"=>"CurrencyCode Null"]));
                    }

                    if(!isset($data['PaidAmount'])
                    && empty($data['PaidAmount'])
                    && $boolRes==true
                    || $data['PaidAmount']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"PaidAmount Kosong","English"=>"PaidAmount Null"]));
                    }

                    if(!isset($data['TotalAmount'])
                    && empty($data['TotalAmount'])
                    && $boolRes==true
                    || $data['TotalAmount']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"TotalAmount Kosong","English"=>"TotalAmount Null"]));
                    }

                    if(!isset($data['SubCompany'])
                    && empty($data['SubCompany'])
                    && $boolRes==true
                    || $data['SubCompany']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"SubCompany Kosong","English"=>"SubCompany Null"]));
                    }

                    if(!isset($data['Reference'])
                    && empty($data['Reference'])
                    && $boolRes==true
                    || $data['Reference']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"Reference Kosong","English"=>"Reference Null"]));
                    }

                    if(!isset($data['FlagAdvice'])
                    && empty($data['FlagAdvice'])
                    && $boolRes==true
                    || $data['FlagAdvice']==''){
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"FlagAdvice Kosong","English"=>"FlagAdvice Null"]));
                    }

                    if($data['FlagAdvice']=="N" || $data['FlagAdvice']=="Y"){
                    }else{
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"Nilai FlagAdvice Salah","English"=>"Wrong Value FlagAdvice"]));

                    }

                    $format = 'd/m/Y H:i:s';
                    $date = $data['TransactionDate'];
                    $d = DateTime::createFromFormat($format, $date);
                    if($d && $d->format($format) === $date){
                    }else{
                        $boolRes = false;
                        $result['CustomerName']='';
                        $result['PaidAmount']='';
                        $result['TotalAmount']='';
                        $result['CurrencyCode']='IDR';
                        $result['TransactionDate']=$data['TransactionDate'];;
                        $result['DetailBills']=[];
                        $result['FreeTexts']=[];
                        $result['AdditionalData']='';
                        $result['CompanyCode'] = $data['CompanyCode'];
                        $result['CustomerNumber'] = $data['CustomerNumber'];
                        $result['RequestID'] = $data['RequestID'];
                        $result['PaymentFlagStatus'] = "01";
                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"TransactionDate Salah Format","English"=>"Wrong TransactionDate Format"]));
                    }

                    if($boolRes){
                        $cc = $data["CompanyCode"];
                        $cn = $data["CustomerNumber"];
                        $rid = $data['RequestID'];
                        $ad = $data['AdditionalData'];
                        $pa = $data['PaidAmount'];
                        $rf = $data['Reference'];
                        $fa = $data['FlagAdvice'];
                        $ta = $data['TotalAmount'];
                        $getBill1 = mysqli_query($db_conn, "SELECT COUNT(*) AS tot FROM `bills` WHERE company_code='$cc' AND customer_number='$cn' AND paid=1");
                        $data_result1 = mysqli_fetch_all($getBill1, MYSQLI_ASSOC);

                        if($data_result1[0]['tot']>0){
                            $boolRes = false;
                            $result['CustomerName']='';
                            $result['PaidAmount']='';
                            $result['TotalAmount']='';
                            $result['CurrencyCode']='IDR';
                            $result['TransactionDate']=$data['TransactionDate'];;
                            $result['DetailBills']=[];
                            $result['FreeTexts']=[];
                            $result['AdditionalData']='';
                            $result['CompanyCode'] = $data['CompanyCode'];
                            $result['CustomerNumber'] = $data['CustomerNumber'];
                            $result['RequestID'] = $data['RequestID'];
                            $result['PaymentFlagStatus'] = "01";
                            $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"Sudah Dibayar","English"=>"Already Paid"]));

                        }else{
                            $getBill = mysqli_query($db_conn, "SELECT * FROM `bills` WHERE company_code='$cc' AND customer_number='$cn'");
                            $data_result = mysqli_fetch_all($getBill, MYSQLI_ASSOC);

                            if(count($data_result)==0){
                                $boolRes = false;
                                        $result['CustomerName']='';
                                        $result['PaidAmount']='';
                                        $result['TotalAmount']='';
                                        $result['CurrencyCode']='IDR';
                                        $result['TransactionDate']=$data['TransactionDate'];;
                                        $result['DetailBills']=[];
                                        $result['FreeTexts']=[];
                                        $result['AdditionalData']='';
                                        $result['CompanyCode'] = $data['CompanyCode'];
                                        $result['CustomerNumber'] = $data['CustomerNumber'];
                                        $result['RequestID'] = $data['RequestID'];
                                        $result['PaymentFlagStatus'] = "01";
                                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"Tidak Terdaftar","English"=>"Not Listed"]));
                            }else{
                                if(strcmp($data_result[0]['total_amount'],$ta)==0){
                                        $nova = $cc.$cn;
                                        $update = mysqli_query($db_conn, "UPDATE `bills` SET `request_id`='$rid', `additional_data`='$ad', `paid_amount`='$pa',`paid`=1,`reference`='$rf',`flag_advice`='$fa' WHERE company_code='$cc' AND customer_number='$cn'");
                                        $vnum = $cc.$cn;
                                        $update = mysqli_query($db_conn, "UPDATE `master_deposit` SET status=1 WHERE `va_number`='$vnum'");
                                        $getIDM = mysqli_query($db_conn, "SELECT id_master FROM `master_deposit` WHERE va_number='$vnum'");
                                        $data_resultM = mysqli_fetch_all($getIDM, MYSQLI_ASSOC);
                                        $idM = $data_resultM[0]['id_master'];
                                        $am = (int) $pa;
                                        $InsertStats = mysqli_query(
                                            $db_conn,
                                            "UPDATE `master` SET `deposit_balance`=`deposit_balance`+'$am' WHERE id='$idM'"
                                          );
                                        $result['CompanyCode'] = $cc;
                                        $result['CustomerNumber'] = $cn;
                                        $result['RequestID'] = $rid;
                                        $result['PaymentFlagStatus'] = "00";
                                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=> "Sukses","English"=>"Success"]));
                                        $result['CustomerName'] = $data_result[0]['customer_name'];
                                        $result['CurrencyCode'] = $data_result[0]['currency_code'];
                                        $result['PaidAmount'] = $pa;
                                        $result['TotalAmount'] = $data_result[0]['total_amount'];
                                        $result['TransactionDate'] = $data['TransactionDate'];
                                        // $result['TransactionDate'] = strtotime($result['TransactionDate']);
                                        // $result['TransactionDate'] = date("d/m/Y H:i:s",$result['TransactionDate']);
                                        $result['DetailBills'] = [];
                                        $result['FreeTexts'] = [];
                                        $result['AdditionalData'] = "";

                                }else{
                                        $boolRes = false;
                                        $result['CustomerName']='';
                                        $result['PaidAmount']='';
                                        $result['TotalAmount']='';
                                        $result['CurrencyCode']='IDR';
                                        $result['TransactionDate']=$data['TransactionDate'];;
                                        $result['DetailBills']=[];
                                        $result['FreeTexts']=[];
                                        $result['AdditionalData']='';
                                        $result['CompanyCode'] = $data['CompanyCode'];
                                        $result['CustomerNumber'] = $data['CustomerNumber'];
                                        $result['RequestID'] = $data['RequestID'];
                                        $result['PaymentFlagStatus'] = "01";
                                        $result['PaymentFlagReason'] = json_decode(json_encode(["Indonesian"=>"TotalAmount Salah","English"=>"Wrong TotalAmount "]));
                                }
                            }

                        }

                        }

                    echo json_encode($result);

                }else{
                    echo json_encode(["success"=>0,"msg"=>"Wrong Signature", "sig"=>$sig]);
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
