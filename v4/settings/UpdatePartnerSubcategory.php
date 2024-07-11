<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('../auth/Token.php');

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
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{

    $data = json_decode(file_get_contents('php://input'));
    $id = $token->id_partner;
    $query = "";
    // if(!empty($data->subcategory)){

    //     $subcategory = $data->subcategory;
        
    //     $error_in = "";
    //     foreach($subcategory as $subs){
    //         $sub= $subs["subcategory_id"];
    //         // $sub= $subs->subcategory_id;
    //         $psa_id = $subs["psa_id"];
    //         // $psa_id = $subs->psa_id;
    //         $action = $subs["action"];
    //         // $action = $subs->action;
            
    //         $qValidateSubCat = "SELECT psa.id, psa.subcategory_id, psa.category_id FROM partner_subcategory_assignments WHERE partner_id='$id' AND psa.subcategory_id='$sub' AND psa.deleted_at IS NULL";
    //         $qSqlValidateSubCat = mysqli_query($db_conn, $qValidateSubCat);
    //         $qCat = mysqli_query($db_conn, "SELECT category_id FROM partner_subcategories WHERE id='$sub'");
    //         $fetchCat = mysqli_fetch_all($qCat, MYSQLI_ASSOC);
    //         $catID = $fetchCat[0]["category_id"];
            
    //         if(mysqli_num_rows($qSqlValidateSubCat) || ($psa_id != 0 && $psa_id != "0")){
    //             if($catID){
    //                 $qUpdate = mysqli_query($db_conn, "UPDATE  partner_subcategory_assignments SET category_id='$catID', subcategory_id='$sub' WHERE partner_id='$id' AND id='$psa_id'");
    //                 if($qUpdate){
    //                 }else{
    //                     $error_in = $sub . ",Insert";
    //                     break;
    //                 }
    //             } else {
    //                 $error_in = $sub . ",catID";
    //                 break;
    //             }
    //         } else if ($action != "delete" || ($psa_id = 0 || $psa_id = "0")){
    //             if($catID){
    //                 $qInsert = mysqli_query($db_conn, "INSERT INTO partner_subcategory_assignments SET category_id='$catID', subcategory_id='$sub', partner_id='$id'");
    //                 if($qInsert){
    //                 }else{
    //                     $error_in = $sub . ",Insert";
    //                     break;
    //                 }
    //             } else {
    //                 $error_in = $sub . ",catID";
    //                 break;
    //             }
    //         } else {
    //             $qDelete = mysqli_query($db_conn, "UPDATE  partner_subcategory_assignments SET deleted_at=NOW() WHERE partner_id='$id' AND id='$psa_id'");
    //         }
            
            
    //     }
    //  if($error_in == ""){
    //      $success = 1;
    //      $status = 200;
    //      $msg = "Berhasil Ubah Data";
         
    //  }else{
    //      $success = 0;
    //      $status = 203;
    //      $msg="Gagal Ubah Data";
    //     //  $msg=$error_in;
    //  }
    // }else{
    //     $success = 0;
    //      $status = 200;
    //      $msg="Data tidak lengkap";
    // }
    
    if(!empty($data->subcategory)){

        $subcategory = $data->subcategory;
        
        //check subcategory in db for that partner
        $qSubCat = mysqli_query($db_conn, "SELECT psa.subcategory_id FROM partner_subcategory_assignments psa WHERE psa.partner_id='$id' AND psa.deleted_at IS NULL");
        $fetchSubCat = mysqli_fetch_all($qSubCat, MYSQLI_ASSOC);
        $subCatVal = [];
        foreach($fetchSubCat as $sc){
            array_push($subCatVal,$sc["subcategory_id"]);
        }
        
        $subId = $data->subcategory;
        
        $error_in = "";
        foreach($subId as $subs){
            $sub= $subs;
            if(in_array($sub, $subCatVal)){
                continue;    
            } else {
                 // // $action = $subs->action;
                $qValidateSubCat = "SELECT psa.id, psa.subcategory_id, psa.category_id FROM partner_subcategory_assignments WHERE partner_id='$id' AND psa.subcategory_id='$sub' AND psa.deleted_at IS NULL";
                $qSqlValidateSubCat = mysqli_query($db_conn, $qValidateSubCat);
                $qCat = mysqli_query($db_conn, "SELECT category_id FROM partner_subcategories WHERE id='$sub'");
                $fetchCat = mysqli_fetch_all($qCat, MYSQLI_ASSOC);
                $catID = $fetchCat[0]["category_id"];
                
                $qInsert = mysqli_query($db_conn, "INSERT INTO partner_subcategory_assignments SET category_id='$catID', subcategory_id='$sub', partner_id='$id'");
            }

        }
        
        //delete partner subcategory
        foreach($subCatVal as $sub){
             if(in_array($sub, $subId)){
                continue;
             } else {
                 $qDelete = mysqli_query($db_conn, "UPDATE  partner_subcategory_assignments SET deleted_at=NOW() WHERE partner_id='$id' AND subcategory_id='$sub'");
             }
        }
        
     if($error_in == ""){
         $success = 1;
         $status = 200;
         $msg = "Berhasil Ubah Data";
         
     }else{
         $success = 0;
         $status = 203;
         $msg="Gagal Ubah Data";
        //  $msg=$error_in;
     }
    }else{
        $success = 0;
         $status = 200;
         $msg="Data tidak lengkap";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>

