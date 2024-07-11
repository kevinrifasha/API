<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

$y=date('Y');
$m=date('n');
while($m<=12){
	if($m<10){
		$m1 = "0".$m;
	}else{
		$m1=$m;
	}
	$ym=$y."-".$m1;
	$firstday = 
	$index = 1;
	$date = $ym."-"."01";
	$last = date("t", strtotime($date));
	while($index<=$last){
		$indexStr=$index;
		if($index<10){
			$indexStr = "0".$index;
		}
		$id = $ym."-".$indexStr;
		$time = strtotime($id);
		echo $id;
		$day = date('l', $time);
        $query = "INSERT INTO `calendar`(`str_date`, `str_day`) VALUES ('$id', '$day')";
        $clone = mysqli_query($db_conn, $query);
		$index+=1;
	}
	$m+=1;
}
        
?>