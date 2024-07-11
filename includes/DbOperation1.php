<?php


date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Endroid\QrCode\QrCode;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

class DbOperation
{
  private $conn;
  private $conn2;


  //Constructor
  function __construct()
  {
    require_once dirname(__FILE__) . '/Constants.php';
    require_once dirname(__FILE__) . '/DbConnect.php';
    require_once dirname(__FILE__) . '/Mailer.php';
    require_once dirname(__FILE__) . '/functions.php';



    // opening db connection
    $db = new DbConnect();
    $this->conn = $db->connect();
    $this->conn2 = $db->connect();
  }


  public function mailingAccountingMaster($email)
  {
    $fs = new functions();
    $stmt = $this->conn->prepare("SELECT id,name, phone, email FROM master WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $email);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);




      $dateNow = date('d/M/Y');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      // $first_day_last_month = date('01/M/Y');
      // $last_day_last_month  = date('t/M/Y');
      // $dateFirstDb = date('Y-m-01');
      // $dateLastDb = date('Y-m-t');


      $menu = mysqli_query($db_conn, "SELECT * FROM menu join partner ON menu.id_partner=partner.id WHERE partner.id_master='$id'");
      $transaksi = mysqli_query($db_conn, "SELECT transaksi.* FROM transaksi join partner ON transaksi.id_partner=partner.id WHERE partner.id_master='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");


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
      $point = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      $sumTax = 0;
      $sumService = 0;
      $sumPromo = 0;
      $sumTotal = 0;
      $sumCharge_ewallet = 0;
      $sumPoint = 0;
      // Set some content to print
      while ($row = mysqli_fetch_assoc($transaksi)) {
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $tot = 0;
        $countCE = 0;

        $tax = $row['tax'];
        $service = $row['service'];
        $charge_ur = $row['charge_ur'];
        $charge_ewallet = $row['charge_ewallet'];
        $total = $row['total'];
        $promo = $row['promo'];
        $point = $row['point'];
        $sumPoint += $point;

        $sumPromo += $promo;
        $sumTotal += $total;
        $sumCharge_ur += $charge_ur;

        $countService = ceil((($total - $promo) * $service) / 100);
        $sumService += $countService;
        $countTax = ceil(((($total - $promo) + $countService + $charge_ur) * $tax) / 100) + $charge_ur;
        $sumTax += $countTax;

        if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
          $tot = $countService + $countTax + $total - $promo;
          $countCE = ceil(($tot * $charge_ewallet) / 100) + ceil((ceil(($tot * $charge_ewallet) / 100) * $tax) / 100);
          $sumCharge_ewallet += $countCE;
          $ovo += $tot;
        } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
          $tot = $countService + $countTax + $total - $promo;
          $countCE = ceil(($tot * $charge_ewallet) / 100) + ceil((ceil(($tot * $charge_ewallet) / 100) * $tax) / 100);
          $sumCharge_ewallet += $countCE;
          $gopay += $tot;
        } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
          $tot = $countService + $countTax + $total - $promo;
          $countCE = ceil(($tot * $charge_ewallet) / 100) + ceil((ceil(($tot * $charge_ewallet) / 100) * $tax) / 100);
          $sumCharge_ewallet += $countCE;
          $dana += $tot;
        } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
          $tot = $countService + $countTax + $total - $promo;
          $countCE = ceil(($tot * $charge_ewallet) / 100) + ceil((ceil(($tot * $charge_ewallet) / 100) * $tax) / 100);
          $sumCharge_ewallet += $countCE;
          $linkaja += $tot;
        } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
          $tot = $countService + $countTax + $total - $promo;
          $tunaiDebit += $tot;
        } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
          $tot = $countService + $countTax + $total - $promo;
          $countCE = ceil(($tot * $charge_ewallet) / 100) + ceil((ceil(($tot * $charge_ewallet) / 100) * $tax) / 100);
          $sumCharge_ewallet += $countCE;
          $sakuku += $tot;
        } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
          $tot = $countService + $countTax + $total - $promo;
          $creditCard += $tot;
        } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
          $tot = $countService + $countTax + $total - $promo;
          $debitCard += $tot;
        }
      }
      $hargaPokok = 0;
      //   $subtotalAll =(($ovo+$gopay+$dana+$linkaja+$sakuku)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)))+$tunaiDebit+$creditCard);
      $subtotalAll = ($sumTotal + $sumService + $sumTax - $sumCharge_ur - ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) - $sumPoint;
      $menu2 = mysqli_query($db_conn, "SELECT partner.name as nama_partner,menu.nama,menu.harga,menu.hpp,SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) AS total_harga,SUM(detail_transaksi.qty) as qtyTotal FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE MD5(partner.id)='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' GROUP BY menu.nama order by SUM(detail_transaksi.qty) DESC");
      while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
        $hargaPokok += $rowcheck1['hpp'];
      }
      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur) - $sumPoint;
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
              <span class="preheader">Laporan Keuangan Bulanan Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</span>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                  <div class="align-center"><img  src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-purple.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Bulan ' . $fs->tgl_indo(date('m', strtotime('-1 month'))) . '</p>
                                          <h1 class="f-fallback discount_heading">' . $fs->rupiah($sumTotal - $sumPromo + ($sumService + $sumTax - $sumCharge_ewallet - $sumCharge_ur) - $sumPoint) . '</h1>
                                          <p>Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</p>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';

      $html .= '<tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($ovo) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($gopay) . '</span></td>
                                                    </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($dana) . '</span></td>
                                                    </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($linkaja) . '</span></td>
                                                    </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sakuku) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">MDR (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sumCharge_ewallet) . '</span></td>
                                                  </tr>
                                                  <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet) . '</span></td>
                                                </tr>
                                                    ';
      $html .= '
                                                <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    </tr>
                                                <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($tunaiDebit) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($creditCard) . '</span></td>
                                                  </tr>
                                                  <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($debitCard) . '</span></td>
                                                  </tr>
                                                  <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                </tr>
                                                <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback">Pembayaran Point</span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($sumPoint) . '</span></td>
                                                </tr>
                                                <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah(($sumTotal - $sumPromo + ($sumService + $sumTax - $sumCharge_ewallet)) - $sumPoint) . '</span></td>
                                              </tr>
                                              <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                </tr>
                                                <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($sumCharge_ur) . '</span></td>
                                              </tr>

                                              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                            </tr>
                                            <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah(($sumTotal - $sumPromo + ($sumService + $sumTax - $sumCharge_ewallet - $sumCharge_ur)) - $sumPoint) . '</span></td>
                                              </tr>
                                                    ';
      $html .= '</table>
                                          </td>
                                        </tr>
                                      </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td>
                                          <h3>Menu Terlaris</h3></td>
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
      $fav = mysqli_query($db_conn, "SELECT  menu.nama,SUM(detail_transaksi.qty) AS qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id_master= '$id' AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'  GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                            </tr>';
      }
      $html .= '</table>
                                        </td>
                                      </tr>
                                    </table>
                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td>
                                          <h3>Variant Terlaris</h3></td>
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
      $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id_master= '$id'  AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' ");
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
            $vg_name = $arr['name'];
            $detail = $arr['detail'];
            $v_qty = 0;
            foreach ($detail as $det) {
              $v_name =  $det['name'];
              $v_qty += (int) $det['qty'];
              $v_id = $det['id'];
              $reportV[$v_id]['id'] = $v_id;
              $reportV[$v_id]['qty'] = $v_qty;
              $reportV[$v_id]['name'] = $v_name;
              $reportV[$v_id]['vg_name'] = $vg_name;
              $reportV[$v_id]['menu_name'] = $namaMenu;
            }
          }
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
                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfMaster.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsMaster.php?id=' . md5($id) . '">download a XLS version</a>.</p>
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
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        'Laporan Keuangan',
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function mailingAccountingPartner($email)
  {
    $fs = new functions();
    $stmt = $this->conn->prepare("SELECT id,name,phone,tax,service,email FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $tax, $service, $email);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

      $dateNow = date('d/M/Y');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      // $first_day_last_month = date('01/M/Y');
      // $last_day_last_month  = date('t/M/Y');
      // $dateFirstDb = date('Y-m-01');
      // $dateLastDb = date('Y-m-t');

      $menu = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $sumCharge_ur = 0;
      $sumCharge_ewallet = 0;
      $sumCharge_ewallet1 = 0;
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $point = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      $sumPoint = 0;
      $sumCharge_ur1 = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur, point FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $sumCharge_ur += $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $point = $rowtypeBayar['point'];
        $sumPoint += $point;
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $countCE = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
        $sumCharge_ewallet1 = $sumCharge_ewallet;
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
                <span class="preheader">Laporan Keuangan Bulanan Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</span>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                <div class="align-center"><img  src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-purple.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Bulan ' . $fs->tgl_indo(date('m', strtotime('-1 month'))) . '</p>
                                          <h1 class="f-fallback discount_heading">' . $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet + $tunaiDebit + $creditCard + $debitCard - $sumCharge_ur - $sumPoint) . '</h1>
                                          <p>Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</p>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
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
      $sumPoint = 0;
      $point = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur, point FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        $sumCharge_ur += $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $point = $rowtypeBayar['point'];
        $sumPoint = $point;
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $countCE = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.$fs->rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.$fs->rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($ovo) . '</span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($gopay) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($dana) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($linkaja) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sakuku) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback"> MDR (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sumCharge_ewallet1) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet1) . '</span></td>
            </tr>
                ';
      $html .= '
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($tunaiDebit) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($creditCard) . '</span></td>
              </tr>
              <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($debitCard) . '</span></td>
              </tr>
              <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">Pembayaran Point</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sumPoint) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
            </tr>
            <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet1 + $tunaiDebit + $creditCard + $debitCard - $sumPoint) . '</span></td>
          </tr>
          <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
        </tr>
        <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($sumCharge_ur) . '</span></td>
      </tr>
      <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
    </tr>
    <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet1 + $tunaiDebit + $creditCard + $debitCard - $sumCharge_ur - $sumPoint) . '</span></td>
      </tr>
                ';



      $html .= '</table>
                                                                                </td>
                                                                              </tr>
                                                                            </table>

                                                                          <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                                            <tr>
                                                                              <td>
                                                                                <h3>Menu Terlaris</h3></td>
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
      $fav = mysqli_query($db_conn, "SELECT menu.nama,SUM(detail_transaksi.qty) AS qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id JOIN partner ON transaksi.id_partner = partner.id WHERE partner.id= '$id' AND transaksi.status<=2 and transaksi.status>=1  AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                                                                    <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                                                                  </tr>';
      }
      $html .= '</table>
                                                                              </td>
                                                                            </tr>
                                                                          </table>

                                                                          <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                                          <tr>
                                                                            <td>
                                                                              <h3>Variant Terlaris</h3></td>
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
      $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id'  AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' ");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $variant = $rowMenu['variant'];
        $namaMenu = $rowMenu['nama'];
        $variant = substr($variant, 1, -1);
        $var = "{" . $variant . "}";
        $var = str_replace("'", '"', $var);
        $var1 = json_decode($var, true);

        $arrVar = $var1['variant'];
        foreach ($arrVar as $arr) {
          $vg_name = $arr['name'];
          $detail = $arr['detail'];
          foreach ($detail as $det) {
            $v_name =  $det['name'];
            $v_qty = (int) $det['qty'];
            $v_id = $det['id'];
            $reportV[$v_id]['id'] = $v_id;
            $reportV[$v_id]['qty'] += $v_qty;
            $reportV[$v_id]['name'] = $v_name;
            $reportV[$v_id]['vg_name'] = $vg_name;
            $reportV[$v_id]['menu_name'] = $namaMenu;
          }
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

                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfPartner.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsPartner.php?id=' . md5($id) . '">download a Xls version</a>.</p>
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
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Laporan Keuangan",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }


  public function mailingPerDayPartner($email)
  {
    $fs = new functions();
    $stmt = $this->conn->prepare("SELECT id,name, phone,email,jam_buka,jam_tutup,tax,service FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $email, $jamBuka, $jamTutup, $tax, $service);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      $dateNow = date('d/M/Y');
      $dateNowDb = date('Y-m-d');
      // $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      // $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      // $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      // $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      $menu = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $point = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $sumCharge_ur = 0;
      $sumPoint = 0;
      $sumCharge_ewallet = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur, point FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' ");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $point = $rowtypeBayar['point'];
        $sumPoint += $point;
        $sumCharge_ur += $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $countCE = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }
      // / if($tax==1){
      //   $subtotal = ($total + ($total * 0.1));
      // }
      // if($service!=0){
      //   $subtotal = ($total + ($total * ($service/100)));
      // }
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                <div class="align-center"><img  src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-purple.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Hari Ini</p>
                                          <h1 class="f-fallback discount_heading">' . $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet + $tunaiDebit + $creditCard + $debitCard - $sumCharge_ur - $sumPoint) . '</h1>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $point = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $sumCharge_ur = 0;
      $sumPoint = 0;
      $sumCharge_ewallet = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur,point FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' ");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $sumCharge_ur += $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $point = $rowtypeBayar['point'];
        $sumPoint += $point;
        $servicetype = $rowtypeBayar['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $countCE = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $countCE = ceil(($withTax * $charge_ewallet) / 100) + ceil((ceil(($withTax * $charge_ewallet) / 100) * $taxtype) / 100);
          $sumCharge_ewallet += $countCE;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.$fs->rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.$fs->rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($ovo) . '</span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($gopay) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($dana) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($linkaja) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sakuku) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback"> MDR (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sumCharge_ewallet) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet) . '</span></td>
            </tr>
                ';
      $html .= '
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($tunaiDebit) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($creditCard) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($debitCard) . '</span></td>
            </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">Pembayaran Poin</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . $fs->rupiah($sumPoint) . '</span></td>
            </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
            </tr>
            <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet + $tunaiDebit + $creditCard + $debitCard) - $sumPoint . '</span></td>
          </tr>
          <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
        </tr>
        <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($sumCharge_ur) . '</span></td>
      </tr>
      <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
    </tr>
    <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  $fs->rupiah($ovo + $gopay + $dana + $linkaja + $sakuku - $sumCharge_ewallet + $tunaiDebit + $creditCard + $debitCard - $sumCharge_ur - $sumPoint) . '</span></td>
      </tr>
                ';


      $html .= '</table>
                                          </td>
                                        </tr>
                                      </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td>
                                          <h3>Menu Terlaris</h3></td>
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
      $fav = mysqli_query($db_conn, "SELECT menu.nama,SUM(detail_transaksi.qty) AS qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id JOIN partner ON transaksi.id_partner = partner.id WHERE partner.id= '$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                            </tr>';
      }
      $html .= '</table>
                                        </td>
                                      </tr>
                                    </table>

                                    </td>
                                    </tr>
                                  </table>

                                  <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td>
                                      <h3>Variant Terlaris</h3></td>
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
      $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id'  AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb'");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $variant = $rowMenu['variant'];
        $namaMenu = $rowMenu['nama'];
        $variant = substr($variant, 1, -1);
        $var = "{" . $variant . "}";
        $var = str_replace("'", '"', $var);
        $var1 = json_decode($var, true);

        $arrVar = $var1['variant'];
        foreach ($arrVar as $arr) {
          $vg_name = $arr['name'];
          $detail = $arr['detail'];
          foreach ($detail as $det) {
            $v_name =  $det['name'];
            $v_qty = (int) $det['qty'];
            $v_id = $det['id'];
            $reportV[$v_id]['id'] = $v_id;
            $reportV[$v_id]['qty'] += $v_qty;
            $reportV[$v_id]['name'] = $v_name;
            $reportV[$v_id]['vg_name'] = $vg_name;
            $reportV[$v_id]['menu_name'] = $namaMenu;
          }
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

                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfPerDay.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsPerDay.php?id=' . md5($id) . '">download a Xls version</a>.</p>
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
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Laporan Keuangan",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
  public function mailingApplicant($email, $career_title, $name, $career_category)
  {
    $html = '
    <!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>UR Applicant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /**
   * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
   */
    @media screen {
      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 400;
        src: local("Source Sans Pro Regular"), local("SourceSansPro-Regular"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format("woff");
      }

      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 600;
        src: local("Source Sans Pro Bold"), local("SourceSansPro-Bold"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format("woff");
      }
    }


    /**
   * Avoid browser level font resizing.
   * 1. Windows Mobile
   * 2. iOS / OSX
   */
    body,
    table,
    td,
    a {
      -ms-text-size-adjust: 100%;
      /* 1 */
      -webkit-text-size-adjust: 100%;
      /* 2 */
    }

    /**
   * Remove extra space added to tables and cells in Outlook.
   */
    table,
    td {
      mso-table-rspace: 0pt;
      mso-table-lspace: 0pt;
    }

    /**
   * Better fluid images in Internet Explorer.
   */
    img {
      -ms-interpolation-mode: bicubic;
    }

    /**
   * Remove blue links for iOS devices.
   */
    a[x-apple-data-detectors] {
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      color: inherit !important;
      text-decoration: none !important;
    }

    /**
   * Fix centering issues in Android 4.4.
   */
    div[style*="margin: 16px 0;"] {
      margin: 0 !important;
    }

    body {
      width: 100% !important;
      height: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /**
   * Collapse table borders to avoid space between cells.
   */
    table {
      border-collapse: collapse !important;
    }

    a {
      color: #1a82e2;
    }

    img {
      height: auto;
      line-height: 100%;
      text-decoration: none;
      border: 0;
      outline: none;
    }

    .fa {
      padding: 5px;
      font-size: 30px;
      width: 30px;
      text-align: center;
      text-decoration: none;
      margin: 5px 2px;
    }

    .fa:hover {
      opacity: 0.7;
    }

    .fa-facebook {
      background: #3B5998;
      color: white;
    }

    .fa-instagram {
      background: #125688;
      color: white;
    }

    .fa-linkedin {
      background: #007bb5;
      color: white;
    }

    /* .t1{
    background-color: red;
  }

  @media only screen and (max-width: 600px) {
  .t1{
    background-color: lightblue;
  } */
    }
  </style>

</head>

<body>

  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center" bgcolor="#f2f4f6">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" bgcolor="#ffffff">
              <a href="https://ur-hub.com">
                <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-blue-boxless.png" alt="Logo"
                  border="0" width="48" style="width: 48px; max-width: 48px; min-width: 48px;">
              </a>
            </td>
          </tr>

          <tr>
            <td align="left" bgcolor="#ffffff"
              style="padding:24px 24px 0; font-family: Source Sans Pro, Helvetica, Arial, sans-serif;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 600; letter-spacing: -1px; line-height: 48px;">Terima
                Kasih Atas Lamaran Anda!</h1>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td align="center" bgcolor="#f2f4f6" valign="top" width="100%">
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%"
          style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 100%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:500px;">
                  <tr>
                    <td align="left" bgcolor="#ffffff"
                      style="padding: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p style="margin-bottom: 10px;">Dear ' . $name . '.</p>
                      <p style="margin-bottom: 10px;">Terima kasih telah tertarik di bagian ' . $career_category . ' dengan posisi
                      ' . $career_title . '.</p>
                      <p style="margin-bottom: 10px;">Kami akan memeriksa lamaran anda dan kami akan menghubungi anda
                        segera.</p>
                      <p style="margin-bottom: 10px;">Salam,</p>
                      <p style="margin: 0;"><a href="https://ur-hub.com/">UR </a> HR Team </p>
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
      <td align="center" bgcolor="#f2f4f6">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 50%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250;">
                  <tr>
                    <td align="left" border="1" valign="top"
                      style="padding-bottom: 24px; padding-left: 15px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">

                      <p>Copyright 2020 © PT. Rahmat Tuhan Lestari</p>
                      <p>Hak Cipta Dilindungi Undang-undang</p>
                    </td>
                  </tr>
                </table>
              </div>

              <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                  <tr>
                    <td align="left" valign="top"
                      style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p><strong>Ketahui info terbaru ur</strong></p>
                      <a href="https://www.facebook.com/UR-Easy-Quick-Order-101245531496672/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/fb.png" alt="UR Facebook"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://www.instagram.com/ur.hub/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/ig.png" alt="UR Instagram"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://id.linkedin.com/company/ur-hub?trk=public_profile_topcard_current_company">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/linkedin.png" alt="UR Linkedin"
                          border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px;">
                      </a>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>


        </table>

      </td>
    </tr>

  </table>

</body>

</html>
    ';

    $mailer = new Mailer();
    if ($mailer->sendMessage(
      $email,
      "Pemberitahuan Lamaran",
      $html
    )) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function mailingApplicantToAdmin($email, $career_title, $name, $phone, $career_category)
  {
    $html = '
    <!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>UR Applicant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /**
   * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
   */
    @media screen {
      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 400;
        src: local("Source Sans Pro Regular"), local("SourceSansPro-Regular"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format("woff");
      }

      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 600;
        src: local("Source Sans Pro Bold"), local("SourceSansPro-Bold"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format("woff");
      }
    }


    /**
   * Avoid browser level font resizing.
   * 1. Windows Mobile
   * 2. iOS / OSX
   */
    body,
    table,
    td,
    a {
      -ms-text-size-adjust: 100%;
      /* 1 */
      -webkit-text-size-adjust: 100%;
      /* 2 */
    }

    /**
   * Remove extra space added to tables and cells in Outlook.
   */
    table,
    td {
      mso-table-rspace: 0pt;
      mso-table-lspace: 0pt;
    }

    /**
   * Better fluid images in Internet Explorer.
   */
    img {
      -ms-interpolation-mode: bicubic;
    }

    /**
   * Remove blue links for iOS devices.
   */
    a[x-apple-data-detectors] {
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      color: inherit !important;
      text-decoration: none !important;
    }

    /**
   * Fix centering issues in Android 4.4.
   */
    div[style*="margin: 16px 0;"] {
      margin: 0 !important;
    }

    body {
      width: 100% !important;
      height: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /**
   * Collapse table borders to avoid space between cells.
   */
    table {
      border-collapse: collapse !important;
    }

    a {
      color: #1a82e2;
    }

    img {
      height: auto;
      line-height: 100%;
      text-decoration: none;
      border: 0;
      outline: none;
    }

    .fa {
      padding: 5px;
      font-size: 30px;
      width: 30px;
      text-align: center;
      text-decoration: none;
      margin: 5px 2px;
    }

    .fa:hover {
      opacity: 0.7;
    }

    .fa-facebook {
      background: #3B5998;
      color: white;
    }

    .fa-instagram {
      background: #125688;
      color: white;
    }

    .fa-linkedin {
      background: #007bb5;
      color: white;
    }

    /* .t1{
    background-color: red;
  }

  @media only screen and (max-width: 600px) {
  .t1{
    background-color: lightblue;
  } */
    }
  </style>

</head>

<body>

  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center" bgcolor="#f2f4f6">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" bgcolor="#ffffff">
              <a href="https://ur-hub.com">
                <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-blue-boxless.png" alt="Logo"
                  border="0" width="48" style="width: 48px; max-width: 48px; min-width: 48px;">
              </a>
            </td>
          </tr>

          <tr>
            <td align="left" bgcolor="#ffffff"
              style="padding:24px 24px 0; font-family: Source Sans Pro, Helvetica, Arial, sans-serif;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 600; letter-spacing: -1px; line-height: 48px;">Pemberitahuan Ada lamaran!</h1>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td align="center" bgcolor="#f2f4f6" valign="top" width="100%">
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%"
          style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 100%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:500px;">
                  <tr>
                    <td align="left" bgcolor="#ffffff"
                      style="padding: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p style="margin-bottom: 10px;">Ada Pelamar bernama ' . $name . ' .</p>
                      <p style="margin-bottom: 10px;">Melamar di bagian ' . $career_category . ' dengan posisi
                      ' . $career_title . '.</p>
                      <p style="margin-bottom: 10px;">Dengan data sebagai berikut.</p>
                      <p style="margin-bottom: 10px;">E-mail: ' . $email . ' .</p>
                      <p style="margin-bottom: 10px;">Nomor Telepon ' . $phone . ' .</p>
                      <p style="margin-bottom: 10px;">Silahkan buka portal admin untuk menindak lanjuti lamaran.</p>
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
      <td align="center" bgcolor="#f2f4f6">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 50%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250;">
                  <tr>
                    <td align="left" border="1" valign="top"
                      style="padding-bottom: 24px; padding-left: 15px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">

                      <p>Copyright 2020 © PT. Rahmat Tuhan Lestari</p>
                      <p>Hak Cipta Dilindungi Undang-undang</p>
                    </td>
                  </tr>
                </table>
              </div>

              <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                  <tr>
                    <td align="left" valign="top"
                      style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p><strong>Ketahui info terbaru ur</strong></p>
                      <a href="https://www.facebook.com/UR-Easy-Quick-Order-101245531496672/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/fb.png" alt="UR Facebook"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://www.instagram.com/ur.hub/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/ig.png" alt="UR Instagram"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://id.linkedin.com/company/ur-hub?trk=public_profile_topcard_current_company">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/linkedin.png" alt="UR Linkedin"
                          border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px;">
                      </a>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>


        </table>

      </td>
    </tr>

  </table>

</body>

</html>
    ';

    $mailer = new Mailer();
    if ($mailer->sendMessage(
      "recruitment@ur-hub.com",
      "Pemberitahuan ada Lamaran",
      $html
    )) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function mailingApplicantToSender($name, $email)
  {
    $html = '
    <!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>UR Applicant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /**
   * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
   */
    @media screen {
      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 400;
        src: local("Source Sans Pro Regular"), local("SourceSansPro-Regular"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format("woff");
      }

      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 600;
        src: local("Source Sans Pro Bold"), local("SourceSansPro-Bold"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format("woff");
      }
    }


    /**
   * Avoid browser level font resizing.
   * 1. Windows Mobile
   * 2. iOS / OSX
   */
    body,
    table,
    td,
    a {
      -ms-text-size-adjust: 100%;
      /* 1 */
      -webkit-text-size-adjust: 100%;
      /* 2 */
    }

    /**
   * Remove extra space added to tables and cells in Outlook.
   */
    table,
    td {
      mso-table-rspace: 0pt;
      mso-table-lspace: 0pt;
    }

    /**
   * Better fluid images in Internet Explorer.
   */
    img {
      -ms-interpolation-mode: bicubic;
    }

    /**
   * Remove blue links for iOS devices.
   */
    a[x-apple-data-detectors] {
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      color: inherit !important;
      text-decoration: none !important;
    }

    /**
   * Fix centering issues in Android 4.4.
   */
    div[style*="margin: 16px 0;"] {
      margin: 0 !important;
    }

    body {
      width: 100% !important;
      height: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /**
   * Collapse table borders to avoid space between cells.
   */
    table {
      border-collapse: collapse !important;
    }

    a {
      color: #1a82e2;
    }

    img {
      height: auto;
      line-height: 100%;
      text-decoration: none;
      border: 0;
      outline: none;
    }

    .fa {
      padding: 5px;
      font-size: 30px;
      width: 30px;
      text-align: center;
      text-decoration: none;
      margin: 5px 2px;
    }

    .fa:hover {
      opacity: 0.7;
    }

    .fa-facebook {
      background: #3B5998;
      color: white;
    }

    .fa-instagram {
      background: #125688;
      color: white;
    }

    .fa-linkedin {
      background: #007bb5;
      color: white;
    }

    /* .t1{
    background-color: red;
  }

  @media only screen and (max-width: 600px) {
  .t1{
    background-color: lightblue;
  } */
    }
  </style>

</head>

<body>

  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center" bgcolor="#f2f4f6">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" bgcolor="#ffffff">
              <a href="https://ur-hub.com">
                <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-blue-boxless.png" alt="Logo"
                  border="0" width="48" style="width: 48px; max-width: 48px; min-width: 48px;">
              </a>
            </td>
          </tr>

          <tr>
            <td align="left" bgcolor="#ffffff"
              style="padding:24px 24px 0; font-family: Source Sans Pro, Helvetica, Arial, sans-serif;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 600; letter-spacing: -1px; line-height: 48px;">Terima Kasih telah mengirim pesan.</h1>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td align="center" bgcolor="#f2f4f6" valign="top" width="100%">
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%"
          style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 100%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:500px;">
                  <tr>
                    <td align="left" bgcolor="#ffffff"
                      style="padding: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p style="margin-bottom: 10px;">Terima kasih Bapak/Ibu ' . $name . ' telah mengirim pesan pada kami.</p>
                      <p style="margin-bottom: 10px;">Kami akan membacanya.</p>
                      <p style="margin-bottom: 10px;">Salam,</p>
                      <p style="margin: 0;"><a href="https://ur-hub.com/">UR </a> Support Team </p>
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
      <td align="center" bgcolor="#f2f4f6">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 50%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250;">
                  <tr>
                    <td align="left" border="1" valign="top"
                      style="padding-bottom: 24px; padding-left: 15px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">

                      <p>Copyright 2020 © PT. Rahmat Tuhan Lestari</p>
                      <p>Hak Cipta Dilindungi Undang-undang</p>
                    </td>
                  </tr>
                </table>
              </div>

              <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                  <tr>
                    <td align="left" valign="top"
                      style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p><strong>Ketahui info terbaru ur</strong></p>
                      <a href="https://www.facebook.com/UR-Easy-Quick-Order-101245531496672/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/fb.png" alt="UR Facebook"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://www.instagram.com/ur.hub/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/ig.png" alt="UR Instagram"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://id.linkedin.com/company/ur-hub?trk=public_profile_topcard_current_company">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/linkedin.png" alt="UR Linkedin"
                          border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px;">
                      </a>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>


        </table>

      </td>
    </tr>

  </table>

</body>

</html>
    ';

    $mailer = new Mailer();
    if ($mailer->sendMessage(
      $email,
      "Terima kasih telah Mengirim Pesan",
      $html
    )) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }
  public function mailingApplicantToSupport($name, $email, $message, $date)
  {
    $html = '
    <!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>UR Applicant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /**
   * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
   */
    @media screen {
      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 400;
        src: local("Source Sans Pro Regular"), local("SourceSansPro-Regular"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format("woff");
      }

      @font-face {
        font-family: "Source Sans Pro";
        font-style: normal;
        font-weight: 600;
        src: local("Source Sans Pro Bold"), local("SourceSansPro-Bold"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format("woff");
      }
    }


    /**
   * Avoid browser level font resizing.
   * 1. Windows Mobile
   * 2. iOS / OSX
   */
    body,
    table,
    td,
    a {
      -ms-text-size-adjust: 100%;
      /* 1 */
      -webkit-text-size-adjust: 100%;
      /* 2 */
    }

    /**
   * Remove extra space added to tables and cells in Outlook.
   */
    table,
    td {
      mso-table-rspace: 0pt;
      mso-table-lspace: 0pt;
    }

    /**
   * Better fluid images in Internet Explorer.
   */
    img {
      -ms-interpolation-mode: bicubic;
    }

    /**
   * Remove blue links for iOS devices.
   */
    a[x-apple-data-detectors] {
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      color: inherit !important;
      text-decoration: none !important;
    }

    /**
   * Fix centering issues in Android 4.4.
   */
    div[style*="margin: 16px 0;"] {
      margin: 0 !important;
    }

    body {
      width: 100% !important;
      height: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /**
   * Collapse table borders to avoid space between cells.
   */
    table {
      border-collapse: collapse !important;
    }

    a {
      color: #1a82e2;
    }

    img {
      height: auto;
      line-height: 100%;
      text-decoration: none;
      border: 0;
      outline: none;
    }

    .fa {
      padding: 5px;
      font-size: 30px;
      width: 30px;
      text-align: center;
      text-decoration: none;
      margin: 5px 2px;
    }

    .fa:hover {
      opacity: 0.7;
    }

    .fa-facebook {
      background: #3B5998;
      color: white;
    }

    .fa-instagram {
      background: #125688;
      color: white;
    }

    .fa-linkedin {
      background: #007bb5;
      color: white;
    }

    /* .t1{
    background-color: red;
  }

  @media only screen and (max-width: 600px) {
  .t1{
    background-color: lightblue;
  } */
    }
  </style>

</head>

<body>

  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center" bgcolor="#f2f4f6">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" bgcolor="#ffffff">
              <a href="https://ur-hub.com">
                <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-blue-boxless.png" alt="Logo"
                  border="0" width="48" style="width: 48px; max-width: 48px; min-width: 48px;">
              </a>
            </td>
          </tr>

          <tr>
            <td align="left" bgcolor="#ffffff"
              style="padding:24px 24px 0; font-family: Source Sans Pro, Helvetica, Arial, sans-serif;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 600; letter-spacing: -1px; line-height: 48px;">Pemberitahuan Ada Pesan!</h1>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td align="center" bgcolor="#f2f4f6" valign="top" width="100%">
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%"
          style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 100%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:500px;">
                  <tr>
                    <td align="left" bgcolor="#ffffff"
                      style="padding: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p style="margin-bottom: 10px;">Tanggal ' . $date . ' .</p>
                      <p style="margin-bottom: 10px;">Ada pesan dari:  ' . $name . ' .</p>
                      <p style="margin-bottom: 10px;">E-mail : ' . $email . ' .</p>
                      <p style="margin-bottom: 10px;">Dengan pesan berikut: </p>
                      <p style="margin-bottom: 10px;"> ' . $message . ' .</p>
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
      <td align="center" bgcolor="#f2f4f6">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr>
            <td align="center" valign="top" style="font-size: 0;">
              <div style="display: inline-block; width: 100%; max-width: 50%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250;">
                  <tr>
                    <td align="left" border="1" valign="top"
                      style="padding-bottom: 24px; padding-left: 15px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">

                      <p>Copyright 2020 © PT. Rahmat Tuhan Lestari</p>
                      <p>Hak Cipta Dilindungi Undang-undang</p>
                    </td>
                  </tr>
                </table>
              </div>

              <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                  <tr>
                    <td align="left" valign="top"
                      style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p><strong>Ketahui info terbaru ur</strong></p>
                      <a href="https://www.facebook.com/UR-Easy-Quick-Order-101245531496672/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/fb.png" alt="UR Facebook"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://www.instagram.com/ur.hub/">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/ig.png" alt="UR Instagram"
                          border="0" width="20px"
                          style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://id.linkedin.com/company/ur-hub?trk=public_profile_topcard_current_company">
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/linkedin.png" alt="UR Linkedin"
                          border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px;">
                      </a>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>


        </table>

      </td>
    </tr>

  </table>

</body>

</html>
    ';

    $mailer = new Mailer();
    if ($mailer->sendMessage(
      "support@ur-hub.com",
      "Pemberitahuan ada Pesan",
      $html
    )) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function forgotPasswordQueue($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'partner';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT name, phone, email FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Lupa Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
                      color: #FFF;
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
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
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
                      color: #A8AAAF;
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
                      padding: 45px;
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
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hai ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Queue account. gunakan tombol di bawah ini untuk mengubahnya.</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/changePasswordQueue.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Thanks,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
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
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
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
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
}
