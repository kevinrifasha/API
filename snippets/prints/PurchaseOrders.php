
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
    $getPO = mysqli_query($db_conn, "SELECT p.logo, po.notes, po.no, po.id, p.name, p.address, p.phone, po.created_at, p.email, s.name AS sName, s.phone AS sPhone, s.email AS sEmail, s.address AS sAddress FROM purchase_orders po JOIN partner p ON po.partner_id = p.id JOIN suppliers s ON s.id = po.supplier_id WHERE po.deleted_at IS NULL AND po.id='$id'");
    $resPO = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
    $poData = $resPO[0];
    $getDetails = mysqli_query($db_conn, "SELECT pod.id, pod.qty, pod.price, pod.metric_id, m.name AS metricName, pod.raw_id, pod.menu_id, CASE WHEN raw_id=0 THEN (SELECT nama FROM menu WHERE menu.id=menu_id) ELSE (SELECT name FROM raw_material rm WHERE rm.id=pod.raw_id) END AS itemName FROM purchase_orders_details pod JOIN metric m ON pod.metric_id=m.id WHERE pod.purchase_order_id='$id' AND pod.deleted_at IS NULL");
    $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
    if($poData['logo']==null){
        $logoURL = "https://partner.ur-hub.com/foreground-purple.png";
    }else{
        $logoURL = $poData['logo'];
    }
?>
<head>
    <title>Purchase Order No <?php echo $poData['no'] ?></title>
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
                               <?php echo "<img src='".$logoURL."' width='50vw' height='50vw'/>";?>
                           </td>
                           <td width="75%">
                               <table width="100%">
                                   <tr>
                                       <td><h3>Purchase Order</h3></td>
                                       <td class="right"></td>
                                   </tr>
                                   <tr>
                                       <td class="left">
                                           No. Purchase Order
                                       </td>
                                       <td class="right">
                                           <?php echo $poData['no'];?>
                                       </td>
                                   </tr>
                                   <tr>
                                       <td class="left">
                                           Tgl. Purchase Order
                                       </td>
                                       <td class="right">
                                           <?php echo tgl_indo2($poData['created_at']);?>
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
                               Order Kepada
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
                                <?php echo $poData['name'];?></div>
                           </td>
                           <td>
                               <div style="font-size:20;font-weight:bold;"><?php echo $poData['sName'];?></div>
                               </td>
                       </tr>
                        <tr>
                           <td>Telp: <?php echo $poData['phone'];?></td>
                           <td>Telp: <?php echo $poData['sPhone'];?></td>
                        </tr>
                        <tr>
                           <td>Email: <?php echo $poData['email'];?></td>
                           <td>Email: <?php echo $poData['sEmail'];?></td>
                        </tr>
                        <tr>
                           <td>Alamat: <?php echo $poData['address'];?></td>
                           <td>Alamat: <?php echo $poData['sAddress'];?></td>
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
                            <th>
                                Harga
                            </th>
                            <th>
                                Total
                            </th>
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
                                echo "<td>".rupiah($x['price'])."</td>";
                                echo "<td>".rupiah($x['price']*$x['qty'])."</td>";
                            echo"</tr>";
                            $i++;
                            $total+=$x['price']*$x['qty'];
                        }
                        ?>
                    </table>
                    <hr/>
                    <div style="text-align:right;">
                        Total <?php echo rupiah($total);?>
                    </div>
                    <div style="text-align:left;">
                    Catatan :  <?php echo $poData['notes'];?>
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