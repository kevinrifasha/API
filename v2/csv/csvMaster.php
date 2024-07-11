
<?php
    // force download of CSV
    // simulate file handle w/ php://output, direct to output (from http://www.php.net/manual/en/function.fputcsv.php#72428)
    // (could alternately write to memory handle & read from stream, this seems more direct)
    // headers from http://us3.php.net/manual/en/function.readfile.php
    date_default_timezone_set('Asia/Jakarta');
    header('Content-Description: File Transfer');
    header('Content-Type: application/csv');
    header("Content-Disposition: attachment; filename=Sales.csv");
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    require '../db_connection.php';

    function rupiah($angka){
	
        $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
        return $hasil_rupiah;
     
    }

    $handle = fopen('php://output', 'w');
    ob_clean(); // clean slate

    $id = $_GET['id'];
    $first_day_last_month = date('01/M/Y', strtotime('-1 month')); // hard-coded '01' for first day
    $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
    $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
    $dateLastDb = date('Y-m-t', strtotime('-1 month'));

    $fields = array('No', 'Code', 'Menu', 'HPP', 'Harga', 'Status','Total');
    fputcsv($handle, $fields);
    $n=1;
    $subtotal=0;
      // [given some database query object $result]...
      $menu = mysqli_query($db_conn, "SELECT * FROM menu join partner ON menu.id_partner=partner.id WHERE MD5(partner.id_master)='$id';");
      while($rowmenu=mysqli_fetch_assoc($menu)){
        $idMenu=$rowmenu['id'];
        $namaMenu=$rowmenu['nama'];
        $harga=$rowmenu['harga'];
        $hpp=$rowmenu['hpp'];
        $detail = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenu' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN $dateFirstDb AND $dateLastDb;"); 
        while($rowdetail=mysqli_fetch_assoc($detail)){
            if($rowdetail['qtytotal']==''){
                $qty = 0;
              }else{
                $qty = $rowdetail['qtytotal'];
              }
        }
        $subtotal += (($harga-$hpp)*$qty);
        $lineData = array($n, $idMenu, $namaMenu, rupiah($hpp), rupiah($harga), $qty,rupiah(($harga-$hpp)*$qty));
        fputcsv($handle, $lineData);
        // $html .='<tr nobr="true"><td width="5%">'.$n.'</td><td width="7%">'.$idMenu.'</td><td width="25%">'.$namaMenu.'</td><td width="18%">'.rupiah($hpp).'</td><td width="18%">'.rupiah($harga).'</td><td width="7%">'.$qty.'</td><td width="21%">'.rupiah(($harga-$hpp)*$qty).' </td></tr>';
        $n++;
      }
    //   while ($row = db_fetch_array($result)) {
    //     // parse the data...
        
    //      // direct to buffered output
    //   }

    ob_flush(); // dump buffer
    fclose($handle);
    die();		
    // client should see download prompt and page remains where it was