
<!DOCTYPE html>
<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include '../../db_connection.php';
    include 'Functions.php';
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
    if(!isset($_GET['token'])||empty($_GET['token'])||!isset($_GET['id'])||empty($_GET['id'])){
        echo "<h3>Not Authorized</h3>";
    }else{
    $id = $_GET['id'];
    $getGR = mysqli_query($db_conn, "SELECT gr.id, gr.notes, gr.recieve_date, gr.delivery_order_number, gr.sender, po.no AS poNo, s.name AS sName, s.phone AS sPhone, s.email AS sEmail, s.address AS sAddress, e.nama AS employeeName, p.logo,p.name, p.address, p.phone, p.email FROM goods_receipt gr JOIN purchase_orders po ON gr.purchase_order_id=po.id JOIN suppliers s ON s.id=po.supplier_id JOIN employees e ON e.id=gr.receiver_id JOIN partner p ON gr.id_partner = p.id WHERE gr.deleted_at IS NULL AND gr.id='$id'");
    $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
    $grData = $resGR[0];
    $getDetails = mysqli_query($db_conn, "SELECT grd.id, grd.qty, CASE WHEN id_raw_material=0 THEN 'pcs' ELSE (SELECT name FROM metric m  WHERE grd.id_metric=m.id) END AS metricName, CASE WHEN id_raw_material=0 THEN (SELECT nama FROM menu WHERE menu.id=grd.id_menu) ELSE (SELECT name FROM raw_material rm WHERE rm.id=grd.id_raw_material) END AS itemName FROM goods_receipt_detail grd WHERE grd.id_gr='$id' AND grd.deleted_at IS NULL ORDER BY grd.id ASC");
    $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
    if($grData['logo']==null){
        $logoURL = "https://partner.ur-hub.com/foreground-purple.png";
    }else{
        $logoURL = $grData['logo'];
    }
?>
<head>
    <title>Penerimaan Barang No <?php echo $grData['delivery_order_number'] ?></title>
    <style>
        .left{
            text-align:left;
        },
        .right{
            text-align:right;
        }
        td{
            vertical-align:top;
        }
        #customers {
        border-collapse: collapse;
        width: 100%;
        }

        #customers td, #customers th {
        border: 1px solid #ddd;
        padding: 8px;
        }

        #customers tr:nth-child(even){background-color: #f2f2f2;}

        #customers tr:hover {background-color: #ddd;}

        #customers th {
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: left;
        background-color: #DE148C;
        color: white;
        }
    </style>
    <script>
        window.print();
    </script>
</head>
<body>
    <div style="background-color:gray">
        <div style="width:21cm;height:29.7cm;background-color:white;">
            <div style="background-color:white;">
                <div style="margin-left:4mm;margin-right:4mm;">

                   <table border="1" width="100%">
                       <tr>
                           <td width="25%" style="text-align:left;">
                                <center>
                                <?php echo "<img src='".$logoURL."' width='100vw' height='100vw'/>";?>
                                </center>
                           </td>
                           <td width="75%">
                               <table width="100%">
                                   <tr>
                                       <td><h3>Penerimaan Barang</h3></td>
                                       <td class="right"></td>
                                   </tr>
                                   <tr>
                                       <td class="left">
                                           No. Surat Jalan
                                       </td>
                                       <td class="right">
                                           <?php echo $grData['delivery_order_number'];?>
                                       </td>
                                   </tr>
                                   <tr>
                                       <td class="left">
                                           Tgl. Diterima
                                       </td>
                                       <td class="right">
                                           <?php echo tgl_indo2($grData['recieve_date']);?>
                                       </td>
                                   </tr>
                               </table>
                           </td>
                       </tr>
                   </table>
                   <br/>
                   <table width="100%">
                       <tr>
                           <td width="50%">
                               Info Perusahaan
                           </td>
                           <td width="50%">
                               Diterima Dari
                           </td>
                       </tr>
                       <tr>
                           <td>
                               <hr/>
                           </td>
                           <td>
                               <hr/>
                           </td>
                       </tr>
                       <tr>
                           <td>
                            <div style="font-size:20;font-weight:bold;">
                                <?php echo $grData['name'];?></div>
                           </td>
                           <td>
                               <div style="font-size:20;font-weight:bold;"><?php echo $grData['sName'];?></div>
                               </td>
                       </tr>
                        <tr>
                           <td>Telp: <?php echo $grData['phone'];?></td>
                           <td>Telp: <?php echo $grData['sPhone'];?></td>
                        </tr>
                        <tr>
                           <td>Email: <?php echo $grData['email'];?></td>
                           <td>Email: <?php echo $grData['sEmail'];?></td>
                        </tr>
                        <tr>
                           <td>Alamat: <?php echo $grData['address'];?></td>
                           <td>Alamat: <?php echo $grData['sAddress'];?></td>
                        </tr>
                    </table>
                    <br>
                    <table width="100%" id="customers">
                        <tr >
                            <th>
                                No
                            </th>
                            <th>
                                Produk
                            </th>
                            <th>
                                Qty
                            </th>
                            <th>
                                Satuan
                            </th>
                            <!-- <th>
                                Harga
                            </th>
                            <th>
                                Total
                            </th> -->
                        </tr>
                        <?php
                        $i=1;
                        $total = 0;
                        foreach($details as $x){
                            echo "<tr>";
                                echo "<td>".$i."</td>";
                                echo "<td>".$x['itemName']."</td>";
                                echo "<td>".ribuan($x['qty'])."</td>";
                                echo "<td>".$x['metricName']."</td>";
                                // echo "<td>".rupiah($x['price']/$x['qty'])."</td>";
                                // echo "<td>".rupiah($x['price'])."</td>";
                            echo"</tr>";
                            $i++;
                            // $total+=(int)$x['price'];
                        }
                        ?>
                    </table>
                    <hr/>
                    <div style="text-align:left;">
                    Catatan :  <?php echo $grData['notes'];?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<?php
    }
    ?>
</html>