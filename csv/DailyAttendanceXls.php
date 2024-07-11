<html>
<head>
	<title>Laporan Kehadiran</title>
</head>
<body>
	<style type="text/css">
	body{
		font-family: sans-serif;
	}
	table{
		margin: 20px auto;
		border-collapse: collapse;
	}
	table th,
	table td{
		border: 1px solid #3c3c3c;
		padding: 3px 8px;
 
	}
	a{
		background: blue;
		color: #fff;
		padding: 8px 10px;
		text-decoration: none;
		border-radius: 2px;
	}
	</style>
 
    <?php
      date_default_timezone_set('Asia/Jakarta');
      $dateFormat = date("2021-07-16");
      $id = $_GET['partnerID'];
      $date = $_GET['date'];
      header("Content-Type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=Laporan_Kehadiran_\"$dateFormat\".xls");
      require '../db_connection.php';
      require_once '../includes/CalculateFunctions.php';
      $cf = new CalculateFunction();
      $fID="";
      $name="";
      $jamBuka="";
      $jamTutup="";
      $partnerQ = mysqli_query($db_conn, "SELECT id,name,jam_buka,jam_tutup FROM partner WHERE MD5(id)='$id'");
      while($row = mysqli_fetch_array($partnerQ)){
          $fID=$row['id'];
          $name=$row['name'];
          $jamBuka=$row['jam_buka'];
          $jamTutup=$row['jam_tutup'];
      }
      $dateNow = date('d m Y', strtotime($date));
      $dateFormat = $date;
      $dateNowDb = $date;

      
      function rupiah($angka)
      {
        $hasil_rupiah = "Rp" . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
	?>
 	<center>
        <h1>Laporan Kehadiran
             <br><?php echo $name ?></h1>
	</center>
    <b>Tanggal: <?php echo $dateNow;?><b>
    <?php
    $attendance = mysqli_query($db_conn, "SELECT a.id, a.employee_id, a.in_time, a.out_time, a.in_image, a.out_image, k.nama as name, DATE(a.in_time) as date,HOUR(a.in_time) as in_hour, MINUTE(a.in_time) as in_minute, SECOND(a.in_time) as in_second, HOUR(a.out_time) as out_hour, MINUTE(a.out_time) as out_minute, SECOND(a.out_time) as out_second, a.schedule_in_time, a.schedule_out_time  FROM attendance a JOIN employees k ON k.id=a.employee_id WHERE k.id_partner='$fID' AND DATE(a.in_time) BETWEEN '$dateNowDb' AND '$dateNowDb' ORDER BY DATE(a.in_time) DESC  ");
    
    if(mysqli_num_rows($attendance) > 0) {
        $all_attendance = mysqli_fetch_all($attendance, MYSQLI_ASSOC);
        $res = array();
        $i = 0;
        $j = 0;
        $firts = true;
        foreach ($all_attendance as  $value) {
            if($value['in_hour']<10){
                $value['in_hour']="0".$value['in_hour'];
            }
            if($value['in_minute']<10){
                $value['in_minute']="0".$value['in_minute'];
            }
            if($value['in_second']<10){
                $value['in_second']="0".$value['in_second'];
            }
            $in_time = $value['in_hour'].":".$value['in_minute'].":".$value['in_second'];
            $in_time = strtotime($in_time);
            $s_in_time = strtotime($value['schedule_in_time']);
            $status_a = "Tepat Waktu";
            if($in_time>$s_in_time){
                $status_a = "Terlambat";
            }
            $value['in_status']=$status_a;
            
            $status_a="Belum Keluar";
            if(!empty($value['out_time'])){
                if($value['out_hour']<10){
                    $value['out_hour']="0".$value['out_hour'];
                }
                if($value['out_minute']<10){
                    $value['out_minute']="0".$value['out_minute'];
                }
                if($value['out_second']<10){
                    $value['out_second']="0".$value['out_second'];
                }
                $out_time = $value['out_hour'].":".$value['out_minute'].":".$value['out_second'];
                $out_time = strtotime($out_time);
                $s_out_time = strtotime($value['schedule_out_time']);
                $status_a = "Tepat Waktu";
                if($out_time<$s_out_time){
                    $status_a = "Pulang Cepat";
                }
            }
                $value['out_status']=$status_a;
            if($firts==true){
                $firts = false;
                $res[$i]['date']=$value['date'];
                $res[$i]['employee_count']=1;
                $res[$i]['detail'][$j]=$value;
            }else{
                if($res[$i]['detail'][$j]['date']==$value['date']){
                    $j+=1;
                    $res[$i]['detail'][$j]=$value;
                    $res[$i]['employee_count']+=1;
                }else{
                    $i+=1;
                    $j=0;
                    $res[$i]['employee_count']=1;
                    $res[$i]['date']=$value['date'];
                    $res[$i]['detail'][$j]=$value;
                }
            }
        }
        ?>
        <table border="1">
            <tr>
                <th>
                    Nama
                </th>
                <th>
                    Jam Masuk
                </th>
                <th>
                    Jam Masuk Seharusnya
                </th>
                <th>
                    Status
                </th>
                <th>
                    Foto Masuk
                </th>
                <th>
                    Jam Keluar
                </th>
                <th>
                    Jam Keluar Seharusnya
                </th>
                <th>
                    Status
                </th>
                <th>
                    Foto Keluar
                </th>
            </tr>
            <?php
                $i=1;
                foreach ($res[0]['detail'] as $value) {
            ?>
                <tr>
                    <td>
                        <?php echo $value['name'];?>
                    </td>
                    <td>
                        <?php echo $value['in_time'];?>
                    </td>
                    <td>
                        <?php echo $value['schedule_in_time'];?>
                    </td>
                    <td>
                        <?php echo $value['in_status'];?>
                    </td>
                    <td>
                        <img src="<?php echo $value['in_image'];?>" alt="<?php echo $value['in_image'];?>" width="100" >
                    </td>
                    <td>
                        <?php echo $value['out_time'];?>
                    </td>
                    <td>
                        <?php echo $value['schedule_out_time'];?>
                    </td>
                    <td>
                        <?php echo $value['out_status'];?>
                    </td>
                    <td>
                        <img src="<?php echo $value['out_image'];?>" alt="<?php echo $value['out_image'];?>" width="100" >
                    </td>
                </tr>                
            <?php
                }
            ?>
        </table>    
    <?php
    }
    ?>
  <br/>
  <br/>
</body>
</html>