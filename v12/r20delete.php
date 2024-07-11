<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./tokenModels/tokenManager.php");
// require_once("connection.php");
// require '../db_connection.php';
// require  __DIR__ . '/../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
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
// $getSFG = mysqli_query($db_conn, "SELECT r.sfg_id FROM recipe r JOIN raw_material rm ON rm.id=r.sfg_id where rm.id_partner='000217' AND r.deleted_at IS NULL AND rm.deleted_at IS NULL AND r.sfg_id!=0 GROUP BY r.sfg_id");
// $resSFG = mysqli_fetch_all($getSFG, MYSQLI_ASSOC);
// foreach($getSFG as $x){
//     $sfgID = $x['sfg_id'];
//     $getSFGRecipe = mysqli_query($db_conn, "SELECT r.* FROM recipe r WHERE sfg_id='$sfgID'");
//     $sfgRecipe = mysqli_fetch_all($getSFGRecipe, MYSQLI_ASSOC);

//     $getMenuSFGR = mysqli_query($db_conn, "SELECT r.id_menu FROM recipe r WHERE id_raw='$sfgID'");
//     $menuSFGR = mysqli_fetch_all($getMenuSFGR, MYSQLI_ASSOC);
//     foreach($menuSFGR as $y){
//         $menuID = $y['id_menu'];
//         $q="delete from recipe where id_menu='$menuID' AND id_raw='$sfgID'";
//             $insertRecipe = mysqli_query($db_conn, $q);
//     }
// }

?>