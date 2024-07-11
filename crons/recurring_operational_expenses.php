<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

$query = "SELECT `category_id`, `cycle`, `name`, `amount`, `created_by` FROM `recurring_operational_expenses` WHERE deleted_at IS NULL AND status=1";
$sql = mysqli_query($db_conn, $query);
$month = date("n");
$date = date("j");
$day = date("w");
if($day==0){
    $day="Sunday";
}else if($day==1){
    $day="Monday";
}else if($day==2){
    $day="Tuesday";
}else if($day==3){
    $day="Wednesday";
}else if($day==4){
    $day="Thursday";
}else if($day==5){
    $day="Friday";
}else if($day==6){
    $day="Saturday";
}
while($row=mysqli_fetch_assoc($sql)){
    $category_id=$row['category_id'];
    $name=$row['name'];
    $amount=$row['amount'];
    $created_by=$row['created_by'];
    print_r(json_decode($row['cycle']));
    $cycle = json_decode($row['cycle']);
    if($cycle->selectedCycles->value==="Yearly"){
        $selectedMonth = $cycle->selectedMonth;
        foreach($selectedMonth as $month_v){
            if($month_v->value==(int) $month){
                $selectedMonthDays = $cycle->selectedMonthDays;
                foreach($selectedMonthDays as $day_v){
                    if($day_v->value==(int) $date){
                        $insert = "INSERT INTO `operational_expenses`(`category_id`, `name`, `amount`, `created_by`, `created_at`) VALUES ('$category_id', '$name', '$amount', '$created_by', NOW())";
                        $insert = mysqli_query($db_conn, $insert);
                    }
                }
            }
        }
    }else if($cycle->selectedCycles->value==="Monthly"){
        $selectedMonthDays = $cycle->selectedMonthDays;
        foreach($selectedMonthDays as $day_v){
            if($day_v->value==(int) $date){
                $insert = "INSERT INTO `operational_expenses`(`category_id`, `name`, `amount`, `created_by`, `created_at`) VALUES ('$category_id', '$name', '$amount', '$created_by', NOW())";
                $insert = mysqli_query($db_conn, $insert);
            }
        }
    }else if($cycle->selectedCycles->value==="Weekly"){
        $selectedCyclesWeekly = $cycle->selectedCyclesWeekly;
        foreach($selectedCyclesWeekly as $day_v){
            if($day_v->value==$day){
                $insert = "INSERT INTO `operational_expenses`(`category_id`, `name`, `amount`, `created_by`, `created_at`) VALUES ('$category_id', '$name', '$amount', '$created_by', NOW())";
                $insert = mysqli_query($db_conn, $insert);
            }
        }
    }else{
                $insert = "INSERT INTO `operational_expenses`(`category_id`, `name`, `amount`, `created_by`, `created_at`) VALUES ('$category_id', '$name', '$amount', '$created_by', NOW())";
                $insert = mysqli_query($db_conn, $insert);
        
    }
    print_r(mysqli_errno($db_conn));
}