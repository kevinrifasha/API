<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
//init var
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$tokenizer = new Token();
$token = '';
$res = array();
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$value = array();
$success=0;
$msg = 'Failed';

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
// if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
//     $status = $tokenValidate['status'];
//     $msg = $tokenValidate['msg']; 
//     $success = 0; 
// }else{
    $id = $token->id_partner;
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res=array();
    $status = 200;
    $success=1;
    $msg="Success";

    $query = "SELECT SUM(count) count, hour FROM (SELECT COUNT(transaksi.id) AS count,HOUR(transaksi.jam) AS hour 
    FROM transaksi 
    WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.status<=2 AND transaksi.status>=1 GROUP BY hour ";
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT COUNT(`$transactions`.id) AS count,HOUR(`$transactions`.jam) AS hour 
            FROM `$transactions` 
            WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.status<=2 AND `$transactions`.status>=1 GROUP BY hour  ";
        }
    }
    $query .= " ) as tmp GROUP BY hour ";
    $hourlyTransaction = mysqli_query($db_conn, $query);

  if (mysqli_num_rows($hourlyTransaction) > 0) {
    $ht = mysqli_fetch_all($hourlyTransaction, MYSQLI_ASSOC);
    $res['hourlyTransaction']=$ht;
  } else {
    $res['hourlyTransaction']=[];
  }

    // $paymentMethodCount = mysqli_query($db_conn, "SELECT payment_method.nama,transaksi.tipe_bayar AS payment_id, COUNT(*) AS count FROM `transaksi` 
    // JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE transaksi.id_partner='000035' 
    // AND DATE(transaksi.jam) BETWEEN '2020-01-01' AND '2021-03-01' AND NOT transaksi.tipe_bayar=0 AND transaksi.status<=2 and transaksi.status>=1 GROUP BY transaksi.tipe_bayar");

    // if (mysqli_num_rows($paymentMethodCount) > 0) {
    //     $pmc = mysqli_fetch_all($paymentMethodCount, MYSQLI_ASSOC);
    //     $res['paymentMethodCount']=$pmc;
    // } else {
    //     $res['paymentMethodCount']=[];
    // }

    $query = "SELECT SUM(Total) Total, SUM(OvoCount) OvoCount, SUM(GopayCount) GopayCount, SUM(DanaCount) DanaCount, SUM(LinkAjaCount) LinkAjaCount, SUM(TunaiCount) TunaiCount, SUM(SakukuCount) SakukuCount, SUM(CreditCount) CreditCount, SUM(DebitCount) DebitCount, SUM(QrisCount) QrisCount, SUM(ShopeeCount) ShopeeCount FROM ( SELECT DISTINCT 
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS Total,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=1 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS OvoCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=2 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS GopayCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=3 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS DanaCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=4 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS LinkAjaCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=5 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS TunaiCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=6 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS SakukuCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=7 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS CreditCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=8 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS DebitCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=9 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS QrisCount,
    (SELECT DISTINCT COUNT(*) FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tipe_bayar=10 AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS ShopeeCount
    FROM transaksi LEFT JOIN users ON users.phone=transaksi.phone  AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT DISTINCT 
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS Total,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=1 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS OvoCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=2 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS GopayCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=3 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS DanaCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=4 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS LinkAjaCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=5 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS TunaiCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=6 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS SakukuCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=7 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS CreditCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=8 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS DebitCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=9 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS QrisCount,
            (SELECT DISTINCT COUNT(*) FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.tipe_bayar=10 AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS ShopeeCount
            FROM `$transactions` LEFT JOIN users ON users.phone=`$transactions`.phone  AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        }
    }
    $query .= " ) as tmp ";
    $transaksi = mysqli_query($db_conn, $query);

    $values = array();

    while ($row = mysqli_fetch_assoc($transaksi)) {
        $total = $row['Total'];
        $ovo = $row['OvoCount'];
        $gopay = $row['GopayCount'];
        $dana = $row['DanaCount'];
        $linkaja = $row['LinkAjaCount'];
        $tunai = $row['TunaiCount'];
        $sakuku = $row['SakukuCount'];
        $credit = $row['CreditCount'];
        $debit = $row['DebitCount'];
        $qris = $row['QrisCount'];
    }

    $pOvo =(round((float)($ovo/$total) * 100 ));
    if(is_nan($pOvo)){
      $pOvo=0;
    }
    $pGopay =(round((float)($gopay/$total) * 100 ));
    if(is_nan($pGopay)){
      $pGopay=0;
    }
    $pDana =(round((float)($dana/$total) * 100 ));
    if(is_nan($pDana)){
      $pDana=0;
    }
    $pLinkaja =(round((float)($linkaja/$total) * 100 ));
    if(is_nan($pLinkaja)){
      $pLinkaja=0;
    }
    $pTunai =(round((float)($tunai/$total) * 100 ));
    if(is_nan($pTunai)){
      $pTunai=0;
    }
    $pSakuku =(round((float)($sakuku/$total) * 100 ));
    if(is_nan($pSakuku)){
      $pSakuku=0;
    }
    $pCredit =(round((float)($credit/$total) * 100 ));
    if(is_nan($pCredit)){
      $pCredit=0;
    }
    $pDebit =(round((float)($debit/$total) * 100 ));
    if(is_nan($pDebit)){
      $pDebit=0;
    }
    $pQris =(round((float)($qris/$total) * 100 ));
    if(is_nan($pQris)){
      $pQris=0;
    }
    array_push($values, array("label" => 'OVO', "value" => $pOvo));
    // array_push($values, array("label" => 'Gopay', "value" => $pGopay));
    array_push($values, array("label" => 'Dana', "value" => $pDana));
    array_push($values, array("label" => 'LinkAja', "value" => $pLinkaja));
    array_push($values, array("label" => 'Tunai', "value" => $pTunai));
    // array_push($values, array("label" => 'Sakuku', "value" => $pSakuku));
    array_push($values, array("label" => 'Kartu Kredit', "value" => $pCredit));
    array_push($values, array("label" => 'Kartu Debit', "value" => $pDebit));
    array_push($values, array("label" => 'QRIS', "value" => $pQris));
    $res['paymentMethodPercentage']=$values;

    $query = "SELECT SUM(count) count, category FROM ( SELECT COUNT(DISTINCT users.phone) AS count,'11-20' as category
    from users join transaksi ON users.phone=transaksi.phone 
    where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 AND 20) AND transaksi.id_partner ='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
    UNION ALL
    SELECT COUNT(DISTINCT users.phone) AS count,'21-30' as category
    from users join transaksi ON users.phone=transaksi.phone 
    where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 AND 30) AND transaksi.id_partner ='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
    UNION ALL
    SELECT COUNT(DISTINCT users.phone) AS count,'31-40' as category
    from users join transaksi ON users.phone=transaksi.phone 
    where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 AND 40) AND transaksi.id_partner ='$id'AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
    UNION ALL
    SELECT COUNT(DISTINCT users.phone) AS count,'41-50' as category
    from users join transaksi ON users.phone=transaksi.phone 
    where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 AND 50) AND transaksi.id_partner ='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
    UNION ALL
    SELECT COUNT(DISTINCT users.phone) AS count,'51+' as category
    from users join transaksi ON users.phone=transaksi.phone 
    where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) >51) AND transaksi.id_partner ='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT COUNT(DISTINCT users.phone) AS count,'11-20' as category
            from users join `$transactions` ON users.phone=`$transactions`.phone 
            where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 AND 20) AND `$transactions`.id_partner ='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
            UNION ALL
            SELECT COUNT(DISTINCT users.phone) AS count,'21-30' as category
            from users join `$transactions` ON users.phone=`$transactions`.phone 
            where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 AND 30) AND `$transactions`.id_partner ='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
            UNION ALL
            SELECT COUNT(DISTINCT users.phone) AS count,'31-40' as category
            from users join `$transactions` ON users.phone=`$transactions`.phone 
            where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 AND 40) AND `$transactions`.id_partner ='$id'AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
            UNION ALL
            SELECT COUNT(DISTINCT users.phone) AS count,'41-50' as category
            from users join `$transactions` ON users.phone=`$transactions`.phone 
            where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 AND 50) AND `$transactions`.id_partner ='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
            UNION ALL
            SELECT COUNT(DISTINCT users.phone) AS count,'51+' as category
            from users join `$transactions` ON users.phone=`$transactions`.phone 
            where (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) >51) AND `$transactions`.id_partner ='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        }
    }
    $query .= " ) AS tmp GROUP BY category";
    $transaksi = mysqli_query($db_conn, $query);
    $values = array();

    while ($row = mysqli_fetch_assoc($transaksi)) {
        array_push($values, array("category" => $row['category'], "value" => $row['count']));
    }
    $res['ageTransactionCount']=$values;
    
    $query = "SELECT SUM(Total) Total, SUM(PriaCount) PriaCount, SUM(WanitaCount) WanitaCount, SUM(Unassign) Unassign FROM( SELECT DISTINCT 
    (SELECT DISTINCT COUNT(*) FROM users JOIN transaksi ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS Total,
    (SELECT DISTINCT COUNT(*) FROM users JOIN transaksi ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND users.Gender = 'Pria' AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS PriaCount,
    (SELECT DISTINCT COUNT(*) FROM users JOIN transaksi ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND users.Gender = 'Wanita' AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS WanitaCount,
    (SELECT DISTINCT COUNT(*) FROM users JOIN transaksi ON users.phone=transaksi.phone  WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND (users.Gender = '' || users.Gender IS NULL) AND transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1) AS Unassign
    FROM users JOIN transaksi ON users.phone=transaksi.phone  AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT DISTINCT 
            (SELECT DISTINCT COUNT(*) FROM users JOIN `$transactions` ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS Total,
            (SELECT DISTINCT COUNT(*) FROM users JOIN `$transactions` ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND users.Gender = 'Pria' AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS PriaCount,
            (SELECT DISTINCT COUNT(*) FROM users JOIN `$transactions` ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND users.Gender = 'Wanita' AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS WanitaCount,
            (SELECT DISTINCT COUNT(*) FROM users JOIN `$transactions` ON users.phone=`$transactions`.phone  WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND (users.Gender = '' || users.Gender IS NULL) AND `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1) AS Unassign
            FROM users JOIN `$transactions` ON users.phone=`$transactions`.phone  AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  ";
        }
    }
    $query .= " ) AS tmp";
    $transaksi = mysqli_query($db_conn, $query);
    $values = array();

    while ($row = mysqli_fetch_assoc($transaksi)) {
        $total = $row['Total'];
        $pria = $row['PriaCount'];
        $wanita = $row['WanitaCount'];
        $unassign = $row['Unassign'];
    }
    $persenPria =(round((float)($pria/$total) * 100 ));
    if(is_nan($persenPria)){
      $persenPria=0;
    }
    $persenWanita =(round((float)($wanita/$total) * 100 ));
    if(is_nan($persenWanita)){
      $persenWanita=0;
    }
    $unassigned =100-$persenPria-$persenWanita;
    array_push($values, array("label" => 'Pria', "value" => $persenPria));
    array_push($values, array("label" => 'Wanita', "value" => $persenWanita));
    array_push($values, array("label" => 'Belum Menentukan', "value" => $unassigned));
    $res['genderTransactionPercentage']=$values;
    // var_dump($res
    
    // }
    // http_response_code($status);
    echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res]);  
