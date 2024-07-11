<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

$dateFirstDbStr = "20200101";
$dateLastDbStr = "20201231";
$nameT = "transactions_".$dateFirstDbStr."_".$dateLastDbStr;
$nameDT = "detail_transactions_".$dateFirstDbStr."_".$dateLastDbStr;

$query = "CREATE TABLE `$nameDT` SELECT detail_transactions_20190101_20211208.* FROM detail_transactions_20190101_20211208 JOIN transactions_20190101_20211208 ON transactions_20190101_20211208.id=detail_transactions_20190101_20211208.id_transaksi WHERE DATE(jam) BETWEEN '2020-01-01' AND '2020-12-31' ";
var_dump($query);
$clone = mysqli_query($db_conn, $query);
var_dump($clone);
$queryD = "ALTER TABLE `$nameDT` ADD PRIMARY KEY( `id`);";
$cloneD = mysqli_query($db_conn, $queryD);
if(mysqli_errno($db_conn)==0){
    $query = "DELETE detail_transactions_20190101_20211208 FROM detail_transactions_20190101_20211208 JOIN transactions_20190101_20211208 ON transaksi.id=detail_transactions_20190101_20211208.id_transaksi  WHERE DATE(jam) BETWEEN '2020-01-01' AND '2020-12-31'";
    $clone = mysqli_query($db_conn, $query);
    if(mysqli_errno($db_conn)==0){ 
        $query = "CREATE TABLE `$nameT` SELECT * FROM transactions_20190101_20211208  WHERE DATE(jam) BETWEEN '2020-01-01' AND '2020-12-31' ";
        $clone = mysqli_query($db_conn, $query);
        $alter = "ALTER TABLE `$nameT` ADD PRIMARY KEY( `id`);";
        $alterclone = mysqli_query($db_conn, $alter);
        if(mysqli_errno($db_conn)==0){ 
            $query = "DELETE FROM transactions_20190101_20211208 WHERE DATE(jam) BETWEEN '2020-01-01' AND '2020-12-31'";
            $clone = mysqli_query($db_conn, $query);
        }
    }
}