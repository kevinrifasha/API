<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

//init var
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
$tokenizer = new Token();
$token = '';

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$value = array();
$vals = array();
$vals[0]['id'] = "";
$vals[0]['start'] = "";
$vals[0]['end'] = "";
$vals[0]['petty_cash'] = 0;
$vals[0]['pax'] = 0;
$vals[0]['name'] = array();
$vals[0]['cash_income'] = 0;
$vals[0]['delivery_fee'] = 0;
$vals[0]['dpTotal'] = 0;
$vals[0]['menus'] = array();
$vals[0]['payment_method_income'] = array();
$vals[0]['shift_transactions'] = array();
$value[0]['id'] = "";
$value[0]['start'] = "";
$value[0]['end'] = "";
$value[0]['petty_cash'] = 0;
$value[0]['pax'] = 0;
$value[0]['name'] = array();
$value[0]['cash_income'] = 0;
$value[0]['delivery_fee'] = 0;
$value[0]['dpTotal'] = 0;
$value[0]['menus'] = array();
$value[0]['payment_method_income'] = array();
$value[0]['shift_transactions'] = array();
$value['pax'] = 0;
$value['delivery_fee'] = 0;
$value['payment_method_income'][0]['payment_method'] = "";
$value['payment_method_income'][0]['point'] = 0;
$value['payment_method_income'][0]['promo'] = 0;
$value['payment_method_income'][0]['program_discount'] = 0;
$value['payment_method_income'][0]['diskon_spesial'] = 0;
$value['payment_method_income'][0]['employee_discount'] = 0;
$value['payment_method_income'][0]['delivery_fee'] = 0;
$value['payment_method_income'][0]['dpTotal'] = 0;
$value['payment_method_income'][0]['income'] = 0;


$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$type = 0;
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $q = mysqli_query($db_conn, "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id FROM shift s WHERE s.partner_id='$token->id_partner' AND s.end IS NULL AND s.deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $vals = array();
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res as $value) {
            $empID = explode(",", $value['employee_id']);
            $j = 0;
            foreach ($empID as $eID) {
                $qK = mysqli_query($db_conn, "SELECT nama as name FROM employees WHERE employees.id='$eID'");
                $resK = mysqli_fetch_all($qK, MYSQLI_ASSOC);
                $res[$i]['name'][$j] = $resK[0];
                $j += 1;
            }
            $i += 1;
        }
        $type = 1;
        foreach ($res as $value) {
            $sID = $value['id'];
            $value['cash_income'] = 0;
            $value['petty_cash'] = ceil($value['petty_cash']);
            if ($value['end'] == null) {
                $query3 = "SELECT SUM(transaksi.pax) AS pax, SUM(`transaksi`.program_discount) AS program_discount, SUM(`transaksi`.promo) AS promo, SUM(`transaksi`.diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(`transaksi`.total) AS total, SUM(`transaksi`.point) AS point, SUM( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 ) AS service, SUM( ( ( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 )+ `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point + `transaksi`.charge_ur )* `transaksi`.tax / 100 ) AS tax, SUM(transaksi.charge_ur) as charge_ur, payment_method.nama AS pmName, `transaksi`.tipe_bayar , IFNULL(SUM(delivery.ongkir),0) as delivery_fee, SUM(transaksi.dp_total) AS dp_total FROM shift s JOIN `transaksi` ON `transaksi`.shift_id = s.id JOIN payment_method ON `transaksi`.tipe_bayar = payment_method.id LEFT JOIN delivery ON `transaksi`.`id` = delivery.transaksi_id AND delivery.rate_id = 0 WHERE s.id = '$sID' AND `transaksi`.deleted_at IS NULL AND `transaksi`.status IN(1, 2,5) GROUP BY `transaksi`.tipe_bayar";
                $qPM = mysqli_query($db_conn, $query3);
                $query2 = "SELECT SUM(qty) AS qty, variant, nama, harga_satuan, id_menu FROM ( SELECT DISTINCT detail_transaksi.id as detail_id, menu.id as id_menu, CASE WHEN detail_transaksi.variant IS NOT NULL THEN detail_transaksi.variant ELSE NULL END as variant, menu.nama, SUM(detail_transaksi.qty) AS qty, detail_transaksi.harga_satuan FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id JOIN menu ON menu.id = detail_transaksi.id_menu WHERE transaksi.shift_id = '$sID' AND transaksi.status NOT IN(0, 3, 4, 5) AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL GROUP BY detail_transaksi.id_menu, detail_transaksi.variant ORDER BY `menu`.`nama` ASC ) AS tmp GROUP BY id_menu, variant ORDER BY nama";
                $getAR = mysqli_query($db_conn, "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, ar.transaction_id, ar.group_id FROM account_receivables ar JOIN employees e ON ar.created_by=e.id WHERE ar.shift_id='$sID' AND ar.deleted_at IS NULL AND ar.partner_id='$token->id_partner' AND ar.payment_method_id=0 AND ar.status=0 AND ar.paid_date IS NULL");
                if (mysqli_num_rows($getAR) > 0) {
                    $resAR = mysqli_fetch_all($getAR, MYSQLI_ASSOC);
                    $i = 0;
                    foreach ($resAR as $x) {
                        $arr[$i] = $x;
                        $j = 0;
                        if ($x['group_id'] == 0 || $x['group_id'] == "0") {
                            $transactionID = $x['transaction_id'];
                            $getTransactions = mysqli_query($db_conn, "SELECT t.total,
                      t.program_discount,
                      t.promo,
                      t.diskon_spesial,
                      t.employee_discount,
                      t.service,
                      t.tax,
                      t.charge_ur  FROM transaksi t WHERE t.id='$transactionID'");
                            $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                            foreach ($resTrx as $y) {
                                $subtotal = $y['total'] - $y['program_discount'] - $y['promo'] - $y['diskon_spesial'] - $y['employee_discount'];
                                $service = ceil($subtotal * $y['service'] / 100);
                                $serviceandCharge = $service + $y['charge_ur'];
                                $tax = ceil(($subtotal + $serviceandCharge) * $y['tax'] / 100);
                                $grandTotal = $subtotal + $serviceandCharge + $tax;
                                $arr[$i]['total'] = $grandTotal;
                                $value['arTotal'] += $arr[$i]['total'];
                                $j++;
                            }
                        } else {
                            $groupID = $x['group_id'];
                            $getTransactions = mysqli_query($db_conn, "SELECT SUM(t.total) AS total,
                      SUM(t.program_discount) AS program_discount,
                      SUM(t.promo) AS promo,
                      SUM(t.diskon_spesial) AS diskon_spesial,
                      SUM(t.employee_discount) AS employee_discount,
                      t.service,
                      t.tax,
                      SUM(t.charge_ur) AS charge_ur  FROM transaksi t WHERE t.group_id='$groupID'");
                            $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                            foreach ($resTrx as $y) {
                                $subtotal = $y['total'] - $y['program_discount'] - $y['promo'] - $y['diskon_spesial'] - $y['employee_discount'];
                                $service = ceil($subtotal * $y['service'] / 100);
                                $serviceandCharge = $service + $y['charge_ur'];
                                $tax = ceil(($subtotal + $serviceandCharge) * $y['tax'] / 100);
                                $grandTotal = $subtotal + $serviceandCharge + $tax;
                                $arr[$i]['total'] = $grandTotal;
                                $value['arTotal'] += $arr[$i]['total'];
                                $j++;
                            }
                        }
                        $i++;
                    }

                    $value['ar'] = $arr;
                } else {
                    $value['ar'] = [];
                    $value['arTotal'] = 0;
                }
                $qMS = mysqli_query($db_conn, $query2);
                $qST = mysqli_query($db_conn, "SELECT `id`, `type`, `amount`, `description` FROM `shift_transactions` WHERE `shift_id`='$sID' AND deleted_at IS NULL");
                $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
                $shiftTransactions = mysqli_fetch_all($qST, MYSQLI_ASSOC);

                $menuFetched = mysqli_fetch_all($qMS, MYSQLI_ASSOC);
                $menus = array();

                $i = 0;

                function str_replace_first($search, $replace, $subject)
                {
                    $search = '/' . preg_quote($search, '/') . '/';
                    return preg_replace($search, $replace, $subject, 1);
                }

                function str_replace_last($search, $replace, $subject)
                {
                    $pos = strrpos($search, $replace);
                    if ($pos === FALSE) return search;
                    return substr($search, 0, $pos) . $subject .
                        substr($search, $pos + strlen($replace));
                }

                foreach ($menuFetched as $val) {

                    $variantFetched = "";
                    if ($val["variant"] != "") {
                        $variantFetched = $val["variant"];
                        $variantFetched = str_replace_first('[', '{', $val["variant"]);
                        $variantFetched = str_replace_last($variantFetched, ']', '}');
                        $variantFetched = str_replace("'", '"', $variantFetched);
                        $variantFetched = json_decode($variantFetched);
                        $variantFetched = $variantFetched->variant;

                        $k = 0;
                        foreach ($variantFetched as $var) {
                            $variable = array();
                            $detail = $var->detail;
                            $variable = $detail[0];
                            // $newData["id"] = $variable["id"];
                            $variantFetched[$k] = $variable;

                            $k++;
                        }

                        $l = 0;
                        foreach ($variantFetched as $vars) {
                            $data = array();

                            $data["name"] = $vars->name;
                            $data["id"] = $vars->id;

                            $variantFetched[$l] = $data;

                            $l++;
                        }
                    }

                    $menus[$i] = $val;

                    if ($variantFetched != "") {
                        $menus[$i]["nama"] .= " (";
                        $n = 0;
                        foreach ($variantFetched as $var) {
                            $variant = $var["name"];
                            if ($n == 0) {
                                $menus[$i]["nama"] .= "$variant";
                            } else {
                                $menus[$i]["nama"] .= ", $variant";
                            }

                            $n++;
                        }
                        $menus[$i]["nama"] .= ")";
                    }
                    $menus[$i]["variant"] = $variantFetched;

                    $i++;
                }

                $result = array_reduce($menus, function ($carry, $item) {
                    if (!isset($carry[$item['nama']])) {
                        $carry[$item['nama']] = [
                            'nama' => $item['nama'],
                            'id_menu' => $item['id_menu'],
                            'harga_satuan' => $item['harga_satuan'],
                            'qty' => $item['qty']
                        ];
                    } else {
                        $carry[$item['nama']]['qty'] += $item['qty'];
                        $carry[$item['nama']]['qty'] = (string) $carry[$item['nama']]['qty'];
                    }

                    return $carry;
                });

                $value['menus'] = array_values($result);

                $i = 0;
                foreach ($paymentMethodIncome as $valuePMI) {
                    if ($valuePMI['pmName'] == "TUNAI") {
                        $value['cash_income'] += ceil($valuePMI['total']) - ceil($valuePMI['promo']) - ceil($valuePMI['program_discount']) - ceil($valuePMI['diskon_spesial']) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + round($valuePMI['service']) + round($valuePMI['tax']) + ceil($valuePMI['charge_ur']) - ceil($valuePMI['dp_total'])+(int)$valuePMI['rounding'];

                    }

                    // $value['pax'] += $valuePMI['pax'];
                    ($value['pax'] ?? $value['pax'] = 0) > 0 ? $value['pax'] += $valuePMI['pax'] : $value['pax'] = $valuePMI['pax'];

                    // $value['delivery_fee'] += ceil($valuePMI['delivery_fee']);
                    ($value['delivery_fee'] ?? $value['delivery_fee'] = 0) > 0 ? $value['delivery_fee'] += ceil($valuePMI['delivery_fee']) : $value['delivery_fee'] = ceil($valuePMI['delivery_fee']);

                    $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];

                    // $value['payment_method_income'][$i]['point'] += ceil($valuePMI['point']);
                    ($value['payment_method_income'][$i]['point'] ?? $value['payment_method_income'][$i]['point'] = 0) > 0 ? $value['payment_method_income'][$i]['point'] += ceil($valuePMI['point']) : $value['payment_method_income'][$i]['point'] = ceil($valuePMI['point']);

                    // $value['payment_method_income'][$i]['promo'] += ceil($valuePMI['promo']);
                    ($value['payment_method_income'][$i]['promo'] ?? $value['payment_method_income'][$i]['promo'] = 0) ? $value['payment_method_income'][$i]['promo'] += ceil($valuePMI['promo']) : $value['payment_method_income'][$i]['promo'] = ceil($valuePMI['promo']);

                    // $value['payment_method_income'][$i]['program_discount'] += ceil($valuePMI['program_discount']);
                    ($value['payment_method_income'][$i]['program_discount'] ?? $value['payment_method_income'][$i]['program_discount'] = 0) ? $value['payment_method_income'][$i]['program_discount'] += ceil($valuePMI['program_discount']) : $value['payment_method_income'][$i]['program_discount'] = ceil($valuePMI['program_discount']);

                    // $value['payment_method_income'][$i]['diskon_spesial'] += ceil($valuePMI['diskon_spesial']);
                    ($value['payment_method_income'][$i]['diskon_spesial'] ?? $value['payment_method_income'][$i]['diskon_spesial'] = 0) ? $value['payment_method_income'][$i]['diskon_spesial'] += ceil($valuePMI['diskon_spesial']) : $value['payment_method_income'][$i]['diskon_spesial'] = ceil($valuePMI['diskon_spesial']);

                    // $value['payment_method_income'][$i]['employee_discount'] += ceil($valuePMI['employee_discount']);
                    ($value['payment_method_income'][$i]['employee_discount'] ?? $value['payment_method_income'][$i]['employee_discount'] = 0) ? $value['payment_method_income'][$i]['employee_discount'] += ceil($valuePMI['employee_discount']) : $value['payment_method_income'][$i]['employee_discount'] = ceil($valuePMI['employee_discount']);

                    // $value['payment_method_income'][$i]['delivery_fee'] += ceil($valuePMI['delivery_fee']);
                    ($value['payment_method_income'][$i]['delivery_fee'] ?? $value['payment_method_income'][$i]['delivery_fee'] = 0) ? $value['payment_method_income'][$i]['delivery_fee'] += ceil($valuePMI['delivery_fee']) : $value['payment_method_income'][$i]['delivery_fee'] = ceil($valuePMI['delivery_fee']);

                    // $value['payment_method_income'][$i]['dpTotal'] += ceil($valuePMI['dp_total']);
                    ($value['payment_method_income'][$i]['dpTotal'] ?? $value['payment_method_income'][$i]['dpTotal'] = 0) ? $value['payment_method_income'][$i]['dpTotal'] += ceil($valuePMI['dp_total']) : $value['payment_method_income'][$i]['dpTotal'] = ceil($valuePMI['dp_total']);

                    // $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur'])+ceil($valuePMI['delivery_fee'])-ceil($valuePMI['dp_total']);
                    ($value['payment_method_income'][$i]['income'] ?? $value['payment_method_income'][$i]['income'] = 0) ? $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total']) - ceil($valuePMI['promo']) - ceil($valuePMI['program_discount']) - ceil($valuePMI['diskon_spesial']) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + round($valuePMI['service']) + round($valuePMI['tax']) + ceil($valuePMI['charge_ur']) + ceil($valuePMI['delivery_fee']) - ceil($valuePMI['dp_total'])+(int)$valuePMI['rounding'] : $value['payment_method_income'][$i]['income'] = ceil($valuePMI['total']) - ceil($valuePMI['promo']) - ceil($valuePMI['program_discount']) - ceil($valuePMI['diskon_spesial']) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + round($valuePMI['service']) + round($valuePMI['tax']) + ceil($valuePMI['charge_ur']) + ceil($valuePMI['delivery_fee']) - ceil($valuePMI['dp_total'])+(int)$valuePMI['rounding'];

                    $i += 1;
                }

                if ($i == 0) {
                    $value["payment_method_income"] = array();
                }
                $j = 0;
                foreach ($shiftTransactions as $valueST) {
                    $value['shift_transactions'][$j] = $valueST;
                    $value['shift_transactions'][$j]['amount'] =  $valueST['amount'];

                    $j += 1;
                }
                if ($j == 0) {
                    $value["shift_transaction"] = array();
                }
                $dp = mysqli_query($db_conn, "SELECT SUM(dp.amount) AS total, pm.nama AS name FROM down_payments dp JOIN payment_method pm ON pm.id=dp.payment_method_id WHERE dp.shift_id='$sID' AND dp.partner_id='$token->id_partner' AND dp.deleted_at IS NULL GROUP BY dp.payment_method_id");
                if (mysqli_num_rows($dp) > 0) {
                    $resDP = mysqli_fetch_all($dp, MYSQLI_ASSOC);
                    foreach ($resDP as $x) {
                        $value['dpTotal'] += $x['total'];
                    }
                    $value["dp"] = $resDP;
                } else {
                    $value["dp"] = [];
                    $value['dpTotal'] = 0;
                }
                $getVoid = mysqli_query($db_conn, "SELECT tc.id, CASE WHEN tc.detail_transaction_id =0 THEN (select SUM(qty) FROM detail_transaksi dt WHERE dt.id_transaksi= tc.transaction_id AND dt.deleted_at IS NULL) ELSE tc.qty END AS cancelled_qty, CASE WHEN tc.detail_transaction_id=0 THEN 'Transaksi' ELSE 'Item' END AS type FROM transaction_cancellation tc WHERE shift_id='$sID'");
                $resVoid = mysqli_fetch_all($getVoid, MYSQLI_ASSOC);
                $countTrx = 0;
                $cancelledItems = 0;
                foreach ($resVoid as $x) {
                    if ($x['type'] == "Transaksi") {
                        $countTrx++;
                    }
                    $cancelledItems += $x['cancelled_qty'];
                }
                $value['voidTrx'] = $countTrx;
                $value['voidItems'] = $cancelledItems;
            }
            array_push($vals, $value);
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "type"=>$type,"shifts"=>$vals]);

