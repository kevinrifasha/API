
<!DOCTYPE html>
<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include '../db_connection.php';
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

    $id = $_GET['id'];
    $getPO = mysqli_query($db_conn, "SELECT po.id, p.name, p.address, p.phone, po.created_at, p.email, s.name AS sName, s.phone AS sPhone, s.email AS sEmail, s.address AS sAddress FROM purchase_orders po JOIN partner p ON po.partner_id = p.id JOIN suppliers s ON s.id = po.supplier_id WHERE po.deleted_at IS NULL AND po.id='$id'");
    while($row = mysqli_fetch_assoc($getPO)){
        $poData = $row;
    }
    $getDetails = mysqli_query($db_conn, "SELECT pod.id, rm.name, pod.qty, pod.price, metric.name AS metricName FROM purchase_orders_details pod JOIN raw_material rm ON pod.raw_id = rm.id JOIN metric ON metric.id = pod.metric_id WHERE pod.purchase_order_id = '$id'");
    $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
?>
<head>
    <title>Purchase Order No <?php echo $data['id'] ?></title>
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
    </style>
</head>
<body>
    <div style="background-color:gray">
        <div style="width:21cm;height:29.7cm;background-color:white;">
            <div style="background-color:white;">
                <div style="margin-left:4mm;margin-right:4mm;">

                   <table border="1" width="100%">
                       <tr>
                           <td width="50%" style="text-align:left;">
                               gambar
                           </td>
                           <td width="50%">
                               <table width="100%">
                                   <tr>
                                       <td></td>
                                       <td class="right"><h3>Purchase Order</h3></td>
                                   </tr>
                                   <tr>
                                       <td class="left">
                                           No. Purchase Order
                                       </td>
                                       <td class="right">
                                           <?php echo $poData['id'];?>
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
                    <table width="100%">
                        <tr style="background-color:#0054ff; color:white;text-align:left;">
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
                                echo "<td>".$x['name']."</td>";
                                echo "<td>".ribuan($x['qty'])."</td>";
                                echo "<td>".$x['metricName']."</td>";
                                echo "<td>".rupiah($x['price']/$x['qty'])."</td>";
                                echo "<td>".rupiah($x['price'])."</td>";
                            echo"</tr>";
                            $i++;
                            $total+=(int)$x['price'];
                        }
                        ?>
                    </table>
                    <hr/>
                    <div style="text-align:right;">
                        Total <?php echo rupiah($total);?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>