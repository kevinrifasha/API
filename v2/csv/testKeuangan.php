<?php
require '../db_connection.php';
function rupiah($angka)
{
  $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
  return $hasil_rupiah;
}

$dateNow = date('d/M/Y');
// $dateNowDb = date('Y-m-d');
$dateNowDb = '2020-08-27';
// $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
// $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
// $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
// $dateLastDb = date('Y-m-t', strtotime('-1 month'));
$foodcourt = mysqli_query($db_conn, "SELECT id,name, phone,email,jam_buka,jam_tutup,tax,service FROM foodcourt WHERE email = 'nyonathan21@gmail.com'");
while ($rowfoodcourt = mysqli_fetch_assoc($foodcourt)) {
  $id= $row['id'];
  $name= $row['name'];
  $phone= $row['phone'];
  $email= $row['email'];
  $jamBuka= $row['jam_buka'];
  $jamTutup= $row['jam_tutup'];
  $tax= $row['tax'];
  $service= $row['service'];
}
$menu = mysqli_query($db_conn, "SELECT * FROM menu JOIN partner ON menu.id_partner=partner.id WHERE partner.id_foodcourt='$id';");
$transaksi = mysqli_query($db_conn, "SELECT total,promo,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi_foodcourt WHERE id_foodcourt='$id' AND transaksi_foodcourt.status <= 2 and transaksi_foodcourt.status>=1 AND DATE(created_at)='$dateNowDb' AND TIME(created_at) BETWEEN '$jamBuka' AND '$jamTutup'");
$rowTest = mysqli_fetch_row($transaksi);
if(implode(null,$rowTest) == null){
  echo ('no');
}else{
  echo ('yes');
  //$row has some value rather than null
}
$total = 0;
$promo = 0;
$ovo = 0;
$gopay = 0;
$dana = 0;
$linkaja = 0;
$tunaiDebit = 0;
$sakuku = 0;
$creditCard = 0;
$debitCard = 0;
$charge_ewallet = 0;
$taxtype = 0;
$sumCharge_ur = 0;

while ($row = mysqli_fetch_assoc($transaksi)) {
  // if($row['tipe_bayar']=='5'|| $row['tipe_bayar']=='7' || $row['tipe_bayar']==5 || $row['tipe_bayar']==7){
  //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)));
  //   $promo += $row['promo'];
  // }else{
  //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))-ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))+(ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))*($row['tax']/100))));
  // }
  // $promo += $row['promo'];

  $sumCharge_ur += $row['charge_ur'];
  $charge_ewallet = $row['charge_ewallet'];
  $taxtype = $row['tax'];
  $servicetype = $row['service'];

  $countService = 0;
  $withService = 0;
  $countTax = 0;
  $withTax = 0;
  if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $ovo += $withTax;
  } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax =ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $gopay += $withTax;
  } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $dana += $withTax;
  } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $linkaja += $withTax;
  } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $tunaiDebit += $withTax;
  } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $sakuku += $withTax;
  } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $creditCard += $withTax;
  } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
    $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
    $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
    $countTax = ceil($withService * ($row['tax'] / 100));
    $withTax = $withService + $countTax;
    $debitCard += $withTax;
  }
}
$hargaPokok = 0;
$menu2 = mysqli_query($db_conn, "SELECT * FROM menu JOIN partner ON menu.id_partner=partner.id WHERE partner.id_foodcourt='$id'");
while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
  $idMenuCheck = $rowcheck1['id'];

  $hargaPokokAwal = $rowcheck1['hpp'];
  $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM transaksi_detail_tenant join transaksi_tenant ON transaksi_detail_tenant.id_transaksi_tenant = transaksi_tenant.id JOIN transaksi_foodcourt ON transaksi_tenant.id_transaksi_fc=transaksi_foodcourt.id WHERE id_menu='$idMenuCheck' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status>=1 AND DATE(created_at)='$dateNowDb' AND TIME(created_at) BETWEEN '$jamBuka' AND '$jamTutup';");
  while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
    $qtyJual = $rowpph['qtytotal'];

  }
  $hargaPokok += $hargaPokokAwal * $qtyJual;
}
echo ($hargaPokok);
$subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) + $tunaiDebit + $creditCard + $debitCard) - $sumCharge_ur;


?>