<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';
require_once '../includes/CalculateFunctions.php';
require '../includes/functions.php';
require_once '../includes/MailerBCC.php';

$cf = new CalculateFunction();
$fs = new functions();
$dateToday = date("Y-m-d");
$dateTodayStr = date("d/m/Y");

$dic = array();
$data = mysqli_query($db_conn, "SELECT p.id, e.email, p.name FROM employees e JOIN master m ON e.id_master=m.id JOIN roles r ON r.id=e.role_id JOIN partner p ON p.id_master=m.id WHERE r.is_owner='1' AND p.is_email_report=1 AND e.deleted_at IS NULL AND p.status=1 AND p.id = '000162' ORDER BY p.id ASC");
if(mysqli_num_rows($data) > 0) {
    $datas = mysqli_fetch_all($data, MYSQLI_ASSOC);
    echo json_encode($datas);
    $tempPID = "";
    foreach ($datas as $value) {
        $hpp = 0;
        $partnerID = $value['id'];
        $email = $value['email'];
        $nama = $value['name'];
        $name = mysqli_real_escape_string($db_conn, $nama);
        
        $hppQ = mysqli_query(
            $db_conn,
            "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$partnerID' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateToday' AND '$dateToday' AND detail_transaksi.deleted_at IS NULL"
        );

        $menusQ = mysqli_query(
            $db_conn,
            "SELECT SUM(detail_transaksi.qty) AS qty, menu.nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu  WHERE transaksi.id_partner='$partnerID' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateToday' AND '$dateToday' AND detail_transaksi.deleted_at IS NULL GROUP BY menu.id ORDER BY qty DESC"
        );

        $opex = 0;
        $sqlOpex = mysqli_query($db_conn, "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE p.id='$partnerID'AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateToday' AND '$dateToday' ORDER BY op.id DESC");
        while($row = mysqli_fetch_assoc($sqlOpex)){
            $opex = $row['amount']==null?0:(int)$row['amount'];
            // $res['opex'] = $opex;
        }
        $opexes = array();
        $sqlOpexes = mysqli_query($db_conn, "SELECT e.id, e.name, e.amount, e.created_at, em.nama AS employeeName, ec.name AS categoryName, ec.id AS categoryID FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id JOIN partner p ON p.id_master=ec.master_id WHERE p.id='$partnerID' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateToday' AND '$dateToday' ORDER BY e.id DESC");
        if(mysqli_num_rows($sqlOpexes) > 0) {
            $opexes = mysqli_fetch_all($sqlOpexes, MYSQLI_ASSOC);
        }

        if (mysqli_num_rows($hppQ) > 0) {
            $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
            $hpp = (double) $resQ[0]['hpp'];
        }

        if($partnerID != $tempPID){
            $charge_ewallet = 0;
            $totalEwallet = 0;
            $totalNonEwallet = 0;
            $totalPoint = 0;
            $charge_ur = 0;
            $paymentMethods = $cf->getGroupPaymentMethod($partnerID, $dateToday, $dateToday, null);
            $details = $cf->getSubTotal($partnerID, $dateToday, $dateToday);
            $details['gross_profit']=(int) $details['clean_sales'];
            $details['gross_profit'] = (int) $details['gross_profit'] - $hpp;
            $details['gross_profit_afterservice']= (int) $details['gross_profit']- (int) $details['service'];
            $details['gross_profit_aftertax']= (int) $details['gross_profit_afterservice']- (int) $details['tax'];

            foreach ($paymentMethods as $value) {
                $charge_ewallet +=$value['charge_ewallet'];
            }
            
            // kirim email disini
            // foreach nya yang dalam email
            $val1 = 0;
            $totalEwallet1 = 0;
            $totalNonEwallet1  = 0;
            $totalPoint1 = 0;
            $charge_ur1 = 0;
            foreach ($paymentMethods as $value) {
            	if($value['tipe']=='1' || $value['tipe']=='2' || $value['tipe']=='3' || $value['tipe']=='4' ||$value['tipe']=='6' ||$value['tipe']=='10'){
                         $val1 = (int) $value['value'];
                         $totalEwallet1 += (int) $value['value'];
                         $totalPoint1 += (int) $value['point'];
                         $charge_ur1 += (int) $value['charge_ur'];
                     }
                     if($value['tipe']!='1' || $value['tipe']!='2' || $value['tipe']!='3' || $value['tipe']!='4'|| $value['tipe']!='6'|| $value['tipe']!='10'){
                         $totalNonEwallet1 += (int) $value['value'];
                         $totalPoint1 += (int) $value['point'];
                         $charge_ur1 += (int) $value['charge_ur'];
                      }
            }

            $eWallet = '';
            foreach ($paymentMethods as $value) {
                    $fs = new functions();
                    $val = 0;
                    if($value['tipe']=='1' || $value['tipe']=='2' || $value['tipe']=='3' || $value['tipe']=='4' ||$value['tipe']=='6' ||$value['tipe']=='10' ){
                          $val = (int) $value['value'];
                          $eWallet .= '<tr>
                                        <td width="80%" class="purchase_item"><span class="f-fallback">'.$value['payment_method_name'].'</span></td>
                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.$fs->rupiah($val).'</span></td>
                                        </tr>';
                          $totalEwallet += (int) $value['value'];
                          $totalPoint += (int) $value['point'];
                          $charge_ur += (int) $value['charge_ur'];
                     }
            }

            $nonEwallet = '';
            // echo json_encode($paymentMethods);
            // 5,7,8,9,14
            foreach ($paymentMethods as $value) {
                //  if($value['tipe'] != '1' || $value['tipe'] != '2' || $value['tipe'] != '3' || $value['tipe'] != '4' || $value['tipe'] != '6' || $value['tipe'] != '10'){
                 if($value['tipe'] == '5' || $value['tipe'] == '7' || $value['tipe'] == '8' || $value['tipe'] == '9' || $value['tipe'] == '14' || (int)$value['tipe'] > 20){
                    //  if($value['tipe'] == '5') {
                    //      echo "test";
                    //  }
                      $nonEwallet .= '
                            <tr>
                                <td width="80%" class="purchase_item">
                                    <span class="f-fallback">'.$value['payment_method_name'].'</span></td>
                                         <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.
                                                $fs->rupiah( (int) $value['value']).' </span></td>
                            </tr>';
                      $totalNonEwallet += (int) $value['value'];
                      $totalPoint += (int) $value['point'];
                      $charge_ur += (int) $value['charge_ur'];
                  }
            }
            // echo json_encode($nonEwallet);

            $menuTerjual = '';
            if (mysqli_num_rows($menusQ) > 0) {
                $resQ = mysqli_fetch_all($menusQ, MYSQLI_ASSOC);
                foreach ($resQ as $value) {
                     $menuTerjual .= '
                          <tr>
                               <td width="80%" class="purchase_item"><span class="f-fallback">'. $value['nama']. '</span>
                               </td>
                               <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x'.
                                $value['qty'] .'</span></td>
                           </tr>';
                 }
            }

            $menuVariant = '';
            $reportV = array();
                $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama FROM menu join
                       detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on
                       detail_transaksi.id_transaksi = transaksi.id join partner on
                       transaksi.id_partner=partner.id WHERE partner.id= '$partnerID' AND transaksi.status
                       IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateToday' AND '$dateToday' AND
                       detail_transaksi.deleted_at IS NULL");
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
                                  $v_id=0;
                                  $vg_name = $arr['name'];
                                  $detail = $arr['detail'];
                                  $v_qty = 0;
                                  foreach ($detail as $det) {
                                  $v_name = $det['name'];
                                  $v_qty += (int) $det['qty'];
                                  $idx = 0;
                                  foreach ($reportV as $value) {
                                      if($value['id']==$det['id'] && $value['name']==$v_name && $value['vg_name']==$vg_name &&
                                          $value['menu_name']==$namaMenu){
                                          $idx=$idx;
                                          break;
                                          }else{
                                          $idx+=1;
                                      }
                                  }
                                  $v_id=$idx;
                                  $dic[$v_id]=$det['id'];
                                  $reportV[$v_id]['id'] = $det['id'];
                                //   $reportV[$v_id]['qty']? $reportV[$v_id]['qty']+= $v_qty:$reportV[$v_id]['qty']=$v_qty;
                                  ($reportV[$v_id]['qty'] ?? $reportV[$v_id]['qty'] = 0) ? $reportV[$v_id]['qty'] += $v_qty : $reportV[$v_id]['qty']=$v_qty;
                                  $reportV[$v_id]['name'] = $v_name;
                                  $reportV[$v_id]['vg_name'] = $vg_name;
                                  $reportV[$v_id]['menu_name'] = $namaMenu;
                                  }
                              }
                              
                              foreach ($reportV as $key => $row) {
                                  $qty[$key] = $row['qty'];
                                  $menu_name[$key] = $row['menu_name'];
                              }

                              // you can use array_column() instead of the above code
                              $qty = array_column($reportV, 'qty');
                              $menu_name = array_column($reportV, 'menu_name');

                              // Sort the data with qty descending, menu_name ascending
                              // Add $data as the last parameter, to sort by the common key
                              array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                        }

                              }
                              
                        foreach ($reportV as $rv) {
                              $menuVariant .= '<tr>
                                <td width="80%" class="purchase_item"><span class="f-fallback">'. $rv['name'].' ('.
                                    $rv['menu_name']. '-'. $rv['vg_name'] .')</span></td>
                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x'.
                                    $rv['qty'] .'</span></td>
                              </tr>';
                        }

                        $biayaOperasional1 = '';
                        $resQ = mysqli_fetch_all($menusQ, MYSQLI_ASSOC);
                        foreach ($opexes as $value) {
                            $ca = date("H:i d/m/Y", strtotime($value['created_at'])); 
                            $add = "(".$ca."-".$value['employeeName'].")";
                            $biayaOperasional1 .= "
                                  <tr>
                                    <td width='80%' class='purchase_item'>
                                        <span class='f-fallback'>
                                        ". $value['name'] ." + ". $add ."
                                        </span>
                                    </td>
                                    <td class='align-right' width='20%' class='purchase_item'><span class='f-fallback'>".
                                        $fs->rupiah($value['amount']) ."</span></td>
                                  </tr>
                                ";
                        }
                            //   echo $biayaOperasional1;
            
            $query = "SELECT template FROM `email_template` WHERE id=6";
            $templateQ = mysqli_query($db_conn, $query);
    
            if (mysqli_num_rows($templateQ) > 0) {
                $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
                $html = $templates[0]['template'];
                
                // str replace :
                $html = str_replace('$dateTodayStr',$dateTodayStr,$html);
                $html = str_replace('$name',$name,$html);
                // $totalPendapatan = $fs->rupiah($totalEwallet1-$charge_ewallet+$totalNonEwallet1-$totalPoint1-$charge_ur1-$hpp-$details['service']-$details['tax']);
                // $html = str_replace('$totalPendapatan',$totalPendapatan,$html);
                $mdr = $fs->rupiah($charge_ewallet);
                $html = str_replace('$mdr',$mdr,$html);
                $html = str_replace('$eWallet',$eWallet,$html);
                // $totalEwallet = $fs->rupiah((int) $totalEwallet- (int) $charge_ewallet);
                $totalEWallet = $fs->rupiah((int) $totalEwallet- (int) $charge_ewallet);
                $html = str_replace('$totalEwallet',$totalEWallet,$html);
                $html = str_replace('$nonEwallet',$nonEwallet,$html);
                $html = str_replace('$menuTerjual',$menuTerjual,$html);
                $html = str_replace('$menuVariant',$menuVariant,$html);
                $pembayaranPoint = $fs->rupiah( (int) $totalPoint);
                $html = str_replace('$pembayaranPoint',$pembayaranPoint,$html);
                // $subTotal = $fs->rupiah((int)$totalEwallet-(int)$charge_ewallet+(int)$totalNonEwallet-(int)$totalPoint);
                $subTotal = $fs->rupiah((int)$totalEwallet+(int)$totalNonEwallet-(int)$totalPoint);
                $html = str_replace('$subTotal',$subTotal,$html);
                $convenienceFee = $fs->rupiah($charge_ur);
                $html = str_replace('$convenienceFee',$convenienceFee,$html);
                $cogs = $fs->rupiah($hpp);
                $html = str_replace('$cogs',$cogs,$html);
                $service = $fs->rupiah($details['service']);
                $html = str_replace('$service',$service,$html);
                $tax = $fs->rupiah($details['tax']);
                $html = str_replace('$tax',$tax,$html);
                $biayaOperasional = $fs->rupiah($opex);
                $html = str_replace('$biayaOperasional',$biayaOperasional,$html);
                // $labaBersih = $fs->rupiah((int)$totalEwallet+(int)$totalNonEwallet-(int)$totalPoint-(int)$charge_ur-(int)$details['service']-(int)$details['tax']-(int)$opex);
                $labaBersih = $fs->rupiah((int)$totalEwallet + (int)$totalNonEwallet - (int)$charge_ewallet - (int)$totalPoint - (int)$hpp - (int)$details['service'] - (int)$details['tax'] - (int)$opex);
                $html = str_replace('$labaBersih',$labaBersih,$html);
                $total = $fs->rupiah((int)$totalEwallet-(int)$charge_ewallet+(int)$totalNonEwallet-(int)$totalPoint-(int)$charge_ur-(int)$hpp-(int)$details['service']-(int)$details['tax']);
                $totalPendapatan = $total;
                $html = str_replace('$totalPendapatan',$totalPendapatan,$html);
                $html = str_replace('$total',$total,$html);
                $html = str_replace('$biayaOperasional1',$biayaOperasional1 ?? 0,$html);
                $partnerID = md5($partnerID);
                $html = str_replace('$partnerID',$partnerID,$html);
                $html = str_replace('$dateToday',$dateToday,$html);
                
                $insertPendingEmail = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$email', '$partnerID', 'UR | Daily Report', '$html', NOW())");
            }
            // kirim email disini end

        }
    }
}
?>