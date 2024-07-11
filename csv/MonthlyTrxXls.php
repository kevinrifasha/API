<html>
<head>
	<title>Laporan Penjualan</title>
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
      $dateFormat = "";
      $id = $_GET['partnerID'];
      $dateEnd = $_GET['dateEnd'];
      $dateStart = $_GET['dateStart'];
      header("Content-Type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=Laporan_Penjualan_\"$dateFormat\".xls");
      require '../db_connection.php';
      require_once '../includes/CalculateFunctions.php';
      require '../includes/functions.php';
      $cf = new CalculateFunction();
      $fs = new functions();
      
      $fID="";
      $name="";
      $jamBuka="";
      $jamTutup="";
      $partnerQ = mysqli_query($db_conn, "SELECT id,name,jam_buka,jam_tutup FROM partner WHERE MD5(id)='$id'");
      while($row = mysqli_fetch_array($partnerQ)){
          $fID=$row['id'];
          $name=$row['name'];
          $jamBuka=$row['jam_buka'];
          $jamTutup=$row['jam_tutup'];
      }
      // $dateNow = dateEnd('d m Y', strtotime($dateEnd));
      $dateFormat = $dateEnd;
      $dateStartNowDb = $dateStart;
      $dateEndNowDb = $dateEnd;

      
      function rupiah($angka)
      {
        $hasil_rupiah = "Rp" . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
	?>
 	<center>
        <h1>Laporan Penjualan <br><?php echo $fs->tgl_indo(date('m', strtotime($dateEnd))) ?></h1>
        
	</center>
  <!-- <b>Tanggal: <?php echo $dateNow;?><b> -->
  <br/>
  <br/>
    <b>Rincian Jenis Pembayaran<b>
      <table border="1">
      <tr>
        <td>
          <b>
            Jenis Pembayaran
          </b>
          <b>
          </b>
        </td>
        <td>
          <b>
            Penjualan Bersih
          </b>
        </td>
        <td>
          <b>
            Convenience Fee
          </b>
        </td>
        <td>
          <b>
            Charge E-Wallet
          </b>
        </td>
        <td>
          <b>
            Total
          </b>
        </td>
      </tr>
      <?php
      $vals = $cf->getGroupPaymentMethod($fID, $dateStartNowDb, $dateEndNowDb);
      $sales = 0;
      $charge_ur = 0;
      $charge_ewallet = 0;
      $total = 0;
      foreach ($vals as $value) {
        $sales += $value['value'];
        $charge_ur += $value['charge_ur'];
        $charge_ewallet += ceil($value['charge_ewallet']);
        $total += $value['value']-$value['charge_ur']-ceil($value['charge_ewallet']);
      ?>
        <tr>
          <td>
            <?php echo $value['payment_method_name'] ?>
          </td>
          <td>
            <?php echo rupiah($value['value']) ?>
          </td>
          <td>
            <?php echo "(".rupiah($value['charge_ur']).")" ?>
          </td>
          <td>
            <?php echo "(".rupiah(ceil($value['charge_ewallet'])).")" ?>
          </td>
          <td>
            <?php echo rupiah($value['value']-$value['charge_ur']-ceil($value['charge_ewallet'])) ?>
          </td>
        </tr>
        <?php
      }
      ?>
      <tr>
        <td>
        </td>
        <td>
          <b><?php echo rupiah($sales) ?></b>
        </td>
        <td>
          <b><?php echo "(".rupiah($charge_ur).")" ?></b>
        </td>
        <td>
          <b><?php echo "(".rupiah($charge_ewallet).")" ?></b>
        </td>
        <td>
          <b><?php echo rupiah($total) ?></b>
        </td>
      </tr>
      </table>
  <br/>
  <br/>
  <b>Laba Kotor<b>
    <?php
      $res = $cf->getSubTotal($fID, $dateStartNowDb, $dateEndNowDb);
      $hppQ = mysqli_query(
        $db_conn,
        "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$fID' AND (transaksi.status=2 OR transaksi.status=1 OR transaksi.status=5) AND DATE(transaksi.paid_date) BETWEEN '$dateStartNowDb' AND '$dateEndNowDb'"
    );
    $res['hpp']=0;
    if (mysqli_num_rows($hppQ) > 0) {       
      $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
      $res['hpp']=(int)$resQ[0]['hpp'];
    }
    $sql = mysqli_query($db_conn, "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE p.id='$fID'AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateStartNowDb' AND '$dateEndNowDb' ORDER BY op.id DESC");
    $res['operational']=0;
    if(mysqli_num_rows($sql) > 0) {
      $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
      $res['operational']=(int) $data[0]['amount'];
    }
    $res['gross_profit']=$res['clean_sales']-$res['hpp'];
    $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
    $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
    ?>
    <table border="1">
      <tr>
        <td>  
          Penjualan
        </td>  
        <td>  
          <?php echo rupiah($res['sales']); ?>
        </td>  
      </tr>
      <tr>
        <td>  
          Diskon Spesial
        </td>  
        <td>  
          <?php echo "(".rupiah($res['diskon_spesial']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          Diskon Karyawan
        </td>  
        <td>  
          <?php echo "(".rupiah($res['employee_discount']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          Promo
        </td>  
        <td>  
          <?php echo "(".rupiah($res['promo']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          Diskon Program
        </td>  
        <td>  
          <?php echo "(".rupiah($res['program_discount']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Penjualan Bersih </b>
        </td>  
        <td>  
          <?php echo rupiah($res['clean_sales']); ?>
        </td>  
      </tr>
      <tr>
        <td>  
          HPP
        </td>  
        <td>  
          <?php echo "(".rupiah($res['hpp']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Laba Kotor</b>
        </td>  
        <td>  
          <?php echo rupiah($res['gross_profit']); ?>
        </td>  
      </tr>
    </table>
  <br/>
  <br/>
  <b>Laba Bersih<b>
    <table border="1">
      <tr>
        <td>  
          <b>Laba Kotor</b>
        </td>  
        <td>  
          <?php echo rupiah($res['gross_profit']); ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Service</b>
        </td>  
        <td>  
          <?php echo "(".rupiah($res['service']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Laba Kotor Setelah Service</b>
        </td>  
        <td>  
          <?php echo rupiah($res['gross_profit_afterservice']); ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Pajak</b>
        </td>  
        <td>  
          <?php echo "(".rupiah($res['tax']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Laba Kotor Setelah Pajak</b>
        </td>  
        <td>  
          <?php echo rupiah($res['gross_profit_aftertax']); ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Operasional</b>
        </td>  
        <td>  
          <?php echo "(".rupiah($res['operational']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Convenience Fee</b>
        </td>  
        <td>  
          <?php echo "(".rupiah($res['charge_ur']).")"; ?>
        </td>  
      </tr>
      <tr>
        <td>  
          <b>Laba Bersih</b>
        </td>  
        <td>  
          <?php echo rupiah($res['gross_profit_aftertax']-$res['operational']-$res['charge_ur']); ?>
        </td>  
      </tr>
      <tr>
    </table>
  <br/>
  <br/>
  <b>Daftar Transaksi<b>
    <table border="1">
    <tr>
      <th>No</th>
      <th>ID</th>
      <th>Jam</th>
      <th>No Meja</th>
      <th>Tipe Bayar</th>
      <th>Subtotal</th>
      <th>Diskon Promo</th>
      <th>Promo</th>
      <th>Diskon Spesial</th>
      <th>Diskon Karyawan</th>
      <th>Service</th>
      <th>Convenience Fee</th>
      <th>Pajak</th>
      <th>Total</th>
    </tr>
    <?php 
		// koneksi database
		$trans = mysqli_query($db_conn, "SELECT transaksi.id, transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount,transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.no_meja, transaksi.jam, transaksi.phone, payment_method.nama AS pName, program_discount FROM `transaksi` JOIN `payment_method` ON `transaksi`.`tipe_bayar`=payment_method.id WHERE md5(transaksi.id_partner)='$id' AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateStartNowDb' AND '$dateEndNowDb' AND (status='1' OR status='2' )");
    $no = 1;
		while($d = mysqli_fetch_array($trans)){
      $tempS=ceil(( (int) $d['total'] - (int) $d['promo'] - (int) $d['diskon_spesial'] - (int) $d['employee_discount'] - (int) $d['program_discount'] )*(int) $d['service'] / 100);
		?>
		<tr>
			<td><?php echo $no++; ?></td>
      <td><?php echo $d['id']; ?></td>
			<td><?php echo $d['jam']; ?></td>
			<td><?php echo $d['no_meja']; ?></td>
      <td><?php echo $d['pName']; ?></td>
      <td><?php echo rupiah($d['total']); ?></td>
      <td><?php echo '('.rupiah($d['program_discount']). ')'; ?></td>
      <td><?php echo '('.rupiah($d['promo']). ')'; ?></td>
      <td><?php echo '('.rupiah($d['diskon_spesial']). ')'; ?></td>
      <td><?php echo '('.rupiah($d['employee_discount']). ')'; ?></td>
      <td><?php echo rupiah($tempS) ?></td>
      <td><?php echo rupiah($d['charge_ur']); ?></td>
      <td><?php echo rupiah( ceil(( (int) $d['total'] - (int) $d['promo'] - (int) $d['diskon_spesial'] - (int) $d['employee_discount'] - (int) $d['program_discount'] + $tempS + (int) $d['charge_ur']) * ( int ) $d['tax'] / 100)); ?></td>
      <td><?php echo rupiah($d['total']-$d['program_discount']-$d['promo']-$d['diskon_spesial']-(int) $d['employee_discount']+$tempS+$d['charge_ur']+ceil(( (int) $d['total'] - (int) $d['promo'] - (int) $d['diskon_spesial']  - (int) $d['employee_discount'] - (int) $d['program_discount'] + $tempS + (int) $d['charge_ur']) * ( int ) $d['tax'] / 100)); ?></td>
		</tr>
		<?php 
    }
		?>

</body>
</html>