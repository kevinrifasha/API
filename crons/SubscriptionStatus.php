<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

$todayExact = date("Y-m-d H:i:s");
$now = date("Y-m-d");
$period7= date("Y-m-d",strtotime("+7 days"));
$period3= date("Y-m-d",strtotime("+3 days"));
$period1= date("Y-m-d",strtotime("+1 days"));

$getPartners = mysqli_query($db_conn, "SELECT id, name, subscription_status, trial_until, subscription_until, primary_subscription_id FROM partner WHERE status=1 AND deleted_at IS NULL AND is_testing=0 AND id='000162'");

while($partners = mysqli_fetch_assoc($getPartners)){
    $partnerName = $partners['name'];
    $subsStatus = $partners['subscription_status'];
    $trialUntil = $partners['trial_until'];
    $subscriptionUntil = $partners['subscription_until'];
    $partnerID = $partners['id'];
    $shouldUpgrade=0;
    if($trialUntil!=null){
      $processedTrialUntil = date('Y-m-d', strtotime($trialUntil));
    }
    if($subscriptionUntil!=null){
      $processedSubscriptionUntil =date('Y-m-d',strtotime($subscriptionUntil));
    }
    if($subsStatus=="Trial"){
      if($processedTrialUntil<$now){
          $shouldUpgrade=1;
      }
  }else if($subsStatus=="Subscribed"){
      if($processedSubscriptionUntil<$now || $subscriptionUntil==null){
          $shouldUpgrade=1;
      }
  }else if($subsStatus=="Expired"){
      $shouldUpgrade=1;
  }
  if($shouldUpgrade==1){
    $update = mysqli_query($db_conn, "UPDATE partner SET subscription_status='Expired' WHERE id='$partnerID'");
  }
  $dayRemaining=100;

  echo $partnerID." ".$processedSubscriptionUntil." ".$period1."\n";
  if($subsStatus=="Trial"){
    if($processedTrialUntil==$period7){
      $dayRemaining=7;
    }else if($processedTrialUntil==$period3){
      $dayRemaining=3;
    }else if($processedTrialUntil==$period1){
      $dayRemaining=1;
    }else if($processedTrialUntil==$period1){
      $dayRemaining=0;
    }
    if($dayRemaining==0){
      $title="Masa trial sudah habis";
      $content= $partnerName.", masa trial UR sudah berakhir. Mohon perpanjang langganan agar tetap dapat menikmati layanan kami";
    }else{
      $title="Masa trial hampir habis";
      $content= $partnerName.", masa trial UR berakhir dalam ".$dayRemaining." hari. Mohon perpanjang langganan agar tetap dapat menikmati layanan kami";
    }
  }else if($subsStatus=="Subscribed"){
    if($processedSubscriptionUntil==$period7){
      $dayRemaining=7;
    }else if($processedSubscriptionUntil==$period3){
      $dayRemaining=3;
    }else if($processedSubscriptionUntil==$period1){
      $dayRemaining=1;
    }else if($processedSubscriptionUntil==$period1){
      $dayRemaining=0;
    }
    if($dayRemaining==0){
      $title="Masa berlangganan sudah habis";
      $content= $partnerName.", masa berlangganan UR sudah berakhir. Mohon perpanjang langganan agar tetap dapat menikmati layanan kami";
    }else{
      $title="Masa berlangganan hampir habis";
      $content= $partnerName.", masa berlangganan UR berakhir dalam ".$dayRemaining." hari. Mohon perpanjang langganan agar tetap dapat menikmati layanan kami";
    }
  }

  if($dayRemaining!=100){
    $insertMessage = mysqli_query($db_conn,"INSERT INTO partner_messages SET partner_id='$partnerID', title='$title', content='$content'");
    $getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE id_partner='$partnerID' AND deleted_at IS NULL");
  while($emp = mysqli_fetch_assoc($getEmployees)){
      $employeeID = $emp['id'];
      $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
      while($devID = mysqli_fetch_assoc($getToken)){
          $devToken = $devID['tokens'];
          $sendNotif=mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken'");
      }
  }
  }
}

?>