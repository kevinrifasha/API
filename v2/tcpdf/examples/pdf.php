<?php
//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 001 for TCPDF class
//               Default Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require '../../db_connection.php';
require_once('tcpdf_include.php');
$id = $_GET['id'];
$first_day_last_month = date('01/M/Y', strtotime('-1 month')); // hard-coded '01' for first day
$last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
$dateFirstDb = date('Y-m-01', strtotime('-1 month'));
$dateLastDb = date('Y-m-t', strtotime('-1 month'));
$info = mysqli_query($db_conn, "SELECT * FROM partner WHERE MD5(id)='$id';");
while($rowinfo=mysqli_fetch_assoc($info)){
  $name=$rowinfo['name'];
}
$menu = mysqli_query($db_conn, "SELECT * FROM menu WHERE MD5(id_partner)='$id';");


$transaksi = mysqli_query($db_conn, "SELECT SUM(total-promo) AS tottotal, SUM(promo) AS totpromo FROM transaksi  WHERE MD5(id_partner)='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN $dateFirstDb AND $dateLastDb");

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ur-hub');
$pdf->SetTitle('Finance Report from UR HUB');
$pdf->SetSubject('Finance Report');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,64,255), array(0,64,128),);

$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 12, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
// Set some content to print

//$html =" 
//<h1>Laporan Keuangan $id</h1>"
//;
function rupiah($angka){
	
	$hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
	return $hasil_rupiah;
 
}
while($row=mysqli_fetch_assoc($transaksi)){
  $total = $row['tottotal'];
  $promo = $row['totpromo'];
}
$hargaPokok=0;
$menu2 = mysqli_query($db_conn, "SELECT * FROM menu WHERE MD5(id_partner)='$id';");
while($rowcheck1=mysqli_fetch_assoc($menu2)){
  $idMenuCheck=$rowcheck1['id'];
  $hargaPokokAwal=$rowcheck1['hpp'];
  $detailcheck = mysqli_query($db_conn, "SELECT COUNT(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenuCheck' AND DATE(jam) BETWEEN $dateFirstDb AND $dateLastDb;"); 
  while($rowpph=mysqli_fetch_assoc($detailcheck)){
    $qtyJual = $rowpph['qtytotal'];
  }
  $hargaPokok += $hargaPokokAwal*$qtyJual;
}
$html = '<p style = "text-align:right;font-size:8">Periode: '.$first_day_last_month.' - '.$last_day_last_month.' </h1>';
$html .= '<h1 style = "text-align:center;margin-top:-10px;">Laporan Keuangan <br>'.$name.' </h1>';
$html .= '<b>Pendapatan<b><br>'; 
  $html .= '
  <table border="1" cellspacing="0" cellpadding="3" border ="0">

  <thead>
  <tr>
  <th ><strong>Pendapatan Usaha</strong></th>
  <th style="text-align:right"><strong></strong></th>       
  </tr>                           
  </thead>
  <tbody> 
  ';

$html .='<tr><td>   Penjualan</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($total+($total*0.1)).'</td></tr>';
$html .='<tr><td style="text-align:right">Total</td><td style="text-align:right">'.rupiah($total+($total*0.1)).'</td></tr>';
$html .="</table><br><br>";

$html .= '<b>Biaya atas Pendapatan<b><br>'; 
  $html .= '
  <table border="1" cellspacing="0" cellpadding="3" border ="0">

  <thead>
  <tr>
  <th ><strong>Biaya Produksi</strong></th>
  <th style="text-align:right"><strong></strong></th>       
  </tr>                           
  </thead>
  <tbody> 
  ';
  $html .='<tr><td>   Harga Pokok Penjualan</td><td style="text-align:right">'.rupiah($hargaPokok).'</td></tr>';
  $html .='<tr><td>   Beban Voucher</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($promo).'</td></tr>';
  $html .='<tr><td style="text-align:right">Total</td><td style="text-align:right">'.rupiah($hargaPokok+$promo).'</td></tr>';
  $html .="</table><br><br>";
  $html .= '
  <table border="1" cellspacing="0" cellpadding="3" border ="0">

  <thead>
  <tr>
  <th ><strong>Biaya Lain-lain</strong></th>
  <th style="text-align:right"><strong></strong></th>       
  </tr>                           
  </thead>
  <tbody> 
  ';

  $html .='<tr><td>   PPN:10%</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($total*0.1).'</td></tr>';
  $html .='<tr><td style="text-align:right">Total</td><td style="text-align:right">'.rupiah($total*0.1).'</td></tr>';  
  $html .='<tr><td></td></tr>';
  $html .='<tr><td style="text-align:right">SubTotal</td><td style="text-align:right;border-top-width:thin">'.rupiah((($total+($total*0.1)) - ($hargaPokok+$promo)) -($total*0.1)).'</td></tr>';  
$html .="</table><br><br>";
$html .='<br pagebreak="true">';
$html .= '<h3>ALL Menu</h3>'; 

$html .= '
<table border="1" cellspacing="0" cellpadding="3">
<thead>
<tr>
<th width="5%"><strong>No</strong></th>
<th width="7%"><strong>Code</strong></th>
<th width="25%"><strong>Menu</strong></th>
<th width="18%"><strong>HPP</strong></th>              
<th width="18%"><strong>Harga</strong></th>
<th width="7%"><strong>Qty</strong></th>
<th width="21%"><strong>Total</strong></th>
</tr>                           
</thead>
<tbody>';

$n=1;
$subtotal=0;
while($rowmenu=mysqli_fetch_assoc($menu)){
  $idMenu=$rowmenu['id'];
  $namaMenu=$rowmenu['nama'];
  $harga=$rowmenu['harga'];
  $hpp=$rowmenu['hpp'];
  $detail = mysqli_query($db_conn, "SELECT COUNT(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenu';"); 
  while($rowdetail=mysqli_fetch_assoc($detail)){
    $qty = $rowdetail['qtytotal'];
  }
  $subtotal += (($harga-$hpp)*$qty);
  $html .='<tr nobr="true"><td width="5%">'.$n.'</td><td width="7%">'.$idMenu.'</td><td width="25%">'.$namaMenu.'</td><td width="18%">'.rupiah($hpp).'</td><td width="18%">'.rupiah($harga).'</td><td width="7%">'.$qty.'</td><td width="21%">'.rupiah(($harga-$hpp)*$qty).' </td></tr>';
  $n++;
}
$html .="</table>";
$html .= '<table border="0" cellspacing="0" cellpadding="3">';
$html .='<tr><td width="5%"></td><td width="7%"></td><td width="25%"></td><td width="13%"></td><td width="14%"></td><td width="16%">Sub Total</td><td width="21%">'.rupiah($subtotal).' </td></tr>';
// $html .='<tr><td width="5%"></td><td width="32%">PPN</td><td width="32%">10%</td><td width="32%">'.($ttlnoppn*0.1).'</td></tr>';
// $html .='<tr><td width="5%"></td><td width="32%">Subtotal</td><td width="32%"></td><td width="32%">'.(($ttlnoppn*0.1)+$ttlnoppn).'</td></tr>';
$html .="</table>";
// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('finacial_report.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
