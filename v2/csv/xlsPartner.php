<html>
<head>
	<title>Laporan Transaksi</title>
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
     $dateFormat = date('d-M-Y');
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Transaksi_\"$dateFormat\".xls");
   require '../db_connection.php';
    $id = $_GET['id'];
     $dateNow = date('d M Y');
     $first_day_last_month = date('01 M Y', strtotime('-1 month')); // hard-coded '01' for first day
     $last_day_last_month  = date('t M Y', strtotime('-1 month'));
     $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
     $dateLastDb = date('Y-m-t', strtotime('-1 month'));
    //  $first_day_last_month = date('01 M Y'); // hard-coded '01' for first day
    //  $last_day_last_month  = date('t M Y');
    //  $dateFirstDb = date('Y-m-01');
    //  $dateLastDb = date('Y-m-t');
    $partner = mysqli_query($db_conn, "SELECT name FROM partner WHERE MD5(id)='$id';");
    while($row = mysqli_fetch_array($partner)){
        $name=$row['name'];
    }
	?>
 	<center>
        <h1>Laporan Transaksi <br><?php echo $name ?></h1>
        
	</center>
  <b>Periode: <?php echo $first_day_last_month;?> sampai dengan <?php echo $last_day_last_month;?><b>
	<table border="1">
		<tr>
            <th>No</th>
			<th>ID</th>
            <th>Jam</th>
            <th>Partner</th>
			<th>No Meja</th>
            <th>Tipe Bayar</th>
            <th>Promo</th>
            <th>Total</th>
		</tr>
        <?php 
        function rupiah($angka)
        {
          $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
          return $hasil_rupiah;
        }
		// koneksi database
		$trans = mysqli_query($db_conn, "SELECT transaksi.*,partner.name as namaPartner FROM transaksi join partner ON transaksi.id_partner=partner.id WHERE MD5(partner.id)='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
    $no = 1;
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
    $withTx=0;
		while($d = mysqli_fetch_array($trans)){
      $charge_ewallet=$d['charge_ewallet'];
      $sumTotal+=$d['total']-$d['promo'];
      $sumCharge_ur+=$d['charge_ur'];
      $taxtype=$d['tax'];
      $servicetype=$d['service'];
      $countService=0;
      $withService=0;
      $countTax=0;
      $withTax=0;
      if($d['tipe_bayar']==1 || $d['tipe_bayar']=='1'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService+=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $ovo+=$withTax;
      }else if($d['tipe_bayar']==2 || $d['tipe_bayar']=='2'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService+=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $gopay+=$withTax;
      }else if($d['tipe_bayar']==3 || $d['tipe_bayar']=='3'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService+=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $dana+=$withTax;
      }else if($d['tipe_bayar']==4 || $d['tipe_bayar']=='4'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService+=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $linkaja+=$withTax;
      }else if($d['tipe_bayar']==5 || $d['tipe_bayar']=='5'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService+=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $tunaiDebit+=$withTax;
      }else if($d['tipe_bayar']==6 || $d['tipe_bayar']=='6'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService+=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $sakuku+=$withTax;
      }else if($d['tipe_bayar']==7 || $d['tipe_bayar']=='7'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $creditCard+=$withTax;
      }else if($d['tipe_bayar']==8 || $d['tipe_bayar']=='8'){
        $countService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $sumService=ceil(($d['total']-$d['promo'])*($d['service']/100));
        $withService=($d['total']-$d['promo'])+$countService+$d['charge_ur'];
        $countTax=ceil($withService*($d['tax']/100));
        $sumTax+=ceil($withService*($d['tax']/100));
        $withTax=$withService+$countTax;
        $debitCard+=$withTax;
      }
      $withTx+=$withTax;
      switch ($d['tipe_bayar']) {
        case 1:
          $type = 'OVO';
          break;
        case 2:
          $type = 'GOPAY';
          break;
        case 3:
          $type = 'DANA';
          break;
        case 4:
          $type = 'T-CASH';
          break;
        case 5:
          $type = 'TUNAI';
          break;
        case 6:
          $type = 'SAKUKU';
          break;
        case 7:
          $type = 'CREDIT CARD';
          break;
        case 7:
          $type = 'DEBIT CARD';
          break;
        
        default:
          $type = 'ALL PAYMENT';
      }
		?>
		<tr>
			<td><?php echo $no++; ?></td>
			<td><?php echo $d['id']; ?></td>
			<td><?php echo $d['jam']; ?></td>
			<td><?php echo $d['no_meja']; ?></td>
      <td><?php echo $d['namaPartner']; ?></td>
            <td><?php echo $type; ?></td>
            <td><?php echo '-'.rupiah($d['promo']); ?></td>
            <td><?php echo rupiah($d['total']); ?></td>
		</tr>
		<?php 
    }
    // $subtotalAll =(($ovo+$gopay+$dana+$linkaja+$sakuku)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)))+$tunaiDebit+$creditCard);
    $subtotalAll = ($sumTotal+$sumService+$sumTax)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)));
		?>
    <tr>
    <td></td><td></td><td></td><td></td><td></td><td></td><td><b>SUBTOTAL</b></td><td><?php echo rupiah($sumTotal)?></td>
    </tr>
    <tr>
    <td></td><td></td><td></td><td></td><td></td><td></td><td><b>Service</b></td><td><?php echo rupiah($sumService)?></td>
    </tr>
    <tr>
    <td></td><td></td><td></td><td></td><td></td><td></td><td><b>Tax</b></td><td><?php echo rupiah($sumTax)?></td>
    </tr>
    <tr>
    <td></td><td></td><td></td><td></td><td></td><td></td><td><b>CHARGE E-WALLET</b></td><td><?php echo '-'.rupiah(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)))?></td>
    </tr>
    <tr>
    <td></td><td></td><td></td><td></td><td></td><td></td><td><b>Convenience Fee</b></td><td><?php echo '-'.rupiah($sumCharge_ur)?></td>
    </tr>
    <tr>
    <td></td><td></td><td></td><td></td><td></td><td></td><td><b>TOTAL</b></td><td><?php echo rupiah($subtotalAll)?></td>
    </tr>
	</table>
<br><br>
  <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">
                                              
                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
  <?php 
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
    $sumtax=0;
    $sumservice=0;
    $sum=0;
    $sumewallet=0;
    $sumecash=0;
    $tipe_bayar = mysqli_query($db_conn, "SELECT transaksi.total,transaksi.promo ,transaksi.tax,transaksi.service,transaksi.status,transaksi.tipe_bayar,transaksi.charge_ewallet,transaksi.charge_ur,partner.name AS namaPartner FROM transaksi join partner on transaksi.id_partner=partner.id WHERE MD5(partner.id)='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
    while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
      $charge_ewallet = $rowtypeBayar['charge_ewallet'];
      $sumCharge_ur  += $rowtypeBayar['charge_ur'];
      $taxtype        = $rowtypeBayar['tax'];
      $servicetype    = $rowtypeBayar['service'];
      $countService   = 0;
      $withService    = 0;
      $countTax       = 0;
      $withTax        = 0;

      $subtotalTransaction = $rowtypeBayar['total'] - $rowtypeBayar['promo'];
      $sumtotal+=$rowtypeBayar['total'] - $rowtypeBayar['promo'];
      $countService = ceil($subtotalTransaction * ($rowtypeBayar['service'] / 100));
      $sumservice+= ceil($subtotalTransaction * ($rowtypeBayar['service'] / 100));
      $withService = $subtotalTransaction + $countService + $rowtypeBayar['charge_ur'];
      $countTax= ceil($withService * ($rowtypeBayar['tax'] / 100));
      $sumtax+= ceil($withService * ($rowtypeBayar['tax'] / 100));
      $withTax = $withService + $countTax;
      $sum+=$withTax;
      if($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
        $ovo += $withTax;
        $sumewallet+=$withTax;
      } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] =='2') {
        $gopay += $withTax;
        $sumewallet+=$withTax;
      }else if($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] =='3'){
        $dana += $withTax;
        $sumewallet+=$withTax;
      }else if($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4'){
        $linkaja += $withTax;
        $sumewallet+=$withTax;
      }else if($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5'){
        $tunaiDebit += $withTax;
        $sumecash+=$withTax;
      }else if($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6'){
        $sakuku += $withTax;
        $sumewallet+=$withTax;
      }else if($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7'){
        $creditCard += $withTax;
        $sumecash+=$withTax;
      }else if($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8'){
        $debitCard += $withTax;
        $sumecash+=$withTax;
      }

    }
    // echo $sumtotal.'<br>';
    // echo $sumservice.'<br>';
    // echo $sumtax.'<br>';
    // echo $sum.'<br>';
    // echo $sum.'<br>';
    // echo $sumewallet.'<br>';
    // echo $sumecash.'<br>';

  ?>
  <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
          </tr>
          <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($ovo) ?></span></td>
          </tr>
          <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($gopay) ?></span></td>
          </tr>
            <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($dana)?></span></td>
          </tr>
          <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback">Charge E-Wallet (<?php echo $charge_ewallet ?> % + <?php echo $taxtype ?>%)</span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo '-'.rupiah(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100))) ?></span></td>
        </tr>
        <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah(($ovo+$gopay+$dana+$linkaja+$sakuku)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100))))?></span></td>
      </tr>
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
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($tunaiDebit) ?></span></td>
          </tr>
          <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($creditCard) ?></span></td>
        </tr>
        <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($debitCard) ?></span></td>
        </tr>
        <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
      </tr>
      <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah(($ovo+$gopay+$dana+$linkaja+$sakuku)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)))+$tunaiDebit+$creditCard+$debitCard) ?></span></td>
    </tr>
    <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
      </tr>
      <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah($sumCharge_ur) ?></span></td>
    </tr>
    <tr>
    <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
  </tr>
  <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"><?php echo rupiah((($ovo+$gopay+$dana+$linkaja+$sakuku)-(ceil(($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)+(ceil((($ovo+$gopay+$dana+$linkaja+$sakuku)*$charge_ewallet/100)*$taxtype/100)))+$tunaiDebit+$creditCard+$debitCard)-$sumCharge_ur) ?></span></td>
    </tr>
    </table>
  </td>
</tr>
</table>

</body>
</html>