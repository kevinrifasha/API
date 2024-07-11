<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $i=0;
    $res=[];
    $sql = mysqli_query($db_conn, "SELECT
    *
  FROM
    (
      SELECT
        u.name AS uName,
        ts.created_at,
        p.name AS partnerName,
        (
          6371000 * acos(
            cos(
              radians(p.latitude)
            ) * cos(
              radians(ts.latitude)
            ) * cos(
              radians(ts.longitude) - radians(p.longitude)
            ) + sin(
              radians(p.latitude)
            ) * sin(
              radians(ts.latitude)
            )
          )
        ) AS distance

      FROM
        table_scanner ts JOIN users u ON ts.customer_id = u.id
        JOIN partner p ON p.id = scanned_id
    ) table_scanner
    ");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        // foreach($data as $x){
        //     $res[$i]['uName']=$x['uName'];
        //     $measureDistance = mysqli_query($db_conn,"SELECT (6371 * acos(
        //         cos( radians(lat2) )
        //       * cos( radians( lat1 ) )
        //       * cos( radians( lng1 ) - radians(lng2) )
        //       + sin( radians(lat2) )
        //       * sin( radians( lat1 ) )
        //         ) ) as distance from your_table");
        //     $distance = mysqli_fetch_all($measureDistance, MYSQLI_ASSOC);
        //     $res[$i]['distance']=$distance;
        //     $i++;
        // }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "scanner"=>$data]);
// $sql = mysqli_query($db_conn, "SELECT
// //     *
// //   FROM
// //     (
// //       SELECT
// //         u.name AS uName,
// //         ts.created_at,
// //         p.name AS partnerName,
// //         (
// //           6371000 * acos(
// //             cos(
// //               radians(p.latitude)
// //             ) * cos(
// //               radians(ts.latitude)
// //             ) * cos(
// //               radians(ts.longitude) - radians(p.longitude)
// //             ) + sin(
// //               radians(p.latitude)
// //             ) * sin(
// //               radians(ts.latitude)
// //             )
// //           )
// //         ) AS distance

// //       FROM
// //         table_scanner ts JOIN users u ON ts.customer_id COLLATE utf8_bin = u.id
// //         JOIN partner p ON p.id COLLATE utf8_bin = scanned_id
// //     ) table_scanner
// //     JOIN users u ON ts.customer_id = u.id
// //     JOIN partner p ON p.id = scanned_id
// //   WHERE
// //     ts.deleted_at IS NULL
// //   ");
?>
