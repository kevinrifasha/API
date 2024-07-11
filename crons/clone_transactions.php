<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

$dateFirstDbStr = date('Ym01', strtotime('-1 month'));
$dateLastDbStr = date('Ymt', strtotime('-1 month'));
$nameT = "transactions_".$dateFirstDbStr."_".$dateLastDbStr;
$nameDT = "detail_transactions_".$dateFirstDbStr."_".$dateLastDbStr;

$query = "CREATE TABLE `$nameDT` SELECT detail_transaksi.* FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi WHERE transaksi.status=2 OR transaksi.status=3 OR transaksi.status=4";
$clone = mysqli_query($db_conn, $query);
$queryD = "ALTER TABLE `$nameDT` ADD PRIMARY KEY( `id`);";
$cloneD = mysqli_query($db_conn, $queryD);
if(mysqli_errno($db_conn)==0){
    $query = "DELETE detail_transaksi FROM detail_transaksi INNER JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi WHERE transaksi.status=2 OR transaksi.status=3 OR transaksi.status=4";
    $clone = mysqli_query($db_conn, $query);
    if(mysqli_errno($db_conn)==0){ 
        $query = "CREATE TABLE `$nameT` SELECT * FROM transaksi WHERE status=2 OR status=3 OR status=4";
        $clone = mysqli_query($db_conn, $query);
        $alter = "ALTER TABLE `$nameT` ADD PRIMARY KEY( `id`);";
        $alterclone = mysqli_query($db_conn, $alter);
        if(mysqli_errno($db_conn)==0){ 
            $query = "DELETE FROM transaksi WHERE status=2 OR status=3 OR status=4";
            $clone = mysqli_query($db_conn, $query);
        }
    }
}