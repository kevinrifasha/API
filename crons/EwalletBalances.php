<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

date_default_timezone_set('Asia/Jakarta');
$todayExact = date("Y-m-d H:i:s");
$today = date("Y-m-d");
$period = date("Y-m-d H:i:s", strtotime("-3 days"));

$getPartners = mysqli_query($db_conn, "SELECT id FROM partner WHERE status=1 AND deleted_at IS NULL AND is_testing=0");
$getSettings = mysqli_query($db_conn, "SELECT name, value FROM settings WHERE id IN(24,25,26)");
while ($settings = mysqli_fetch_assoc($getSettings)) {
  if ($settings['name'] == "mdr_xendit") {
    $mdrXendit = $settings['value'];
  } else if ($settings['name'] == "mdr_midtrans") {
    $mdrMidtrans = $settings['value'];
  } else if ($settings['name'] == "ppn_mdr") {
    $ppn = $settings['value'];
  }
}

while ($partners = mysqli_fetch_assoc($getPartners)) {
  $partnerID = $partners['id'];
  //pemasukan dari jual beli
  $qt = "SELECT
  t.id,
  t.jam,
  t.total,
  t.program_discount,
  t.promo,
  t.diskon_spesial,
  t.employee_discount,
  t.service,
  t.tax,
  t.charge_ur,
  t.tipe_bayar,
  pm.nama AS paymentMethod
FROM
  transaksi t
  JOIN payment_method pm ON t.tipe_bayar = pm.id
WHERE
  t.id_partner = '$partnerID'
  AND t.tipe_bayar IN (1, 2, 3, 4, 10, 14)
  AND t.status = 2
  AND t.deleted_at IS NULL
  AND jam BETWEEN'2022-11-01 00:00:00'AND'$period'
  AND NOT EXISTS (
    SELECT
      NULL
    FROM
      ewallet_balances t1
    WHERE
      t1.reference_id = t.id
  )
ORDER BY
  t.jam DESC
";
  $getTransactions = mysqli_query($db_conn, $qt);

  while ($trx = mysqli_fetch_assoc($getTransactions)) {
    $trxID = $trx['id'];
    $subtotal = $trx['total'] - $trx['program_discount'] - $trx['promo'] - $trx['diskon_spesial'] - $trx['employee_discount'];
    $service = ceil($subtotal * $trx['service'] / 100);
    $serviceandCharge = $service + $trx['charge_ur'];
    $tax = ceil(($subtotal + $serviceandCharge) * $trx['tax'] / 100);
    $grandTotal = $subtotal + $serviceandCharge + $tax;
    if ($trx['tipe_bayar'] == 2 || $trx['tipe_bayar'] == "2") {
      $tipe = "Ini Midtrans";
      $mdrValue = ceil($grandTotal * $mdrMidtrans / 100);
      $ppnValue = ceil($mdrValue * $ppn / 100);
      $mdr = $mdrMidtrans;
    } else if ($trx['tipe_bayar'] == 14 || $trx['tipe_bayar'] == "14") {
      $tipe = "Ini QRIS";
      $mdrValue = ceil($grandTotal * 0.7 / 100);
      $ppnValue = 0;
      $mdr = "0.7";
    } else {
      $tipe = "Ini jendit";
      $mdrValue = ceil($grandTotal * $mdrXendit / 100);
      $ppnValue = ceil($mdrValue * $ppn / 100);
      $mdr = $mdrXendit;
    }
    // echo $trx['id']." ".$trx['paymentMethod']." ".$grandTotal."\n";
    $insertQuery = "INSERT INTO ewallet_balances SET reference_id='$trxID', partner_id='$partnerID',";
    $cb = mysqli_query($db_conn, "SELECT balance FROM ewallet_balances WHERE partner_id='$partnerID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    $res = mysqli_fetch_all($cb, MYSQLI_ASSOC);
    $currentBalance = $res[0]['balance'];
    $title = "Hasil Penjualan";
    $description = "Hasil penjualan (transaksi " . $trxID . ") pada " . $trx['jam'];
    $balance = $currentBalance + $grandTotal;

    $insertIncome = mysqli_query($db_conn, $insertQuery . "type='Debit', amount='$grandTotal', balance='$balance', title='$title', description='$description'");

    $balance = $balance - $mdrValue;
    $title = "Biaya MDR Ewallet";
    $description = "Pemotongan Biaya MDR " . $trx['paymentMethod'] . " " . $mdr . "%" . " (" . $trxID . ")";

    $insertMDR = mysqli_query($db_conn, $insertQuery . "type='Credit', amount='$mdrValue', balance='$balance', title='$title', description='$description'");

    $balance = $balance - $ppnValue;
    $title = "PPn MDR Ewallet";
    $description = "Pemotongan PPn " . $trx['paymentMethod'] . " sebesar " . $ppn . "%" . " (" . $trxID . ")";

    $insertPPN = mysqli_query($db_conn, $insertQuery . "type='Credit', amount='$ppnValue', balance='$balance', title='$title', description='$description'");
    if ($insertIncome && $insertMDR && $insertPPN) {
      echo "Berhasil";
    } else {
      echo "gagal";
    }
    $balance = 0;
  }
  //pemasukan dari reservasi
  $getReservations = mysqli_query($db_conn, "SELECT r.id, r.booking_price, r.name, r.phone, r.paid_date, r.reference_id, r.payment_method, r.payment_channel FROM reservations r WHERE r.deleted_at IS NULL AND r.partner_id='$partnerID' AND r.status!='Pending' AND r.paid_date IS NOT NULL AND r.payment_method IS NOT NULL AND r.payment_channel IS NOT NULL AND r.reference_id IS NOT NULL AND r.paid_date BETWEEN '2022-09-01 00:00:00' AND '$period' AND NOT EXISTS (
      SELECT
        NULL
      FROM
        ewallet_balances t1
      WHERE
        t1.reference_id = r.reference_id
    ) ORDER BY r.paid_date DESC");
  while ($rsv = mysqli_fetch_assoc($getReservations)) {
    $grandTotal = $rsv['booking_price'];
    $paymentChannel = $rsv['payment_channel'];
    $paymentMethod = $rsv['payment_method'];
    $referenceID = $rsv['reference_id'];
    if ($paymentMethod == "EWALLET") {
      $mdrValue = ceil($grandTotal * $mdrXendit / 100);
      $ppnValue = ceil($mdrValue * $ppn / 100);
      $mdr = $mdrXendit . "%";
    } else if ($paymentMethod == "BANK_TRANSFER") {
      $mdrValue = 4500;
      $ppnValue = ceil($mdrValue * $ppn / 100);
      $mdr = 4500;
    } else if ($paymentMethod == "CREDIT_CARD") {
      $mdrValue = ceil($grandTotal * 2.9 / 100) + 2000;
      $ppnValue = ceil($mdrValue * $ppn / 100);
      $mdr = ceil($grandTotal * 2.9 / 100) + 2000;
    }
    $insertQuery = "INSERT INTO ewallet_balances SET reference_id='$referenceID', partner_id='$partnerID',";
    $cb = mysqli_query($db_conn, "SELECT balance FROM ewallet_balances WHERE partner_id='$partnerID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    $res = mysqli_fetch_all($cb, MYSQLI_ASSOC);
    $currentBalance = $res[0]['balance'];
    $title = "Pembayaran Reservasi";
    $description = "Reservasi (nomor " . $referenceID . ") pada " . $rsv['paid_date'];
    $balance = $currentBalance + $grandTotal;

    $insertIncome = mysqli_query($db_conn, $insertQuery . "type='Debit', amount='$grandTotal', balance='$balance', title='$title', description='$description'");

    $balance = $balance - $mdrValue;
    $title = "Biaya MDR";
    $description = "Pemotongan Biaya MDR " . $paymentMethod . " sebesar " . $mdr . " (" . $referenceID . ")";

    $insertMDR = mysqli_query($db_conn, $insertQuery . "type='Credit', amount='$mdrValue', balance='$balance', title='$title', description='$description'");

    $balance = $balance - $ppnValue;
    $title = "PPn MDR";
    $description = "Pemotongan PPn " . $paymentMethod . " sebesar " . $ppn . "%" . " (" . $referenceID . ")";

    $insertPPN = mysqli_query($db_conn, $insertQuery . "type='Credit', amount='$ppnValue', balance='$balance', title='$title', description='$description'");
    if ($insertIncome && $insertMDR && $insertPPN) {
      echo "rsv Berhasil";
    } else {
      echo "gagal";
    }
    $balance = 0;
  }
}
