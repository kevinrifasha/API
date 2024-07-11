<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require '../db_connection.php';

// $query = "SELECT transaksi.id,
// CASE WHEN DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR) IS NULL THEN transaksi.jam ELSE DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR) END AS hour1
// FROM `transaksi` 
// LEFT JOIN order_status_trackings ON transaksi.id=order_status_trackings.transaction_id AND transaksi.status=order_status_trackings.status_after
// WHERE (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
// $allRecom = mysqli_query($db_conn, $query);
// if (mysqli_num_rows($allRecom) > 0) {
//     $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
//     foreach ($rowR as $value) {
//         $id = $value['id'];
//         $hour1 = $value['hour1'];
//         $update = mysqli_query($db_conn, "UPDATE `transaksi` SET `paid_date`='$hour1' WHERE id='$id'");
//     };
// }else{
//     echo "nothing happens";
// }
?>