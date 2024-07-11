<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("./../paymentModels/paymentManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
ini_set('memory_limit', '256M');
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
  if (preg_match($rx_http, $key)) {
    $arh_key = preg_replace($rx_http, '', $key);
    $rx_matches = array();
    // do some nasty string manipulations to restore the original letter case
    // this should work in most cases
    $rx_matches = explode('_', $arh_key);
    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
      foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
      $arh_key = implode('-', $rx_matches);
    }
    $headers[$arh_key] = $val;
  }
}
$token = '';

foreach ($headers as $header => $value) {
  if ($header == "Authorization" || $header == "AUTHORIZATION") {
    $token = substr($value, 7);
  }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success = 0;
$msg = 'Failed';
$all = "0";
$query = "";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

  $status = $tokens['status'];
  $msg = $tokens['msg'];
  $success = 0;
} else {
  $arr = [];
  $i = 0;
  $id = $_GET['id'];
  $dateTo = $_GET['dateTo'];
  $dateFrom = $_GET['dateFrom'];

  $newDateFormat = 0;

  if (strlen($dateTo) !== 10 && strlen($dateFrom) !== 10) {
    $dateTo = str_replace("%20", " ", $dateTo);
    $dateFrom = str_replace("%20", " ", $dateFrom);
    $newDateFormat = 1;
  }

  $total = 0;
  $totalS = 0;
  if ($newDateFormat == 1) {
    if (isset($_GET['all'])) {
      $all = $_GET['all'];
    }
    if ($all !== "1") {
      $query = "SELECT
                pm.nama as pmName,
                detail_transaksi.id AS id,
                detail_transaksi.id_transaksi,
                detail_transaksi.id_menu,
                detail_transaksi.server_id,
                detail_transaksi.harga_satuan,
                detail_transaksi.bundle_id,
                detail_transaksi.qty,
                detail_transaksi.notes,
                detail_transaksi.harga,
                detail_transaksi.variant,
                detail_transaksi.status AS detail_status,
                categories.is_consignment,
                transaksi.jam,
                transaksi.no_meja,
                transaksi.paid_date,
                transaksi.status,
                transaksi.tax,
                transaksi.promo,
                transaksi.id_voucher,
                transaksi.diskon_spesial,
                transaksi.employee_discount AS diskon_karyawan,
                transaksi.program_discount AS diskon_program,
                transaksi.service,
                transaksi.rounding,
                transaksi.tipe_bayar,
                transaksi.customer_name,
                transaksi.total AS total_transaksi,
                IFNULL(tc.notes,'') AS refundNotes,
                menu.nama AS menu,
                menu.id_category AS id_category,
                menu.sku AS sku,
                categories.name AS category,
                e.nama AS served_by,
                tc.qty AS previous_qty,
                dp.amount AS dp
              FROM
                detail_transaksi
                LEFT JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id
                LEFT JOIN menu ON menu.id = detail_transaksi.id_menu
                LEFT JOIN categories ON categories.id = menu.id_category
                LEFT JOIN down_payments dp ON dp.transaction_id = transaksi.id
                LEFT JOIN employees e ON e.id = detail_transaksi.server_id
                LEFT JOIN payment_method pm ON transaksi.tipe_bayar = pm.id
                LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id = detail_transaksi.id
              WHERE
                transaksi.id_partner = '$id'
                AND transaksi.deleted_at IS NULL
                AND transaksi.status IN(1, 2, 3, 4)
                AND transaksi.paid_date BETWEEN '$dateFrom'
                AND '$dateTo'
              ORDER BY
                id DESC";
    } else {
      $query = "SELECT
                pm.nama as pmName,
                detail_transaksi.id AS id,
                detail_transaksi.id_transaksi,
                detail_transaksi.id_menu,
                detail_transaksi.server_id,
                detail_transaksi.harga_satuan,
                detail_transaksi.bundle_id,
                detail_transaksi.qty,
                detail_transaksi.notes,
                detail_transaksi.harga,
                detail_transaksi.variant,
                detail_transaksi.status AS detail_status,
                categories.is_consignment,
                transaksi.jam,
                transaksi.no_meja,
                transaksi.paid_date,
                transaksi.status,
                transaksi.tax,
                transaksi.promo,
                transaksi.id_voucher,
                transaksi.diskon_spesial,
                transaksi.employee_discount AS diskon_karyawan,
                transaksi.program_discount AS diskon_program,
                transaksi.service,
                transaksi.rounding,
                transaksi.tipe_bayar,
                transaksi.customer_name,
                transaksi.total AS total_transaksi,
                IFNULL(tc.notes,'') AS refundNotes,
                menu.nama AS menu,
                menu.id_category AS id_category,
                menu.sku AS sku,
                categories.name AS category,
                e.nama AS served_by,
                tc.qty AS previous_qty,
                dp.amount AS dp
              FROM
                detail_transaksi
                LEFT JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id
                LEFT JOIN menu ON menu.id = detail_transaksi.id_menu
                LEFT JOIN categories ON categories.id = menu.id_category
                LEFT JOIN partner p ON p.id = transaksi.id_partner
                LEFT JOIN employees e ON e.id = detail_transaksi.server_id
                LEFT JOIN down_payments dp ON dp.transaction_id = transaksi.id
                LEFT JOIN payment_method pm ON transaksi.tipe_bayar = pm.id
                LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id = detail_transaksi.id
              WHERE
                p.id_master = '$idMaster'
                AND transaksi.deleted_at IS NULL
                AND transaksi.status IN(1, 2, 3, 4)
                AND transaksi.paid_date BETWEEN '$dateFrom'
                AND '$dateTo'
              ORDER BY
                id DESC";
    }

    $sqlGetSales = mysqli_query($db_conn, $query);
    $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
    $rows = count($data);

    if ($rows > 0) {
    //   $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);

    //   $n = 1;
    //   foreach ($data as $row) {
    //     $id = $row['id'];
    //     $id_transaksi = $row['id_transaksi'];
    //     $id_menu = $row['id_menu'];
    //     $server_id = $row['server_id'];
    //     $harga_satuan = $row['harga_satuan'];
    //     $qty = $row['qty'];
    //     $harga = $row['harga'];
    //     $notes = $row['notes'];
    //     $bundle_id = $row['bundle_id'];
    //     $variant = $row['variant'];
    //     $rounding = $row['rounding'];
    //     $status = $row['status'];
    //     $jam = $row['jam'];
    //     $no_meja = $row['no_meja'];
    //     $paid_date = $row['paid_date'];
    //     $tax = $row['tax'];
    //     $promo = $row['promo'];
    //     $id_voucher = $row['id_voucher'];
    //     $diskon_spesial = $row['diskon_spesial'];
    //     $diskon_karyawan = $row['employee_discount'];
    //     $diskon_program = $row['program_discount'];
    //     $service = $row['service'];
    //     $tipe_bayar = $row['tipe_bayar'];
    //     $customer_name = $row['customer_name'];
    //     $served_by = $row['serverName'];
    //     $menu = $row['menu'];
    //     $category = $row['category'];
    //     $sku = $row['sku'];
    //     $metode_bayar = $row['pmName'];
    //     $refundNotes = $row['refundNotes'];
    //     $detail_status = $row['detail_status'];
    //     $previous_qty = $row['previous_qty'];
    //     $total_transaksi = $row['total_transaksi'];
    //     $is_consignment = $row['is_consignment'];
    //     $dp = $row['dpMenu'];

    //     array_push($arr, array("no" => "$n", "id" => "$id", "id_transaksi" => "$id_transaksi", "id_menu" => "$id_menu", "menu" => "$menu", "server_id" => "$server_id", "category" => "$category", "harga_satuan" => "$harga_satuan", "qty" => "$qty", "harga" => "$harga", "notes" => "$notes", "variant" => "$variant", "status" => "$status", "date" => "$jam", "no_meja" => "$no_meja", "paid_date" => "$paid_date", "tax" => "$tax", "promo" => "$promo", "id_voucher" => "$id_voucher", "diskon_spesial" => "$diskon_spesial", "diskon_karyawan" => "$diskon_karyawan", "bundle_id"=>$bundle_id,"service" => "$service", "tipe_bayar" => "$tipe_bayar", "customer_name" => "$customer_name", "served_by" => "$served_by", "sku" => "$sku", "metode_bayar" => "$metode_bayar", "diskon_program" => $diskon_program, "refundNotes" => "$refundNotes", "detail_status" => $detail_status, "previous_qty" => $previous_qty, "total_transaksi" => $total_transaksi, "is_consignment" => "$is_consignment", "dp" => $dp, "rounding"=>$rounding)); 
    //     $n += 1;
    //   }

      $success = 1;
      $status = 200;
      $msg = "Success";
    } else {
      $success = 0;
      $status = 204;
      $msg = "Data not found!";
    }
  } else {
    if (isset($_GET['all'])) {
      $all = $_GET['all'];
    }
    if ($all !== "1") {
      $query = "SELECT
                pm.nama as pmName,
                SUM(detail_transaksi.harga_satuan * detail_transaksi.qty) AS all_total,
                detail_transaksi.id AS id,
                detail_transaksi.id_transaksi,
                detail_transaksi.id_menu,
                detail_transaksi.server_id,
                detail_transaksi.harga_satuan,
                detail_transaksi.bundle_id,
                detail_transaksi.qty,
                detail_transaksi.notes,
                detail_transaksi.harga,
                detail_transaksi.variant,
                detail_transaksi.status AS detail_status,
                transaksi.jam,
                transaksi.no_meja,
                transaksi.paid_date,
                transaksi.status,
                transaksi.tax,
                transaksi.promo,
                transaksi.id_voucher,
                transaksi.diskon_spesial,
                transaksi.employee_discount,
                transaksi.program_discount,
                transaksi.service,
                transaksi.tipe_bayar,
                transaksi.customer_name,
                IFNULL(tc.notes,'') AS refundNotes,
                menu.nama AS menu,
                menu.id_category AS id_category,
                menu.sku AS sku,
                categories.name AS category,
                e.nama AS serverName
              FROM
                detail_transaksi
                JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id
                JOIN menu ON menu.id = detail_transaksi.id_menu
                JOIN categories ON categories.id = menu.id_category
                LEFT JOIN employees e ON e.id = detail_transaksi.server_id
                LEFT JOIN payment_method pm ON transaksi.tipe_bayar = pm.id
                LEFT JOIN transaction_cancellation tc ON tc.transaction_id = detail_transaksi.id_transaksi
              WHERE
                transaksi.id_partner = '$id'
                AND detail_transaksi.deleted_at IS NULL
                AND transaksi.deleted_at IS NULL
                AND transaksi.status IN(1, 2, 3, 4)
                AND DATE(transaksi.paid_date) BETWEEN '$dateFrom'
                AND '$dateTo'
              ORDER BY
                id DESC";
    } else {
      $query = "SELECT
                pm.nama as pmName,
                SUM(detail_transaksi.harga_satuan * detail_transaksi.qty) AS all_total,
                detail_transaksi.id AS id,
                detail_transaksi.id_transaksi,
                detail_transaksi.id_menu,
                detail_transaksi.server_id,
                detail_transaksi.harga_satuan,
                detail_transaksi.bundle_id,
                detail_transaksi.qty,
                detail_transaksi.notes,
                detail_transaksi.harga,
                detail_transaksi.variant,
                detail_transaksi.status AS detail_status,
                transaksi.jam,
                transaksi.no_meja,
                transaksi.paid_date,
                transaksi.status,
                transaksi.tax,
                transaksi.promo,
                transaksi.id_voucher,
                transaksi.diskon_spesial,
                transaksi.employee_discount,
                transaksi.program_discount,
                transaksi.service,
                transaksi.tipe_bayar,
                transaksi.customer_name,
                IFNULL(tc.notes,'') AS refundNotes,
                menu.nama AS menu,
                menu.id_category AS id_category,
                menu.sku AS sku,
                categories.name AS category,
                e.nama AS serverName
              FROM
                detail_transaksi
                JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id
                JOIN menu ON menu.id = detail_transaksi.id_menu
                JOIN categories ON categories.id = menu.id_category
                JOIN partner p ON p.id = transaksi.id_partner
                LEFT JOIN employees e ON e.id = detail_transaksi.server_id
                LEFT JOIN payment_method pm ON transaksi.tipe_bayar = pm.id
                LEFT JOIN transaction_cancellation tc ON tc.transaction_id = detail_transaksi.id_transaksi
              WHERE
                p.id_master = '$idMaster'
                AND detail_transaksi.deleted_at IS NULL
                AND transaksi.deleted_at IS NULL
                AND transaksi.status IN(1, 2, 3, 4)
                AND DATE(transaksi.paid_date) BETWEEN '$dateFrom'
                AND '$dateTo'
              ORDER BY
                id DESC";
    }

    $sqlGetSales = mysqli_query($db_conn, $query);

    $rows = mysqli_num_rows($sqlGetSales);

    if ($rows > 0) {
      $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);

      $n = 1;
      foreach ($data as $row) {
        $id = $row['id'];
        $id_transaksi = $row['id_transaksi'];
        $id_menu = $row['id_menu'];
        $server_id = $row['server_id'];
        $harga_satuan = $row['harga_satuan'];
        $qty = $row['qty'];
        $harga = $row['harga'];
        $notes = $row['notes'];
        $variant = $row['variant'];
        $status = $row['status'];
        $jam = $row['jam'];
        $no_meja = $row['no_meja'];
        $paid_date = $row['paid_date'];
        $tax = $row['tax'];
        $promo = $row['promo'];
        $id_voucher = $row['id_voucher'];
        $diskon_spesial = $row['diskon_spesial'];
        $diskon_karyawan = $row['employee_discount'];
        $diskon_program = $row['program_discount'];
        $service = $row['service'];
        $tipe_bayar = $row['tipe_bayar'];
        $customer_name = $row['customer_name'];
        $served_by = $row['serverName'];
        $menu = $row['menu'];
        $all_total = $row['all_total'];
        $bundle_id = $row['bundle_id'];
        $category = $row['category'];
        $sku = $row['sku'];
        $metode_bayar = $row['pmName'];
        $refundNotes = $row['refundNotes'];
        $detail_status = $row['detail_status'];
        array_push($arr, array("no" => "$n", "id" => "$id", "id_transaksi" => "$id_transaksi", "id_menu" => "$id_menu", "menu" => "$menu", "server_id" => "$server_id", "category" => "$category", "harga_satuan" => "$harga_satuan", "qty" => "$qty", "harga" => "$harga", "notes" => "$notes", "variant" => "$variant", "status" => "$status", "date" => "$jam", "no_meja" => "$no_meja", "paid_date" => "$paid_date", "tax" => "$tax", "promo" => "$promo", "id_voucher" => "$id_voucher", "diskon_spesial" => "$diskon_spesial", "diskon_karyawan" => "$diskon_karyawan", "service" => "$service", "tipe_bayar" => "$tipe_bayar", "customer_name" => "$customer_name", "served_by" => "$served_by", "sku" => "$sku", "metode_bayar" => "$metode_bayar", "diskon_program" => $diskon_program, "refundNotes" => "$refundNotes", "all_total" => "$all_total", "detail_status" => "$detail_status", "bundle_id"=>$bundle_id));

        $n += 1;
      }

      $success = 1;
      $status = 200;
      $msg = "Success";
    } else {
      $success = 0;
      $status = 204;
      $msg = "Data not found!";
    }
  }
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "menuSales" => $data, "rows" => $rows]);
