<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
  if (preg_match($rx_http, $key)) {
    $arh_key = preg_replace($rx_http, '', $key);
    $rx_matches = array();
    // do some nasty string manipulations to restore the original letter case
    // this should work in most cases
    $rx_matches = explode('_', $arh_key);
    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
      foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
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
  if ($header == "Authorization" || $header == "AUTHORIZATION") {
    $token = substr($value, 7);
  }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
  $status = $tokenValidate['status'];
  $msg = $tokenValidate['msg'];
  $success = 0;
} else {
  $getVariants = mysqli_query($db_conn, "SELECT id,name,price,stock, cogs from variant WHERE is_recipe=1");
  if (mysqli_num_rows($getVariants) > 0) {
    $variants = mysqli_fetch_all($getVariants, MYSQLI_ASSOC);
    foreach ($variants as $x) {
      // if ((int)$x['cogs'] == 0) {
      $variantID = $x['id'];
      // $variantID = 715;
      $total = 0;
      $getRecipes = mysqli_query($db_conn, "SELECT r.id, r.id_variant, r.id_raw, r.qty, rm.name AS rmName, rm.unit_price AS unitPrice, (r.qty*rm.unit_price) AS subtotal FROM recipe r JOIN raw_material rm ON r.id_raw = rm.id WHERE r.id_variant='$variantID' AND r.deleted_at IS NULL AND rm.deleted_at IS NULL");
      if (mysqli_num_rows($getRecipes) > 0) {
        $recipes = mysqli_fetch_all($getRecipes, MYSQLI_ASSOC);
        foreach ($recipes as $y) {
          $total += (float)$y['subtotal'];
        }
      }
      $total = round($total, 2);
      $updateCOGS = mysqli_query($db_conn, "UPDATE variant SET cogs ='$total' WHERE id='$variantID'");
      if ($updateCOGS) {
        echo "\nBerhasil update variant ID " . $variantID . " Harga " . $total;
      }
      // }
    }
  }
}
