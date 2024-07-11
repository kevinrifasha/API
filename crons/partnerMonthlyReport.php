<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';
require_once '../includes/CalculateFunctions.php';
require '../includes/functions.php';
require_once '../includes/MailerBCC.php';

$cf = new CalculateFunction();
$fs = new functions();

$dateFirstDb = date('Y-m-01', strtotime('-1 month'));
$dateLastDb = date('Y-m-t', strtotime('-1 month'));

$dateFirstDbStr = date('01/m/Y', strtotime('-1 month'));
$dateLastDbStr = date('t/m/Y', strtotime('-1 month'));

$month = date('m', strtotime('-1 month'));

$dateTodayStr = date("d/m/Y");

$data = mysqli_query($db_conn, "SELECT p.id, e.email, p.name FROM employees e JOIN master m ON e.id_master=m.id JOIN roles r ON r.id=e.role_id JOIN partner p ON p.id_master=m.id WHERE r.is_owner='1' AND e.deleted_at IS NULL AND p.is_email_report=1 AND p.status=1 ORDER BY p.id ASC");

if(mysqli_num_rows($data) > 0) {
    $datas = mysqli_fetch_all($data, MYSQLI_ASSOC);
    $tempPID = "";
    foreach ($datas as $value) {
        $hpp = 0;
        $partnerID = $value['id'];
        $email = $value['email'];
        $name = $value['name'];

        $hppQ = mysqli_query(
            $db_conn,
            "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$partnerID' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'"
        );

        $menusQ = mysqli_query(
            $db_conn,
            "SELECT SUM(detail_transaksi.qty) AS qty, menu.nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu  WHERE transaksi.id_partner='$partnerID' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' GROUP BY menu.id ORDER BY qty"
        );

        if (mysqli_num_rows($hppQ) > 0) {
            $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
            $hpp = (double) $resQ[0]['hpp'];
        }

        $opex = 0;
        $sqlOpex = mysqli_query($db_conn, "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE p.id='$partnerID'AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFirstDb' AND '$dateLastDb' ORDER BY op.id DESC");
        while($row = mysqli_fetch_assoc($sqlOpex)){
            $opex = $row['amount']==null?0:(int)$row['amount'];
            // $res['opex'] = $opex;
        }


        $opexes = array();
        $sqlOpexes = mysqli_query($db_conn, "SELECT e.id, e.name, e.amount, e.created_at, em.nama AS employeeName, ec.name AS categoryName, ec.id AS categoryID FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id JOIN partner p ON p.id_master=ec.master_id WHERE p.id='$partnerID' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFirstDb' AND '$dateLastDb' ORDER BY e.id DESC");
        if(mysqli_num_rows($sqlOpexes) > 0) {
            $opexes = mysqli_fetch_all($sqlOpexes, MYSQLI_ASSOC);
        }

        if($partnerID != $tempPID){
            $charge_ewallet = 0;
            $totalEwallet = 0;
            $totalNonEwallet = 0;
            $totalPoint = 0;
            $charge_ur = 0;
            $paymentMethods = $cf->getGroupPaymentMethod($partnerID, $dateFirstDb, $dateLastDb);
            $details = $cf->getSubTotal($partnerID, $dateFirstDb, $dateLastDb);
            $details['gross_profit']=(int) $details['clean_sales'];
            $details['gross_profit'] = (int) $details['gross_profit'] - $hpp;
            $details['gross_profit_afterservice']= (int) $details['gross_profit']- (int) $details['service'];
            $details['gross_profit_aftertax']= (int) $details['gross_profit_afterservice']- (int) $details['tax'];

            foreach ($paymentMethods as $value) {
                $charge_ewallet +=(int) $value['charge_ewallet'];
            }

            $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
                <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">
                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                        body {
                        width: 100% !important;
                        height: 100%;
                        margin: 0;
                        -webkit-text-size-adjust: none;
                        }
                        a {
                        color: #3869D4;
                        }
                        a img {
                        border: none;
                        }
                        td {
                            word-break: break-word;
                        }
                        .preheader {
                            display: none !important;
                            visibility: hidden;
                            mso-hide: all;
                            font-size: 1px;
                            line-height: 1px;
                            max-height: 0;
                            max-width: 0;
                            opacity: 0;
                            overflow: hidden;
                        }

                        body,
                        td,
                        th {
                            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                        }
                        h1 {
                            margin-top: 0;
                            color: #333333;
                            font-size: 22px;
                            font-weight: bold;
                            text-align: left;
                        }
                        h2 {
                            margin-top: 0;
                            color: #333333;
                            font-size: 16px;
                            font-weight: bold;
                            text-align: left;
                        }
                        h3 {
                            margin-top: 0;
                            color: #333333;
                            font-size: 14px;
                            font-weight: bold;
                            text-align: left;
                        }
                        td,
                        th {
                            font-size: 16px;
                        }
                        p,
                        ul,
                        ol,
                        blockquote {
                            margin: .4em 0 1.1875em;
                            font-size: 16px;
                            line-height: 1.625;
                        }
                        p.sub {
                            font-size: 13px;
                        }
                        .align-right {
                            text-align: right;
                        }
                        .align-left {
                            text-align: left;
                        }
                        .align-center {
                            text-align: center;
                        }
                        .button {
                            background-color: #3869D4;
                            border-top: 10px solid #3869D4;
                            border-right: 18px solid #3869D4;
                            border-bottom: 10px solid #3869D4;
                            border-left: 18px solid #3869D4;
                            display: inline-block;
                            text-decoration: none;
                            border-radius: 3px;
                            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                            -webkit-text-size-adjust: none;
                            box-sizing: border-box;
                        }
                        .button--green {
                            background-color: #22BC66;
                            border-top: 10px solid #22BC66;
                            border-right: 18px solid #22BC66;
                            border-bottom: 10px solid #22BC66;
                            border-left: 18px solid #22BC66;
                        }
                        .button--red {
                            background-color: #FF6136;
                            border-top: 10px solid #FF6136;
                            border-right: 18px solid #FF6136;
                            border-bottom: 10px solid #FF6136;
                            border-left: 18px solid #FF6136;
                        }
                        @media only screen and (max-width: 500px) {
                            .button {
                            width: 100% !important;
                            text-align: center !important;
                            }
                        }
                        .attributes {
                            margin: 0 0 21px;
                        }
                        .attributes_content {
                        background-color: #F4F4F7;
                        padding: 16px;
                        }
                        .attributes_item {
                        padding: 0;
                        }
                        .related {
                        width: 100%;
                        margin: 0;
                        padding: 25px 0 0 0;
                        -premailer-width: 100%;
                        -premailer-cellpadding: 0;
                        -premailer-cellspacing: 0;
                        }
                        .related_item {
                        padding: 10px 0;
                        color: #CBCCCF;
                        font-size: 15px;
                        line-height: 18px;
                        }
                        .related_item-title {
                        display: block;
                        margin: .5em 0 0;
                        }
                        .related_item-thumb {
                        display: block;
                        padding-bottom: 10px;
                        }
                        .related_heading {
                        border-top: 1px solid #CBCCCF;
                        text-align: center;
                        padding: 25px 0 10px;
                        }
                        .discount {
                            width: 100%;
                            margin: 0;
                            padding: 24px;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                            background-color: #F4F4F7;
                            border: 2px dashed #CBCCCF;
                        }
                        .discount_heading {
                            text-align: center;
                        }
                        .discount_body {
                            text-align: center;
                            font-size: 15px;
                        }
                        .social {
                            width: auto;
                        }
                        .social td {
                            padding: 0;
                            width: auto;
                        }
                        .social_icon {
                            height: 20px;
                            margin: 0 8px 10px 8px;
                            padding: 0;
                        }
                        .purchase {
                            width: 100%;
                            margin: 0;
                            padding: 35px 0;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                        }
                        .purchase_content {
                            width: 100%;
                            margin: 0;
                            padding: 25px 0 0 0;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                        }
                        .purchase_item {
                            padding: 10px 0;
                            color: #51545E;
                            font-size: 15px;
                            line-height: 18px;
                        }
                        .purchase_heading {
                            padding-bottom: 8px;
                            border-bottom: 1px solid #EAEAEC;
                        }
                        .purchase_heading p {
                            margin: 0;
                            color: #85878E;
                            font-size: 12px;
                        }
                        .purchase_footer {
                            padding-top: 15px;
                            border-top: 1px solid #EAEAEC;
                        }
                        .purchase_total {
                            margin: 0;
                            text-align: right;
                            font-weight: bold;
                            color: #333333;
                        }
                        .purchase_total--label {
                            padding: 0 15px 0 0;
                        }
                        body {
                            background-color: #F4F4F7;
                            color: #51545E;
                        }
                        p {
                            color: #51545E;
                        }
                        p.sub {
                            color: #6B6E76;
                        }
                        .email-wrapper {
                            width: 100%;
                            margin: 0;
                            padding: 0;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                            background-color: #F4F4F7;
                        }
                        .email-content {
                            width: 100%;
                            margin: 0;
                            padding: 0;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                        }
                        .email-masthead {
                            padding: 25px 0;
                            text-align: center;
                        }
                        .email-masthead_logo {
                            width: 94px;
                        }
                        .email-masthead_name {
                            font-size: 16px;
                            font-weight: bold;
                            color: #A8AAAF;
                            text-decoration: none;
                            text-shadow: 0 1px 0 white;
                        }
                        .email-body {
                            width: 100%;
                            margin: 0;
                            padding: 0;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                            background-color: #FFFFFF;
                        }
                        .email-body_inner {
                            width: 570px;
                            margin: 0 auto;
                            padding: 0;
                            -premailer-width: 570px;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                            background-color: #FFFFFF;
                        }
                        .email-footer {
                            width: 570px;
                            margin: 0 auto;
                            padding: 0;
                            -premailer-width: 570px;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                            text-align: center;
                        }
                        .email-footer p {
                            color: #6B6E76;
                        }
                        .body-action {
                            width: 100%;
                            margin: 30px auto;
                            padding: 0;
                            -premailer-width: 100%;
                            -premailer-cellpadding: 0;
                            -premailer-cellspacing: 0;
                            text-align: center;
                        }
                        .body-sub {
                            margin-top: 25px;
                            padding-top: 25px;
                            border-top: 1px solid #EAEAEC;
                        }
                        .content-cell {
                            padding: 35px;
                        }
                        @media only screen and (max-width: 600px) {
                        .email-body_inner,
                        .email-footer {
                            width: 100% !important;
                        }
                        }
                        @media (prefers-color-scheme: dark) {
                        body,
                        .email-body,
                        .email-body_inner,
                        .email-content,
                        .email-wrapper,
                        .email-masthead,
                        .email-footer {
                            background-color: #333333 !important;
                            color: #FFF !important;
                        }
                        p,
                        ul,
                        ol,
                        blockquote,
                        h1,
                        h2,
                        h3 {
                            color: #FFF !important;
                        }
                        .attributes_content,
                        .discount {
                            background-color: #222 !important;
                        }
                        .email-masthead_name {
                            text-shadow: none !important;
                        }
                        }
                    </style>
                </head>

                <body>
                    <span class="preheader">Laporan Keuangan Bulanan Tanggal ' . $dateTodayStr . '</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td align="center">
                                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                                        <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td class="content-cell">
                                                    <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"  width="96"></div>
                                                    <div class="f-fallback">
                                                        <h1>Hi ' . $name . ',</h1>
                                                        <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>';

                                                            $val1 = 0;
                                                            $totalEwallet1 = 0;
                                                            $totalNonEwallet1  = 0;
                                                            $totalPoint1 = 0;
                                                            $charge_ur1 = 0;
                                                            foreach ($paymentMethods as $value) {

                                                                if($value['tipe']=='1' || $value['tipe']=='2' || $value['tipe']=='3' || $value['tipe']=='4' ||$value['tipe']=='6' ||$value['tipe']=='10'){
                                                                    $val1 = (int) $value['value'];
                                                                    $totalEwallet1 += (int) $value['value'];
                                                                    $totalPoint1 += (int) $value['point'];
                                                                    $charge_ur1 += (int) $value['charge_ur'];
                                                                }
                                                                if($value['tipe']=='5' || $value['tipe']=='7' || $value['tipe']=='8' || $value['tipe']=='9'){
                                                                    $totalNonEwallet1 += (int) $value['value'];
                                                                    $totalPoint1 += (int) $value['point'];
                                                                    $charge_ur1 += (int) $value['charge_ur'];
                                                                }
                                                            }

                                                            $html .=  '<td align="center">
                                                                    <h1 class="f-fallback discount_heading">' . $fs->rupiah($totalEwallet1-$charge_ewallet+$totalNonEwallet1-$totalPoint1-$charge_ur1-$hpp-$details['service']-$details['tax'])  . '</h1>
                                                                    <p>Periode '.  $fs->tgl_indo(date('m', strtotime('-1 month')))  .'</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td>
                                                                    <h3>Rincian Pendapatan</h3>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                        </tr>';

                                                                        foreach ($paymentMethods as $value) {
                                                                            $fs = new functions();
                                                                            $val = 0;
                                                                            if($value['tipe']=='1' || $value['tipe']=='2' || $value['tipe']=='3' || $value['tipe']=='4' ||$value['tipe']=='6' ||$value['tipe']=='10'){
                                                                                $val = (int) $value['value'];
                                                                                $html .= '<tr>
                                                                                    <td width="80%" class="purchase_item"><span class="f-fallback">'.$value['payment_method_name'].'</span></td>
                                                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.$fs->rupiah($val).'</span></td>
                                                                                </tr>';
                                                                                $totalEwallet += (int) $value['value'];
                                                                                $totalPoint += (int) $value['point'];
                                                                                $charge_ur += (int) $value['charge_ur'];
                                                                            }
                                                                        }

                                                                        $html .= '
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback"> MDR ( 1.5% + 10%)</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($charge_ewallet) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah((int) $totalEwallet- (int) $charge_ewallet) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                        </tr>';

                                                                        foreach ($paymentMethods as $value) {
                                                                            if($value['tipe']=='5' || $value['tipe']=='7' || $value['tipe']=='8' || $value['tipe']=='9'){
                                                                                $html .= '
                                                                                <tr>
                                                                                    <td width="80%" class="purchase_item"><span class="f-fallback">'.$value['payment_method_name'].'</span></td>
                                                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah( (int) $value['value']) . '</span></td>
                                                                                </tr>';
                                                                                $totalNonEwallet += (int) $value['value'];
                                                                                $totalPoint += (int) $value['point'];
                                                                                $charge_ur += (int) $value['charge_ur'];
                                                                            }
                                                                        }

                                                                        $html .= '
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Pembayaran Point</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah( (int) $totalPoint) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($totalEwallet-$charge_ewallet+$totalNonEwallet-$totalPoint) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($charge_ur) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">HPP</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($hpp) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Service</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($details['service']) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Pajak</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($details['tax']) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Biaya Operasional</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($opex) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">Laba Bersih</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($totalEwallet-$charge_ewallet+$totalNonEwallet-$totalPoint-$charge_ur-$hpp-$details['service']-$details['tax']-$opex) . '</span></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($totalEwallet-$charge_ewallet+$totalNonEwallet-$totalPoint-$charge_ur-$hpp-$details['service']-$details['tax']) . '</span></td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td>
                                                            <h3>Menu Terjual</h3></td>
                                                            <td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">

                                                                <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                    <th class="purchase_heading" align="left">
                                                                        <p class="f-fallback">Menu</p>
                                                                    </th>
                                                                    <th class="purchase_heading" align="right">
                                                                        <p class="f-fallback">Amount</p>
                                                                    </th>
                                                                    </tr>';

                                                                    if (mysqli_num_rows($menusQ) > 0) {
                                                                        $resQ = mysqli_fetch_all($menusQ, MYSQLI_ASSOC);
                                                                        foreach ($resQ as $value) {
                                                                            $html .= '
                                                                            <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">' . $value['nama'] . '</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $value['qty'] . '</span></td>
                                                                            </tr>
                                                                            ';
                                                                        }
                                                                    }
                                                                $html .='</table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td>
                                                                    <h3>Variant Terjual</h3></td>
                                                                <td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                                                        <tr>
                                                                            <th class="purchase_heading" align="left">
                                                                                <p class="f-fallback">Variant(Nama Menu - Variant Grup)</p>
                                                                            </th>
                                                                            <th class="purchase_heading" align="right">
                                                                                <p class="f-fallback">Amount</p>
                                                                            </th>
                                                                        </tr>';

                                                                        $reportV = array();
                                                                        $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$partnerID'  AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' ");
                                                                        while ($rowMenu = mysqli_fetch_assoc($fav)) {
                                                                            $variant = $rowMenu['variant'];
                                                                            $namaMenu = $rowMenu['nama'];
                                                                            $variant = substr($variant, 1, -1);
                                                                            $var = "{" . $variant . "}";
                                                                            $var = str_replace("'", '"', $var);
                                                                            $var1 = json_decode($var, true);

                                                                            if (
                                                                            isset($var1['variant'])
                                                                            && !empty($var1['variant'])
                                                                            ) {
                                                                            $arrVar = $var1['variant'];
                                                                            foreach ($arrVar as $arr) {
                                                                                $v_id=0;
                                                                                $vg_name = $arr['name'];
                                                                                $detail = $arr['detail'];
                                                                                $v_qty = 0;
                                                                                foreach ($detail as $det) {
                                                                                $v_name =  $det['name'];
                                                                                $v_qty += (int) $det['qty'];
                                                                                $idx = 0;
                                                                                foreach ($reportV as $value) {
                                                                                    if($value['id']==$det['id'] && $value['name']==$v_name && $value['vg_name']==$vg_name && $value['menu_name']==$namaMenu){
                                                                                        $idx=$idx;
                                                                                        break;
                                                                                    }else{
                                                                                        $idx+=1;
                                                                                    }
                                                                                }
                                                                                $v_id=$idx;
                                                                                $dic[$v_id]=$det['id'];
                                                                                $reportV[$v_id]['id'] = $det['id'];
                                                                                if($reportV[$v_id]['qty']){
                                                                                    $reportV[$v_id]['qty']+= $v_qty;
                                                                                }else{
                                                                                    $reportV[$v_id]['qty']=$v_qty;
                                                                                }
                                                                                $reportV[$v_id]['name'] = $v_name;
                                                                                $reportV[$v_id]['vg_name'] = $vg_name;
                                                                                $reportV[$v_id]['menu_name'] = $namaMenu;
                                                                                }
                                                                            }
                                                                            foreach ($reportV as $key => $row) {
                                                                                $qty[$key]  = $row['qty'];
                                                                                $menu_name[$key] = $row['menu_name'];
                                                                            }

                                                                            // you can use array_column() instead of the above code
                                                                            $qty  = array_column($reportV, 'qty');
                                                                            $menu_name = array_column($reportV, 'menu_name');

                                                                            // Sort the data with qty descending, menu_name ascending
                                                                            // Add $data as the last parameter, to sort by the common key
                                                                            array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                                                                            }

                                                                        }

                                                                        foreach ($reportV as $rv) {
                                                                            $html .= '<tr>
                                                                                <td width="80%" class="purchase_item"><span class="f-fallback">' . $rv['name'] . '(' . $rv['menu_name'] . ' - ' . $rv['vg_name'] . ')' . '</span></td>
                                                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $rv['qty'] . '</span></td>
                                                                            </tr>';
                                                                        }

                                                                    $html .= '</table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td>
                                                            <h3>Biaya Operasional</h3></td>
                                                            <td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">

                                                                <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                    <th class="purchase_heading" align="left">
                                                                        <p class="f-fallback">Nama(Waktu-PIC)</p>
                                                                    </th>
                                                                    <th class="purchase_heading" align="right">
                                                                        <p class="f-fallback">Amount</p>
                                                                    </th>
                                                                    </tr>';
                                                                        foreach ($opexes as $value) {
                                                             $ca = date("H:i d/m/Y", strtotime($value['created_at']));                 $html .= '
                                                                            <tr>
                                                                            <td width="80%" class="purchase_item"><span class="f-fallback">' . $value['name'] . '('.$ca.'-'.$value['employeeName'].')'.'</span></td>
                                                                            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($value['amount']) . '</span></td>
                                                                            </tr>
                                                                            ';
                                                                        }
                                                                $html .='</table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <p> untuk laporan yang lebih detail silakan cek <a href="https://partner.ur-hub.com/dashboard">web partner</a></p>
                                                        <p>Hormat Kami,
                                                        <br>UR - Easy & Quick Order</p>
                                                        <table class="body-sub" role="presentation">
                                                            <tr>
                                                                <td>
                                                                    <p class="f-fallback sub"></strong> <a href="https://api-dev2.ur-hub.com/ur-api/csv/MonthlyTrxXls.php?partnerID=' . md5($partnerID) . '&dateStart='.$dateFirstDb.'&dateEnd='.$dateLastDb.'">download laporan penjualan</a>.</p>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <p class="f-fallback sub"></strong> <a href="https://api-dev2.ur-hub.com/ur-api/csv/MonthlyAttendanceXls.php?partnerID=' . md5($partnerID) .  '&dateStart='.$dateFirstDb.'&dateEnd='.$dateLastDb.'">download laporan kehadiran</a>.</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td class="content-cell" align="center">
                                                    <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                                    <p class="f-fallback sub align-center">
                                                    PT. Rahmat Tuhan Lestari
                                                    <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                                    <br>Jawa Barat 40221
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ';
        }

        $mailer = new MailerBcc();
        if ($mailer->sendMessage(
            $email,
            'Laporan Keuangan',
            $html
            ) == TRANSAKSI_CREATED) {
        //         var_dump($email);
                // echo $html;
            } else {
                echo "FAILED_TO_CREATE_TRANSAKSI";
            }

            $tempPID = $partnerID;
    }
}
?>