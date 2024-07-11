<!DOCTYPE html>
<head>
    <title>Print Piutang</title>
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
        tr.border-bottom td {
            /* border-bottom: 1pt solid #5e5c5d; */
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

<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    require '../../db_connection.php';
    require './Functions.php';

    if(!isset($_GET['token'])||empty($_GET['token'])||!isset($_GET['id'])||empty($_GET['id'])){
        echo "<h3>Not Authorized</h3>";
    }else{
        $res=[];
        $id = $_GET['id'];
        $token = $_GET['token'];
        $validate = "SELECT ar.transaction_id, ar.group_id,p.id, p.name AS partnerName, p.address, p.phone AS partnerPhone, ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName FROM account_receivables ar JOIN partner p ON p.id=ar.partner_id JOIN employees e ON ar.created_by=e.id WHERE ar.id='$id'";
        $qV = mysqli_query($db_conn, $validate);
        if(mysqli_num_rows($qV) > 0) {
            $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
            $x = $resV[0];
            $j=0;
                if($x['group_id']==0||$x['group_id']=="0"){
                    $transactionID=$x['transaction_id'];
                    $getTransactions = mysqli_query($db_conn,"SELECT t.total,
                    t.program_discount,
                    t.promo,
                    t.diskon_spesial,
                    t.employee_discount,
                    t.service,
                    t.tax,
                    t.jam,
                    t.charge_ur  FROM transaksi t WHERE t.id='$transactionID'");
                    $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                    $arr = $resTrx[0];
                    foreach($resTrx as $y){
                        $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                        $service = ceil($subtotal*$y['service']/100);
                        $serviceandCharge = $service + $y['charge_ur'];
                        $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                        $grandTotal = $subtotal+$serviceandCharge+$tax;
                        $arr['grandTotal']=$grandTotal;
                        $getDetails = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, m.nama, dt.harga_satuan, dt.qty, dt.harga FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu WHERE dt.deleted_at IS NULL AND dt.status!=4 AND dt.id_transaksi='$transactionID'");
                        $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
                        $arr['details']=$details;
                    }
                }else{
                    $groupID = $x['group_id'];
                    $getTransactions = mysqli_query($db_conn,"SELECT SUM(t.total) AS total,
                    SUM(t.program_discount) AS program_discount,
                    SUM(t.promo) AS promo,
                    SUM(t.diskon_spesial) AS diskon_spesial,
                    SUM(t.employee_discount) AS employee_discount,
                    t.service,
                    t.tax,
                    t.jam,
                    SUM(t.charge_ur) AS charge_ur FROM transaksi t WHERE t.group_id='$groupID'");
                    $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                    $arr = $resTrx[0];

                    foreach($resTrx as $y){
                        $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                        $service = ceil($subtotal*$y['service']/100);
                        $serviceandCharge = $service + $y['charge_ur'];
                        $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                        $grandTotal = $subtotal+$serviceandCharge+$tax;
                        $arr['grandTotal']=$grandTotal;
                        $getDetails = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, m.nama, dt.harga_satuan, dt.qty, dt.harga FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu JOIN transaksi t ON t.id=dt.id_transaksi WHERE dt.deleted_at IS NULL AND dt.status!=4 AND t.group_id='$groupID'");
                        $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
                        $arr['details']=$details;
                    }
                }  ?>

<div style="background-color:gray">
        <div style="width:21cm;height:29.7cm;background-color:white;">
            <div style="background-color:white;">
                <div style="margin-left:4mm;margin-right:4mm;">
                <table width="100%">
            <tr>
                <td><img src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/partners/undefined/undefined-logo%20bmc-WJMfOigx.webp" width="100" height="100"/></td>
                <td><div style="vertical-align:'text-center'; text-align:right" class="right">
                <?php echo $resV[0]['partnerName']?>
                    <br/>
                    <?php echo $resV[0]['address']?>
                    <br/>
                    <?php echo $resV[0]['partnerPhone']?>
                    <br/>
                </div></td>
            </tr>
            </table>
                    <table width="100%">
                        <tr>
                            <td width="50%" class="left">
                            <?php echo $resV[0]['user_name']."<br/>".$resV[0]['user_phone']."<br/>".$resV[0]['company']."<br/>";?>
                            </td>
                        <td width="50%" class="right">
                        Tanggal Transaksi  <?php echo tgl_indo2($arr['jam']);?><br/>Tanggal Penagihan <?php echo tgl_indo2(date('Y-m-d'));?><br/> <b><?php echo rupiah($arr['grandTotal']);?></b>
                        </td>
                        </tr>
                    </table>
                   <br/>
                    <table width="100%" id="customers">
                        <tr>
                            <th width="5%">
                                No
                            </th>
                            <th width="30%">
                                Produk
                            </th>
                            <th width="10%">
                                Qty
                            </th>
                            <th width="20%">
                                Harga Satuan
                            </th>
                            <th width="30%">
                                Harga
                            </th>
                        </tr>
                        <?php
                        $i=1;
                        foreach($arr['details'] as $z){
                            echo "<tr class='border-bottom'>";
                                echo "<td width='5%'>".$i."</td>";
                                echo "<td width='30%'>".$z['nama']."</td>";
                                echo "<td width='10%'>".ribuan($z['qty'])."</td>";
                                echo "<td width='20%'>".ribuan($z['harga_satuan'])."</td>";
                                echo "<td width='30%'>".rupiah($z['harga'])."</td>";
                            echo"</tr>";
                            $i++;
                        }
                        ?>
                    </table>
                    <hr/>
                    <table width="100%">
                        <tr>
                            <th width="50%">
                                &nbsp;
                            </th>
                            <th width="20%" class="left">
                                Subtotal
                            </th>
                            <th width="30%" class="left">
                                <?php echo rupiah($arr['total']);?>
                            </th>
                        </tr>
                        <tr>
                            <th width="50%">
                                &nbsp;
                            </th>
                            <th width="20%" class="left">
                                Service (<?php echo $arr['service'];?>%)
                            </th>
                            <th width="30%" class="left">
                                <?php echo rupiah($service);?>
                            </th>
                        </tr>
                        <tr>
                            <th width="50%">
                                &nbsp;
                            </th>
                            <th width="20%" class="left">
                                Tax (<?php echo $arr['tax'];?>%)
                            </th>
                            <th width="30%" class="left">
                                <?php echo rupiah($tax);?>
                            </th>
                        </tr>
                        <tr>
                            <th width="50%">
                                &nbsp;
                            </th>
                            <th width="20%" class="left">
                                Total
                            </th>
                            <th width="30%" class="left">
                                <?php echo rupiah($arr['grandTotal']);?>
                            </th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <?php
    }
    }
    ?>
</html>