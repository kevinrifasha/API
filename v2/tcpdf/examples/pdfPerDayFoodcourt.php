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

function rupiah($angka){
	
	$hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
	return $hasil_rupiah;
 
}

// Include the main TCPDF library (search for installation path).
require_once('tcpdf_include.php');
require '../../db_connection.php';
//setup db
$id = $_GET['id'];
$dateNow = date('d M Y');
$first_day_last_month = date('01/M/Y', strtotime('-1 month')); // hard-coded '01' for first day
$last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
$dateFirstDb = date('Y-m-01', strtotime('-1 month'));
$dateLastDb = date('Y-m-t', strtotime('-1 month'));
// $first_day_last_month = date('01/M/Y');
// $last_day_last_month  = date('t/M/Y');
// $dateFirstDb = date('Y-m-01');
// $dateLastDb = date('Y-m-t');

$info = mysqli_query($db_conn, "SELECT * FROM foodcourt WHERE MD5(id)='$id';");
while($rowinfo=mysqli_fetch_assoc($info)){
  $name=$rowinfo['name'];
  $jamBuka=$rowinfo['jam_buka'];
  $jamTutup=$rowinfo['jam_tutup'];
}

$menu = mysqli_query($db_conn, "SELECT menu.nama,menu.harga,menu.hpp,SUM(transaksi_detail_tenant.qty*transaksi_detail_tenant.harga_satuan) AS total_harga,SUM(transaksi_detail_tenant.qty) as qtyTotal FROM menu join transaksi_detail_tenant on menu.id=transaksi_detail_tenant.id_menu JOIN transaksi_tenant ON transaksi_detail_tenant.id_transaksi_tenant=transaksi_tenant.id join transaksi_foodcourt on transaksi_tenant.id_transaksi_fc=transaksi_foodcourt.id WHERE MD5(transaksi_foodcourt.id_foodcourt)='$id' AND transaksi_foodcourt.status<=2 AND transaksi_foodcourt.status>=1 AND DATE(transaksi_foodcourt.created)='$dateNowDb' AND TIME(transaksi_foodcourt.created_at) BETWEEN '$jamBuka' AND '$jamTutup' GROUP BY menu.nama order by SUM(transaksi_detail_tenant.qty) DESC");
$transaksi = mysqli_query($db_conn, "SELECT transaksi_foodcourt.* FROM transaksi_foodcourt WHERE MD5(transaksi_foodcourt.id_foodcourt)='$id' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status>=1 AND DATE(jam)='$dateNowDb' AND TIME(jam) BETWEEN '$jamBuka' AND '$jamTutup'");


// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Ur Hub');
$pdf->SetTitle('Laporan Keuangan');
$pdf->SetSubject('Laporan Keuangan');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,0,0), array(0,0,0));
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
$pdf->SetFont('dejavusans', '', 10, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

$total=0;
$promo=0;
$ovo=0;
$gopay=0;
$dana=0;
$linkaja=0;
$tunaiDebit=0;
$sakuku=0;
$creditCard=0;  
$debitCard=0;
$charge_ewallet=0;                      
$taxtype=0;
$sumCharge_ur=0;
$sumTax=0;
$sumService=0;
// Set some content to print
while($row=mysqli_fetch_assoc($transaksi)){
	$charge_ewallet=$row['charge_ewallet'];
	$sumTotal+=$row['total'];
	$sumCharge_ur+=$row['charge_ur'];
	$taxtype=$row['tax'];
	$servicetype=$row['service'];
	$countService=0;
	$withService=0;
	$countTax=0;
	$withTax=0;
	if($row['tipe_bayar']==1 || $row['tipe_bayar']=='1'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService+=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$ovo+=$withTax;
	  }else if($row['tipe_bayar']==2 || $row['tipe_bayar']=='2'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService+=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$gopay+=$withTax;
	  }else if($row['tipe_bayar']==3 || $row['tipe_bayar']=='3'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService+=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$dana+=$withTax;
	  }else if($row['tipe_bayar']==4 || $row['tipe_bayar']=='4'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService+=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$linkaja+=$withTax;
	  }else if($row['tipe_bayar']==5 || $row['tipe_bayar']=='5'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService+=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$tunaiDebit+=$withTax;
	  }else if($row['tipe_bayar']==6 || $row['tipe_bayar']=='6'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService+=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$sakuku+=$withTax;
	  }else if($row['tipe_bayar']==7 || $row['tipe_bayar']=='7'){
		$countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$sumService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		$withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		$countTax=ceil($withService*($row['tax']/100));
		$sumTax+=ceil($withService*($row['tax']/100));
		$withTax=$withService+$countTax;
		$creditCard+=$withTax;
	  } else if($row['tipe_bayar']==8 || $row['tipe_bayar']=='8'){
		  $countService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		  $sumService=ceil(($row['total']-$row['promo'])*($row['service']/100));
		  $withService=($row['total']-$row['promo'])+$countService+$row['charge_ur'];
		  $countTax=ceil($withService*($row['tax']/100));
		  $sumTax+=ceil($withService*($row['tax']/100));
		  $withTax=$withService+$countTax;
		  $debitCard+=$withTax;
	  }
  }
  $hargaPokok=0;
//   $subtotalAll =(($ovo+$gopay+$dana+$linkaja+$sakuku)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)))+$tunaiDebit+$creditCard);
  $subtotalAll = $sumTotal+$sumService+$sumTax-$sumCharge_ur-ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100));
  $menu2 = mysqli_query($db_conn, "SELECT partner.name as nama_partner,menu.nama,menu.harga,menu.hpp,SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) AS total_harga,SUM(detail_transaksi.qty) as qtyTotal FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE MD5(partner.id)='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' AND TIME(jam) BETWEEN '$jamBuka' AND '$jamTutup' GROUP BY menu.nama order by SUM(detail_transaksi.qty) DESC");
  while($rowcheck1=mysqli_fetch_assoc($menu2)){
	$hargaPokok += $rowcheck1['hpp'];
  }
  $html = '<p style = "text-align:right;font-size:8">Tanggal:'.$dateNow.'</h1>';
  $html .= '<h1 style = "text-align:center;margin-top:-10px;">Laporan Keuangan <br> </h1>';
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
  
  $html .='<tr><td>   Penjualan</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($sumTotal).'</td></tr>';
  $html .='<tr><td style="text-align:right">Subtotal</td><td style="text-align:right">'.rupiah($sumTotal).'</td></tr>';
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
	
	$html .='<tr><td>   Beban Voucher</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($promo).'</td></tr>';
	$html .='<tr><td style="text-align:right">Subtotal</td><td style="text-align:right">'.rupiah($promo).'</td></tr>';
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
  
	$html .='<tr><td>   Tax:</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($sumTax).'</td></tr>';
	$html .='<tr><td>   Service:</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($sumService).'</td></tr>';
	$html .='<tr><td>   Charge E-wallet:</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100))).'</td></tr>';
	$html .='<tr><td>   Convenience Fee :</td><td style="text-align:right;border-bottom-width:thin;">'.rupiah($sumCharge_ur).'</td></tr>';
	$html .='<tr><td style="text-align:right">Subtotal</td><td style="text-align:right">'.rupiah($sumTax+$sumService-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100))+$sumCharge_ur)).'</td></tr>'; 
	$html .='<tr><td></td></tr>';
	$html .='<tr><td style="text-align:right">Total</td><td style="text-align:right;border-top-width:thin">'.rupiah($subtotalAll).'</td></tr>';  
  $html .="</table><br><br>";

  $html .='<br pagebreak="true">';
  $html .= '<h3>ALL Menu</h3>'; 
  
  $html .= '
  <table border="1" cellspacing="0" cellpadding="3">
  <thead>
  <tr>
  <th width="5%"><strong>No</strong></th>
  <th width="15%"><strong>Code</strong></th>
  <th width="23%"><strong>Menu</strong></th>
  <th width="17%"><strong>HPP</strong></th>              
  <th width="17%"><strong>Harga</strong></th>
  <th width="8%"><strong>Jmlh. Terjual</strong></th>
  <th width="19%"><strong>Total</strong></th>
  </tr>                           
  </thead>
  <tbody>';
  
  $n=1;
  $subtotal=0;
  while($rowmenu=mysqli_fetch_assoc($menu)){
	// $namaPartner=$rowmenu['namaPartner'];  
	// $idMenu=$rowmenu['id'];
	// $namaMenu=$rowmenu['nama'];
	// $harga=$rowmenu['harga'];
	// $hpp=$rowmenu['hpp'];
	// $detail = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenu' AND transaksi.status = 2 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb';"); 
	// while($rowdetail=mysqli_fetch_assoc($detail)){
	// 	if($rowdetail['qtytotal']==''){
    //         $qty = 0;
    //       }else{
    //         $qty = $rowdetail['qtytotal'];
    //       }
	// }
	$subtotal += ($rowmenu['total_harga']);
	$html .='<tr nobr="true"><td width="5%">'.$n.'</td><td width="15%">'.$rowmenu['nama_partner'].'</td><td width="23%">'.$rowmenu['nama'].'</td><td width="17%">'.rupiah($rowmenu['hpp']).'</td><td width="17%">'.rupiah($rowmenu['harga']).'</td><td width="8%">'.$rowmenu['qtyTotal'].'</td><td width="19%">'.rupiah($rowmenu['total_harga']).' </td></tr>';
	$n++;
  }
  $html .="</table>";
  $html .= '<table border="0" cellspacing="0" cellpadding="3">';
  $html .='<tr><td width="5%"></td><td width="7%"></td><td width="25%"></td><td width="13%"></td><td width="16%"></td><td width="19%">Sub Total</td><td width="21%">'.rupiah($subtotal).' </td></tr>';
  // $html .='<tr><td width="5%"></td><td width="32%">PPN</td><td width="32%">10%</td><td width="32%">'.($ttlnoppn*0.1).'</td></tr>';
  // $html .='<tr><td width="5%"></td><td width="32%">Subtotal</td><td width="32%"></td><td width="32%">'.(($ttlnoppn*0.1)+$ttlnoppn).'</td></tr>';
  $html .="</table>"; 
// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('Laporan Keuangan.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
