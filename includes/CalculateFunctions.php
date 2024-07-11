<?php
date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Endroid\QrCode\QrCode;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

class CalculateFunction
{
    private $conn;
    private $conn2;


    //Constructor
    function __construct()
    {
        require_once dirname(__FILE__) . '/Constants.php';
        require_once dirname(__FILE__) . '/DbConnect.php';
        require_once dirname(__FILE__) . '/Mailer.php';
        require_once dirname(__FILE__) . '/functions.php';

        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
        $this->conn2 = $db->connect();
    }
 
    public function getSubTotal($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT transaksi.total, transaksi.promo, transaksi.diskon_spesial,  transaksi.rounding, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, IFNULL(delivery.ongkir,0) as delivery_fee, transaksi.pax, IFNULL(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_resto, IFNULL(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_shipper, transaksi.dp_total FROM `transaksi` LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id WHERE transaksi.id_partner = '$id' AND transaksi.status in (1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'";
        $trxQ = mysqli_query($db_conn, $query);

        if(mysqli_num_rows($trxQ) == 0){
            $result = [];
            $result['subtotal'] = 0;
            $result['sales'] = 0;
            $result['promo'] = 0;
            $result['consignmentTax'] = 0;
            $result['consignmentService'] = 0;
            $result['program_discount'] = 0;
            $result['diskon_spesial'] = 0;
            $result['employee_discount'] = 0;
            $result['point'] = 0;
            $result['rounding'] = 0;
            $result['service'] = 0;
            $result['charge_ur'] = 0;
            $result['tax'] = 0;
            $result['delivery_fee'] = 0;
            $result['dpTotal'] = 0;
            $result['pax'] = 0;
            $result['delivery_fee_resto'] = 0;
            $result['delivery_fee_shipper'] = 0;
            $result['total'] = 0;
            $result['clean_sales'] = 0;
            $result['average_check'] = 0;
            $result['count'] = 0;
            return $result;
        }

        $queryConsignment = "SELECT SUM(dt.harga_satuan*dt.qty) as total_consignment, t.tax, t.service FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE t.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND c.is_consignment = 1 AND t.id_partner = '$id' GROUP BY t.id";
        
        $consignmentQ = mysqli_query($db_conn, $queryConsignment);

        $i = 1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $delivery_fee = 0;
        $delivery_fee_resto = 0;
        $delivery_fee_shipper = 0;
        $dpTotal = 0;
        $rounding = 0;
        $pax = 0;
        $consignmentTax = 0;
        $consignmentService = 0;
        
        while ($row = mysqli_fetch_assoc($consignmentQ)) {
            $consignmentService += ceil((int)$row['total_consignment'] * (int)$row['service'] / 100);
            $consignmentTax += ceil(((int)$row['total_consignment'] + ((int)$row['total_consignment'] * ((int)$row['service'] / 100) )) * (int)$row['tax'] / 100);
        }

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $rounding += (int)$row['rounding'];
            $pax += (int)$row['pax'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $dpTotal_per_trx = (int) $row['dpTotal'] + (int)$row['rounding'];
            $point += (int) $row['point'];
            $collectedBeforeTaxAndService = (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'];
            $rounding_per_trx = (int) $row['rounding'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $delivery_fee += (int) $row['delivery_fee'];
            $delivery_fee_resto += (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            if($row['total'] > 0 && $row['dp_total'] > 0 && $collectedBeforeTaxAndService + $tempT + $tempS - $row['dp_total'] <= 0 ){
                $covered = $collectedBeforeTaxAndService + $tempT + $tempS + $rounding_per_trx;
                $dpTotal += $covered;
            } else {
                $dpTotal += (float)$row['dp_total'];
            }
            $i++;
        }

        $result['subtotal'] = $subtotal;
        $result['sales'] = $subtotal + $service + $tax + $charge_ur + $delivery_fee_resto;
        $result['promo'] = $promo;
        $result['pax'] = $pax;
        $result['consignmentTax'] = $consignmentTax;
        $result['consignmentService'] = $consignmentService;
        $result['program_discount'] = $program_discount;
        $result['diskon_spesial'] = $diskon_spesial;
        $result['employee_discount'] = $employee_discount;
        $result['point'] = $point;
        $result['rounding'] = $rounding;
        $result['service'] = $service - $consignmentService;
        $result['charge_ur'] = $charge_ur;
        $result['tax'] = $tax - $consignmentTax;
        $result['delivery_fee'] = $delivery_fee;
        $result['dpTotal'] = $dpTotal;
        $result['delivery_fee_resto'] = $delivery_fee_resto;
        $result['delivery_fee_shipper'] = $delivery_fee_shipper;
        $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $delivery_fee_resto - $dpTotal;
        // $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $dpTotal + $rounding;
        $result['clean_sales'] = $result['total'] + $rounding - $consignmentTax - $consignmentService;
        $result['average_check'] = $result['clean_sales']/$pax;
        $result['count'] = $i;
        //delivery fee shipper tidak masuk ke resto(gojek/grab)

        return $result;
    }

    public function getSubTotalWithHour($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT transaksi.total, transaksi.promo, transaksi.rounding ,transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, IFNULL(delivery.ongkir,0) as delivery_fee, pax, IFNULL(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_resto, IFNULL(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_shipper, transaksi.dp_total FROM `transaksi` LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id WHERE transaksi.id_partner = '$id' AND transaksi.status in (1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'";
        $trxQ = mysqli_query($db_conn, $query);
        
        if(mysqli_num_rows($trxQ) == 0){
            $result = [];
            $result['subtotal'] = 0;
            $result['sales'] = 0;
            $result['promo'] = 0;
            $result['consignmentTax'] = 0;
            $result['consignmentService'] = 0;
            $result['program_discount'] = 0;
            $result['diskon_spesial'] = 0;
            $result['employee_discount'] = 0;
            $result['point'] = 0;
            $result['rounding'] = 0;
            $result['service'] = 0;
            $result['charge_ur'] = 0;
            $result['tax'] = 0;
            $result['delivery_fee'] = 0;
            $result['dpTotal'] = 0;
            $result['pax'] = 0;
            $result['delivery_fee_resto'] = 0;
            $result['delivery_fee_shipper'] = 0;
            $result['total'] = 0;
            $result['clean_sales'] = 0;
            $result['average_check'] = 0;
            $result['count'] = 0;
            return $result;
        } else {
            
            $queryConsignment = "SELECT SUM(dt.harga_satuan*dt.qty) as total_consignment, t.tax, t.service FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE t.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND c.is_consignment = 1 AND t.id_partner = '$id' GROUP BY t.id";
            
            $consignmentQ = mysqli_query($db_conn, $queryConsignment);
    
            $i = 1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $delivery_fee = 0;
            $delivery_fee_resto = 0;
            $delivery_fee_shipper = 0;
            $dpTotal = 0;
            $rounding = 0;
            $pax = 0;
            $consignmentTax = 0;
            $consignmentService = 0;
    
            while ($row = mysqli_fetch_assoc($consignmentQ)) {
                $consignmentService += ceil((int)$row['total_consignment'] * (int)$row['service'] / 100);
                $consignmentTax += ceil(((int)$row['total_consignment'] + ((int)$row['total_consignment'] * ((int)$row['service'] / 100))) * (int)$row['tax'] / 100);
            }
    
            while ($row = mysqli_fetch_assoc($trxQ)) {
                $subtotal += (int) $row['total'];
                $promo += (int) $row['promo'];
                $pax += (int) $row['pax'];
                $rounding += (int)$row['rounding'];
                $program_discount += (int) $row['program_discount'];
                $diskon_spesial += (int) $row['diskon_spesial'];
                $employee_discount += (int) $row['employee_discount'];
                $dpTotal_per_trx = (int) $row['dpTotal'] + (int)$row['rounding'];
                $point += (int) $row['point'];
                $collectedBeforeTaxAndService = (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'];
                $rounding_per_trx = (int) $row['rounding'];
                $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
                $service += $tempS;
                $charge_ur += (int) $row['charge_ur'];
                $delivery_fee += (int) $row['delivery_fee'];
                $delivery_fee_resto += (int) $row['delivery_fee_resto'];
                $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
                $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
                $tax += $tempT;
                if($row['total'] > 0 && $row['dp_total'] > 0 && $collectedBeforeTaxAndService + $tempT + $tempS - $row['dp_total'] <= 0 ){
    
                    $covered = $collectedBeforeTaxAndService + $tempT + $tempS + $rounding_per_trx;
                    $dpTotal += $covered;
                } else {
                    $dpTotal += (float)$row['dp_total'];
                }
                $i++;
            }
        
            $result = [];
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur + $delivery_fee_resto;
            $result['promo'] = $promo;
            $result['consignmentTax'] = $consignmentTax;
            $result['consignmentService'] = $consignmentService;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['rounding'] = $rounding;
            $result['service'] = $service - $consignmentService;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax - $consignmentTax;
            $result['delivery_fee'] = $delivery_fee;
            $result['dpTotal'] = $dpTotal;
            $result['pax'] = $pax;
            $result['delivery_fee_resto'] = $delivery_fee_resto;
            $result['delivery_fee_shipper'] = $delivery_fee_shipper;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $delivery_fee_resto - $dpTotal;
            // $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $dpTotal + $rounding;
            $result['clean_sales'] = $result['total'] + $rounding - $consignmentTax - $consignmentService;
            $result['average_check'] = $result['clean_sales']/$pax;
            $result['count'] = $i;
            //delivery fee shipper tidak masuk ke resto(gojek/grab)
    
            return $result;
        }
        
    }

    public function getSubTotalMaster($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.id_partner, transaksi.status, transaksi.tax, transaksi.rounding, transaksi.program_discount, IFNULL(delivery.ongkir,0) as delivery_fee, IFNULL(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_resto, IFNULL(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_shipper, transaksi.id as trxId FROM `transaksi` LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id JOIN partner p On p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status in (1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $delivery_fee = 0;
        $delivery_fee_resto = 0;
        $delivery_fee_shipper = 0;
        $trxId = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $prevTrxId = $row['trxId'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $rounding += (int) $row['rounding'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $delivery_fee += (int) $row['delivery_fee'];
            $delivery_fee_resto += (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            if($prevTrxId != $trxId){
                $i++;
            }
            
            $trxId = $prevTrxId;
        }

        $result['subtotal'] = $subtotal;
        $result['sales'] = $subtotal + $service + $tax + $charge_ur + $delivery_fee_resto;
        $result['promo'] = $promo;
        $result['program_discount'] = $program_discount;
        $result['diskon_spesial'] = $diskon_spesial;
        $result['employee_discount'] = $employee_discount;
        $result['point'] = $point;
        $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
        $result['service'] = $service;
        $result['charge_ur'] = $charge_ur;
        $result['tax'] = $tax;
        $result['rounding'] = $rounding;
        $result['delivery_fee'] = $delivery_fee;
        $result['delivery_fee_resto'] = $delivery_fee_resto;
        $result['delivery_fee_shipper'] = $delivery_fee_shipper;
        $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $delivery_fee_resto;
        $result['count'] = 0;
        if($i > 1){
            $result['count'] = $i + 1;
        }
        //delivery fee shipper tidak masuk ke resto(gojek/grab)

        return $result;
    }

    public function getSubTotalMasterWithHour($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT transaksi.total, transaksi.id, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.id_partner, transaksi.status, transaksi.tax, transaksi.program_discount, IFNULL(delivery.ongkir,0) as delivery_fee, IFNULL(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_resto, IFNULL(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_shipper FROM `transaksi` LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id JOIN partner p On p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status in (1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $delivery_fee = 0;
        $delivery_fee_resto = 0;
        $delivery_fee_shipper = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $delivery_fee += (int) $row['delivery_fee'];
            $delivery_fee_resto += (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $i++;
        }

        $result['subtotal'] = $subtotal;
        $result['sales'] = $subtotal + $service + $tax + $charge_ur + $delivery_fee_resto;
        $result['promo'] = $promo;
        $result['program_discount'] = $program_discount;
        $result['diskon_spesial'] = $diskon_spesial;
        $result['employee_discount'] = $employee_discount;
        $result['point'] = $point;
        $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
        $result['service'] = $service;
        $result['charge_ur'] = $charge_ur;
        $result['tax'] = $tax;
        $result['delivery_fee'] = $delivery_fee;
        $result['delivery_fee_resto'] = $delivery_fee_resto;
        $result['delivery_fee_shipper'] = $delivery_fee_shipper;
        $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $delivery_fee_resto;
        $result['count'] = 0;
        if($i > 1){
            $result['count'] = $i + 1;
        }
        //delivery fee shipper tidak masuk ke resto(gojek/grab)

        return $result;
    }


    public function getSubTotalAll($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT dt.harga AS total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.id_partner, p.name AS partner_name, transaksi.status, transaksi.tax, transaksi.program_discount, IFNULL(delivery.ongkir,0) as delivery_fee, IFNULL(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_resto, IFNULL(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_shipper FROM `transaksi` JOIN detail_transaksi dt ON dt.id_transaksi=transaksi.id JOIN menu m ON m.id=dt.id_menu LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status in (1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'";
        $trxQ = mysqli_query($db_conn, $query);

        $array = [];

        $i = 1;
        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $delivery_fee = 0;
            $delivery_fee_resto = 0;
            $delivery_fee_shipper = 0;

            $id_partner = $row['id_partner'];
            $partner_name = $row['partner_name'];
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $delivery_fee = (int) $row['delivery_fee'];
            $delivery_fee_resto = (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper = (int) $row['delivery_fee_shipper'];
            $tempT = ceil(
                ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100
            );
            $tax = $tempT;
            // }

            $result['id_partner'] = $id_partner;
            $result['partner_name'] = $partner_name;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur + $delivery_fee_resto;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['delivery_fee'] = $delivery_fee;
            $result['delivery_fee_resto'] = $delivery_fee_resto;
            $result['delivery_fee_shipper'] = $delivery_fee_shipper;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $delivery_fee_resto;
            $result['count'] = $i;
            //delivery fee shipper tidak masuk ke resto(gojek/grab)

            array_push($array, $result);
            $i++;
        }

        $result = array_reduce($array, function ($carry, $item) {
            if (!isset($carry[$item['id_partner']])) {
                $carry[$item['id_partner']] =
                    [
                        'id_partner' => $item['id_partner'],
                        'partner_name' => $item['partner_name'],
                        'charge_ur' => $item['charge_ur'],
                        'clean_sales' => $item['clean_sales'],
                        'subtotal' => $item['subtotal'],
                        'sales' => $item['sales'],
                        'promo' => $item['promo'],
                        'program_discount' => $item['program_discount'],
                        'diskon_spesial' => $item['diskon_spesial'],
                        'employee_discount' => $item['employee_discount'],
                        'point' => $item['point'],
                        'service' => $item['service'],
                        'tax' => $item['tax'],
                        'delivery_fee' => $item['delivery_fee'],
                        'delivery_fee_resto' => $item['delivery_fee_resto'],
                        'delivery_fee_shipper' => $item['delivery_fee_shipper'],
                        'total' => $item['total'],
                    ];
            } else {
                $carry[$item['id_partner']]['tax'] += $item['tax'];
                $carry[$item['id_partner']]['charge_ur'] += $item['charge_ur'];
                $carry[$item['id_partner']]['clean_sales'] += $item['clean_sales'];
                $carry[$item['id_partner']]['subtotal'] += $item['subtotal'];
                $carry[$item['id_partner']]['sales'] += $item['sales'];
                $carry[$item['id_partner']]['promo'] += $item['promo'];
                $carry[$item['id_partner']]['program_discount'] += $item['program_discount'];
                $carry[$item['id_partner']]['diskon_spesial'] += $item['diskon_spesial'];
                $carry[$item['id_partner']]['employee_discount'] += $item['employee_discount'];
                $carry[$item['id_partner']]['point'] += $item['point'];
                $carry[$item['id_partner']]['service'] += $item['service'];
                // $carry[$item['id_partner']]['tax'] += $item['tax'];
                $carry[$item['id_partner']]['delivery_fee'] += $item['delivery_fee'];
                $carry[$item['id_partner']]['delivery_fee_resto'] += $item['delivery_fee_resto'];
                $carry[$item['id_partner']]['delivery_fee_shipper'] += $item['delivery_fee_shipper'];
                $carry[$item['id_partner']]['total'] += $item['total'];
            }

            return $carry;
        });
        

        return $result;
    }


    public function getSubTotalAllWithHour($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT dt.harga AS total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.id_partner, p.name AS partner_name, transaksi.status, transaksi.tax, transaksi.program_discount, IFNULL(delivery.ongkir,0) as delivery_fee, IFNULL(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_resto, IFNULL(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END,0) as delivery_fee_shipper FROM `transaksi` JOIN detail_transaksi dt ON dt.id_transaksi=transaksi.id JOIN menu m ON m.id=dt.id_menu LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status in (1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'";
        $trxQ = mysqli_query($db_conn, $query);

        $array = [];

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $i = 1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $delivery_fee = 0;
            $delivery_fee_resto = 0;
            $delivery_fee_shipper = 0;

            $id_partner = $row['id_partner'];
            $partner_name = $row['partner_name'];
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $delivery_fee = (int) $row['delivery_fee'];
            $delivery_fee_resto = (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper = (int) $row['delivery_fee_shipper'];
            $tempT = ceil(
                ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100
            );
            $tax = $tempT;
            //   $i++;
            // }

            $result['id_partner'] = $id_partner;
            $result['partner_name'] = $partner_name;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur + $delivery_fee_resto;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['delivery_fee'] = $delivery_fee;
            $result['delivery_fee_resto'] = $delivery_fee_resto;
            $result['delivery_fee_shipper'] = $delivery_fee_shipper;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $delivery_fee_resto;
            $result['count'] = $i;
            //delivery fee shipper tidak masuk ke resto(gojek/grab)

            array_push($array, $result);
        }

        $result = array_reduce($array, function ($carry, $item) {
            if (!isset($carry[$item['id_partner']])) {
                $carry[$item['id_partner']] =
                    [
                        'id_partner' => $item['id_partner'],
                        'partner_name' => $item['partner_name'],
                        'charge_ur' => $item['charge_ur'],
                        'clean_sales' => $item['clean_sales'],
                        'subtotal' => $item['subtotal'],
                        'sales' => $item['sales'],
                        'promo' => $item['promo'],
                        'program_discount' => $item['program_discount'],
                        'diskon_spesial' => $item['diskon_spesial'],
                        'employee_discount' => $item['employee_discount'],
                        'point' => $item['point'],
                        'service' => $item['service'],
                        'tax' => $item['tax'],
                        'delivery_fee' => $item['delivery_fee'],
                        'delivery_fee_resto' => $item['delivery_fee_resto'],
                        'delivery_fee_shipper' => $item['delivery_fee_shipper'],
                        'total' => $item['total'],
                    ];
            } else {
                $carry[$item['id_partner']]['tax'] += $item['tax'];
                $carry[$item['id_partner']]['charge_ur'] += $item['charge_ur'];
                $carry[$item['id_partner']]['clean_sales'] += $item['clean_sales'];
                $carry[$item['id_partner']]['subtotal'] += $item['subtotal'];
                $carry[$item['id_partner']]['sales'] += $item['sales'];
                $carry[$item['id_partner']]['promo'] += $item['promo'];
                $carry[$item['id_partner']]['program_discount'] += $item['program_discount'];
                $carry[$item['id_partner']]['diskon_spesial'] += $item['diskon_spesial'];
                $carry[$item['id_partner']]['employee_discount'] += $item['employee_discount'];
                $carry[$item['id_partner']]['point'] += $item['point'];
                $carry[$item['id_partner']]['service'] += $item['service'];
                // $carry[$item['id_partner']]['tax'] += $item['tax'];
                $carry[$item['id_partner']]['delivery_fee'] += $item['delivery_fee'];
                $carry[$item['id_partner']]['delivery_fee_resto'] += $item['delivery_fee_resto'];
                $carry[$item['id_partner']]['delivery_fee_shipper'] += $item['delivery_fee_shipper'];
                $carry[$item['id_partner']]['total'] += $item['total'];
            }

            return $carry;
        });

        return $result;
    }

    public function getShiftTransaction($id, $dateFrom, $dateTo, $idMaster)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "";
        if (isset($idMaster)) {
            $query = "SELECT trx.id,trx.description,CASE WHEN trx.type = 1 THEN 'credit' ELSE 'debit' END as type,trx.amount FROM shift_transactions AS trx JOIN shift ON trx.shift_id = shift.id JOIN partner p ON p.id=shift.partner_id WHERE p.id_master = '$idMaster' AND trx.deleted_at IS NULL AND DATE(trx.created_at) BETWEEN '$dateFrom' AND '$dateFrom'";
        } else {
            $query = "SELECT trx.id,trx.description,CASE WHEN trx.type = 1 THEN 'credit' ELSE 'debit' END as type,trx.amount FROM shift_transactions AS trx JOIN shift ON trx.shift_id = shift.id WHERE shift.partner_id = '$id' AND trx.deleted_at IS NULL AND DATE(trx.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        }

        $trxQ = mysqli_query($db_conn, $query);
        $i = 0;
        $debit = 0;
        $credit = 0;
        $saldo = 0;
        $arr = array();
        while ($row = mysqli_fetch_assoc($trxQ)) {
            $arr[$i] = $row;
            if ($row['type'] == 'debit') {
                $debit += (int) $row['amount'];
            } else {
                $credit += (int) $row['amount'];
            }
            $i++;
        }
        $result['debit'] = $debit;
        $result['credit'] = $credit;
        $result['saldo'] = $debit - $credit;
        $result['data'] = $arr;
        return $result;
    }
    public function getShiftTransactionWithHour($id, $dateFrom, $dateTo, $idMaster)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "";
        if (isset($idMaster)) {
            $query = "SELECT trx.id,trx.description,CASE WHEN trx.type = 1 THEN 'credit' ELSE 'debit' END as type,trx.amount FROM shift_transactions AS trx JOIN shift ON trx.shift_id = shift.id JOIN partner p ON p.id=shift.partner_id WHERE p.id_master = '$idMaster' AND trx.deleted_at IS NULL AND trx.created_at BETWEEN '$dateFrom' AND '$dateFrom'";
        } else {
            $query = "SELECT trx.id,trx.description,CASE WHEN trx.type = 1 THEN 'credit' ELSE 'debit' END as type,trx.amount FROM shift_transactions AS trx JOIN shift ON trx.shift_id = shift.id WHERE shift.partner_id = '$id' AND trx.deleted_at IS NULL AND trx.created_at BETWEEN '$dateFrom' AND '$dateTo'";
        }

        $trxQ = mysqli_query($db_conn, $query);
        $i = 0;
        $debit = 0;
        $credit = 0;
        $saldo = 0;
        $arr = array();
        while ($row = mysqli_fetch_assoc($trxQ)) {
            $arr[$i] = $row;
            if ($row['type'] == 'debit') {
                $debit += (int) $row['amount'];
            } else {
                $credit += (int) $row['amount'];
            }
            $i++;
        }
        $result['debit'] = $debit;
        $result['credit'] = $credit;
        $result['saldo'] = $debit - $credit;
        $result['data'] = $arr;
        return $result;
    }
    public function getSubTotalTenant($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(counted) as counted, name, tipe_bayar, SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT COUNT(transaksi.id) as counted, transaksi.tipe_bayar, transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day ,payment_method.nama as name FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= " SELECT COUNT(`$transactions`.id) as counted,`$transactions`.tipe_bayar ,`$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day, payment_method.nama as name FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
        //     }
        // }
        $query .= " ) as TEMP  ";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $counted = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            if (!empty($row['counted'])) {
                $counted += (int) $row['counted'];
            }
            $tempT = (float) $row['tax'];
            $tax += $tempT;
            //   $i++;
        }

        $result['subtotal'] = $subtotal;
        $result['sales'] = $subtotal + $service + $tax + $charge_ur;
        $result['promo'] = $promo;
        $result['program_discount'] = $program_discount;
        $result['diskon_spesial'] = $diskon_spesial;
        $result['employee_discount'] = $employee_discount;
        $result['point'] = $point;
        $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
        $result['service'] = $service;
        $result['charge_ur'] = $charge_ur;
        $result['tax'] = $tax;
        $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
        $result['count'] = $counted;


        return $result;
    }

    public function getSubTotalTenantWithHour($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(counted) as counted, name, tipe_bayar, SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT COUNT(transaksi.id) as counted, transaksi.tipe_bayar, transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day ,payment_method.nama as name FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= " SELECT COUNT(`$transactions`.id) as counted,`$transactions`.tipe_bayar ,`$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day, payment_method.nama as name FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
        //     }
        // }
        $query .= " ) as TEMP  ";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $counted = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            if (!empty($row['counted'])) {
                $counted += (int) $row['counted'];
            }
            $tempT = (float) $row['tax'];
            $tax += $tempT;
            //   $i++;
        }

        $result['subtotal'] = $subtotal;
        $result['sales'] = $subtotal + $service + $tax + $charge_ur;
        $result['promo'] = $promo;
        $result['program_discount'] = $program_discount;
        $result['diskon_spesial'] = $diskon_spesial;
        $result['employee_discount'] = $employee_discount;
        $result['point'] = $point;
        $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
        $result['service'] = $service;
        $result['charge_ur'] = $charge_ur;
        $result['tax'] = $tax;
        $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
        $result['count'] = $counted;


        return $result;
    }

    public function getByHour($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax, SUM(charge_ewallet) AS charge_ewallet, SUM(charge_ur) AS charge_ur, hour  FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  SUM(transaksi.charge_ur) AS charge_ur, HOUR(transaksi.paid_date) as hour FROM transaksi WHERE transaksi.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  HOUR(`transaksi`.paid_date) ";
        $query .= ") AS temp GROUP BY hour ORDER BY hour ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempHour = 0;
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $hour = $row['hour'];
            $changetoHour = $row['hour'];
            if (strlen($row['hour']) == 1) {
                $set = '0' . $row['hour'] . ':00 - 0' . $row['hour'] . ':59';
            } else {
                $set = $row['hour'] . ':00 - ' . $row['hour'] . ':59';
            }
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = (int) $row['service'];
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $tempT = (int) $row['tax'];
            $tax = $tempT;

            $result['hour'] = $set;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;
            $i++;
        }
        return $res;
    }

    public function getByHourWithHour($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax, SUM(charge_ewallet) AS charge_ewallet, SUM(charge_ur) AS charge_ur, hour  FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  SUM(transaksi.charge_ur) AS charge_ur, HOUR(transaksi.paid_date) as hour FROM transaksi WHERE transaksi.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  HOUR(`transaksi`.paid_date) ";
        $query .= ") AS temp GROUP BY hour ORDER BY hour ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempHour = 0;
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $hour = $row['hour'];
            $changetoHour = $row['hour'];
            if (strlen($row['hour']) == 1) {
                $set = '0' . $row['hour'] . ':00 - 0' . $row['hour'] . ':59';
            } else {
                $set = $row['hour'] . ':00 - ' . $row['hour'] . ':59';
            }
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = (int) $row['service'];
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $tempT = (int) $row['tax'];
            $tax = $tempT;

            $result['hour'] = $set;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;
            $i++;
        }
        return $res;
    }

    public function getByHourMaster($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax, SUM(charge_ewallet) AS charge_ewallet, SUM(charge_ur) AS charge_ur, hour  FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  SUM(transaksi.charge_ur) AS charge_ur, HOUR(transaksi.paid_date) as hour FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  HOUR(`transaksi`.paid_date) ";
        $query .= ") AS temp GROUP BY hour ORDER BY hour ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempHour = 0;
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $hour = $row['hour'];
            $changetoHour = $row['hour'];
            if (strlen($row['hour']) == 1) {
                $set = '0' . $row['hour'] . ':00 - 0' . $row['hour'] . ':59';
            } else {
                $set = $row['hour'] . ':00 - ' . $row['hour'] . ':59';
            }
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = (int) $row['service'];
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $tempT = (int) $row['tax'];
            $tax = $tempT;

            $result['hour'] = $set;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;
            $i++;
        }
        return $res;
    }

    public function getByHourMasterWithHour($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax, SUM(charge_ewallet) AS charge_ewallet, SUM(charge_ur) AS charge_ur, hour  FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  SUM(transaksi.charge_ur) AS charge_ur, HOUR(transaksi.paid_date) as hour FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  HOUR(`transaksi`.paid_date) ";
        $query .= ") AS temp GROUP BY hour ORDER BY hour ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempHour = 0;
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $hour = $row['hour'];
            $changetoHour = $row['hour'];
            if (strlen($row['hour']) == 1) {
                $set = '0' . $row['hour'] . ':00 - 0' . $row['hour'] . ':59';
            } else {
                $set = $row['hour'] . ':00 - ' . $row['hour'] . ':59';
            }
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = (int) $row['service'];
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $tempT = (int) $row['tax'];
            $tax = $tempT;

            $result['hour'] = $set;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;
            $i++;
        }
        return $res;
    }

    public function getByHourTenant($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, hour  FROM (
            SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, HOUR(transaksi.paid_date) as hour FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, HOUR(`$transactions`.paid_date) as hour FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //         // $query .= "SELECT `$transactions`.total, `$transactions`.promo, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax,
        //         // HOUR(`$transactions`.paid_date) AS hour, `$transactions`.program_discount
        //         // FROM `$transactions`
        //         // WHERE `$transactions`.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        //     }
        // }
        $query .= ") AS temp GROUP BY hour ORDER BY hour ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempHour = 0;
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $hour = $row['hour'];
            $changetoHour = $row['hour'];
            if (strlen($row['hour']) == 1) {
                $set = '0' . $row['hour'] . ':00 - 0' . $row['hour'] . ':59';
            } else {
                $set = $row['hour'] . ':00 - ' . $row['hour'] . ':59';
            }
            $subtotal = (int) $row['total'];
            $promo = (int) $row['promo'];
            $program_discount = (int) $row['program_discount'];
            $diskon_spesial = (int) $row['diskon_spesial'];
            $employee_discount = (int) $row['employee_discount'];
            $point = (int) $row['point'];
            $tempS = (int) $row['service'];
            $service = $tempS;
            $charge_ur = (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax = $tempT;

            $result['hour'] = $set;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;
            $i++;
        }
        return $res;
    }

    public function getByDay($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = " SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE (menu.id_partner='$id' OR transaksi.id_partner='$id') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= " SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //     }
        // }
        $query .= ") AS tmp GROUP BY day ORDER BY day ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempDay = "";
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $day = $row['day'];

            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $hpp = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            if ($tempDay == "" || $tempDay != $row['day']) {
                if ($tempDay != "") {
                    $i++;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax += $tempT;

            $result['day'] = $day;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;

            $tempDay = $row['day'];
        }
        return $res;
    }

    public function getByDayMaster($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= " SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //     }
        // }
        $query .= ") AS tmp GROUP BY day ORDER BY day ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempDay = "";
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $day = $row['day'];

            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $hpp = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            if ($tempDay == "" || $tempDay != $row['day']) {
                if ($tempDay != "") {
                    $i++;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax += $tempT;

            $result['day'] = $day;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;

            $tempDay = $row['day'];
        }
        return $res;
    }

    public function getByDayWithHour($id, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = " SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE (menu.id_partner='$id' OR transaksi.id_partner='$id') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= " SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //     }
        // }
        $query .= ") AS tmp GROUP BY day ORDER BY day ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempDay = "";
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $day = $row['day'];

            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $hpp = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            if ($tempDay == "" || $tempDay != $row['day']) {
                if ($tempDay != "") {
                    $i++;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax += $tempT;

            $result['day'] = $day;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;

            $tempDay = $row['day'];
        }
        return $res;
    }

    public function getByDayMasterWithHour($idMaster, $dateFrom, $dateTo)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= " SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //     }
        // }
        $query .= ") AS tmp GROUP BY day ORDER BY day ASC ";
        $trxQ = mysqli_query($db_conn, $query);

        $tempDay = "";
        $res = array();
        $i = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $hpp = 0;

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $day = $row['day'];

            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $hpp = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            if ($tempDay == "" || $tempDay != $row['day']) {
                if ($tempDay != "") {
                    $i++;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax += $tempT;

            $result['day'] = $day;
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal + $service + $tax + $charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
            $result['gross_profit'] = $result['sales'] - $promo - $program_discount - $diskon_spesial - $employee_discount - $point - $hpp;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result['count'] = $i;
            $res[$i] = $result;

            $tempDay = $row['day'];
        }
        return $res;
    }

    public function getGroupPaymentMethodTenant($id, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "SELECT name, tipe_bayar, SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
        SELECT transaksi.tipe_bayar, transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet, transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day ,payment_method.nama as name FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE (menu.id_partner='$id' OR transaksi.id_partner='$id') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= "SELECT `$transactions`.tipe_bayar ,`$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day, payment_method.nama as name FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //     }
        // }
        $query .= ") AS tmp GROUP BY tipe_bayar";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $charge_ewallet = 0;
            if ($i == 0 || $row['tipe_bayar'] != $tempCompare) {
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            $result[$j]['tipe'] = $row['tipe_bayar'];

            $charge_ewallet += ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + $tempT + (int) $row['charge_ur']) * $row['charge_ewallet'] / 100;
            if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == 2 || $row['tipe_bayar'] == 3 || $row['tipe_bayar'] == 4 || $row['tipe_bayar'] == 6 || $row['tipe_bayar'] == 10) {
                $result[$j]['charge_ewallet'] = $charge_ewallet + ($charge_ewallet * 10 / 100);
            } else {
                $result[$j]['charge_ewallet'] = 0;
            }

            $tempCompare = $row['tipe_bayar'];
            $i += 1;
        }

        return $result;
    }

    public function getGroupPaymentMethodTenantWithHour($id, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "SELECT name, tipe_bayar, SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
        SELECT transaksi.tipe_bayar, transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet, transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day ,payment_method.nama as name FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE (menu.id_partner='$id' OR transaksi.id_partner='$id') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= " UNION ALL " ;
        //         $query .= "SELECT `$transactions`.tipe_bayar ,`$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day, payment_method.nama as name FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        //     }
        // }
        $query .= ") AS tmp GROUP BY tipe_bayar";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $charge_ewallet = 0;
            if ($i == 0 || $row['tipe_bayar'] != $tempCompare) {
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = (float) $row['tax'];
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            $result[$j]['tipe'] = $row['tipe_bayar'];

            $charge_ewallet += ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + $tempT + (int) $row['charge_ur']) * $row['charge_ewallet'] / 100;
            if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == 2 || $row['tipe_bayar'] == 3 || $row['tipe_bayar'] == 4 || $row['tipe_bayar'] == 6 || $row['tipe_bayar'] == 10) {
                $result[$j]['charge_ewallet'] = $charge_ewallet + ($charge_ewallet * 10 / 100);
            } else {
                $result[$j]['charge_ewallet'] = 0;
            }

            $tempCompare = $row['tipe_bayar'];
            $i += 1;
        }

        return $result;
    }

    public function getGroupPaymentMethod($id, $dateFrom, $dateTo, $idMaster)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "";

        if (isset($idMaster) && $idMaster != null) {
            $query = "SELECT SUM(transaksi.total) as total, SUM(transaksi.dp_total) AS dpTotal, SUM(transaksi.promo) as promo, SUM(transaksi.diskon_spesial) as diskon_spesial, SUM(transaksi.employee_discount) as employee_discount, SUM( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - transaksi.employee_discount - `transaksi`.point )* `transaksi`.service / 100 ) AS service, SUM( ( ( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 )+ `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point + `transaksi`.charge_ur )* `transaksi`.tax / 100 ) AS tax, SUM(transaksi.charge_ur) as charge_ur, SUM(transaksi.point) as point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, SUM(transaksi.program_discount) as program_discount, IFNULL(SUM(delivery.ongkir),0) as delivery_fee, IFNULL(SUM(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_resto, IFNULL(SUM(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_shipper FROM transaksi JOIN payment_method ON transaksi.tipe_bayar = payment_method.id LEFT JOIN delivery ON `transaksi`.`id` = delivery.transaksi_id JOIN partner p ON p.id=transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN (1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.tipe_bayar";
        } else {
            // $query = "SELECT SUM(transaksi.total) as total, SUM(transaksi.dp_total) AS dpTotal, SUM(transaksi.promo) as promo, SUM(transaksi.diskon_spesial) as diskon_spesial, SUM(transaksi.employee_discount) as employee_discount, SUM( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - transaksi.employee_discount - `transaksi`.point )* `transaksi`.service / 100 ) AS service, SUM( ( ( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 )+ `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point + `transaksi`.charge_ur )* `transaksi`.tax / 100 ) AS tax, SUM(transaksi.charge_ur) as charge_ur, SUM(transaksi.point) as point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, SUM(transaksi.program_discount) as program_discount, IFNULL(SUM(delivery.ongkir),0) as delivery_fee, IFNULL(SUM(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_resto, IFNULL(SUM(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_shipper FROM transaksi JOIN payment_method ON transaksi.tipe_bayar = payment_method.id LEFT JOIN delivery ON `transaksi`.`id` = delivery.transaksi_id WHERE transaksi.id_partner='$id' AND transaksi.status IN (1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.tipe_bayar";
            
            $query = "SELECT SUM(transaksi.total) as total,
SUM(
  CASE WHEN transaksi.dp_total < transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.tax / 100
  ) + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.service / 100
    ) + transaksi.rounding
  THEN transaksi.dp_total 
  ELSE transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.tax / 100
  ) + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.service / 100
  ) + transaksi.rounding END
) AS dpTotal
, SUM(transaksi.promo) as promo, SUM(transaksi.rounding) as rounding, SUM(transaksi.diskon_spesial) as diskon_spesial, SUM(transaksi.employee_discount) as employee_discount, SUM( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - transaksi.employee_discount - `transaksi`.point )* `transaksi`.service / 100 ) AS service, SUM( ( ( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 )+ `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point + `transaksi`.charge_ur )* `transaksi`.tax / 100 ) AS tax, SUM(transaksi.charge_ur) as charge_ur, SUM(transaksi.point) as point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, SUM(transaksi.program_discount) as program_discount, IFNULL(SUM(delivery.ongkir),0) as delivery_fee, IFNULL(SUM(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_resto, IFNULL(SUM(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_shipper FROM transaksi JOIN payment_method ON transaksi.tipe_bayar = payment_method.id LEFT JOIN delivery ON `transaksi`.`id` = delivery.transaksi_id WHERE transaksi.id_partner='$id' AND transaksi.status IN (1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.tipe_bayar";
        }
        // $trxQ = mysqli_query($db_conn, $query);

        // $i = 0;
        // $j = 0;
        // $subtotal = 0;
        // $promo = 0;
        // $program_discount = 0;
        // $diskon_spesial = 0;
        // $employee_discount = 0;
        // $point = 0;
        // $service = 0;
        // $tax = 0;
        // $charge_ur = 0;
        // $charge_ewallet = 0;
        // $delivery_fee = 0;
        // $delivery_fee_resto = 0;
        // $delivery_fee_shipper = 0;
        // $tempCompare = "";
        // $dpTotal = 0;

        // while ($row = mysqli_fetch_assoc($trxQ)) {
        //     if ($i == 0 || $row['tipe_bayar'] != $tempCompare) {
        //         $subtotal = 0;
        //         $promo = 0;
        //         $program_discount = 0;
        //         $diskon_spesial = 0;
        //         $employee_discount = 0;
        //         $point = 0;
        //         $service = 0;
        //         $tax = 0;
        //         $charge_ur = 0;
        //         $charge_ewallet = 0;
        //         $delivery_fee = 0;
        //         $delivery_fee_resto = 0;
        //         $delivery_fee_shipper = 0;
        //         $dpTotal = 0;
        //         if ($i != 0) {
        //             $j += 1;
        //         }
        //     }
        //     $subtotal += (int) $row['total'];
        //     $promo += (int) $row['promo'];
        //     $program_discount += (int) $row['program_discount'];
        //     $diskon_spesial += (int) $row['diskon_spesial'];
        //     $employee_discount += (int) $row['employee_discount'];
        //     $point += (int) $row['point'];
        //     $dpTotal += (int) $row['dpTotal'];
        //     // $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
        //     $tempS = (int) $row['service'];
        //     $service += $tempS;
        //     $charge_ur += (int) $row['charge_ur'];
        //     // $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( int ) $row['tax'] / 100);
        //     $tempT = (float) $row['tax'];
        //     $delivery_fee += (int) $row['delivery_fee'];
        //     $delivery_fee_resto += (int) $row['delivery_fee_resto'];
        //     $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
        //     $tax += $tempT;
        //     $result[$j]['dpTotal'] = $row['dpTotal'];
        //     $result[$j]['payment_method_name'] = $row['name'];
        //     $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + ceil($tax) + $delivery_fee_resto - $dpTotal;
        //     $result[$j]['charge_ur'] = $charge_ur;
        //     $result[$j]['point'] = $point;
        //     $result[$j]['tipe'] = $row['tipe_bayar'];
        //     $result[$j]['delivery_fee'] = $delivery_fee;
        //     $result[$j]['delivery_fee_resto'] = $delivery_fee_resto;
        //     $result[$j]['delivery_fee_shipper'] = $delivery_fee_shipper;
        //     $charge_ewallet += ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + $tempT + (int) $row['charge_ur'] + (int) $row['delivery_fee']) * $row['charge_ewallet'] / 100;
        //     ///charge e-wallet pasti lebih gede soalnya delivery fee, shipper ditanggung resto
        //     if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == 2 || $row['tipe_bayar'] == 3 || $row['tipe_bayar'] == 4 || $row['tipe_bayar'] == 6 || $row['tipe_bayar'] == 10) {
        //         $result[$j]['charge_ewallet'] = ceil($charge_ewallet + ($charge_ewallet * 11 / 100));
        //     } else {
        //         $result[$j]['charge_ewallet'] = 0;
        //     }
        //     $tempCompare = $row['tipe_bayar'];
        //     $i += 1;
        // }

        // return $result;
    
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $delivery_fee = 0;
        $delivery_fee_resto = 0;
        $delivery_fee_shipper = 0;
        $tempCompare = "";
        $dpTotal = 0;
        $rounding = 0;
        
        $queryConsignment = "SELECT 
            SUM(dt.harga_satuan*dt.qty) as total_consignment, t.tax, t.service, t.tipe_bayar FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE t.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND c.is_consignment = 1 AND t.id_partner = '$id' GROUP BY t.tipe_bayar";
        
        $consignmentQ = mysqli_query($db_conn, $queryConsignment);
        
        $consignmentArray = array();
        $i= 0;
        while ($row = mysqli_fetch_assoc($consignmentQ)) {
            $consignmentArray[$i]["tipe_bayar"] = $row["tipe_bayar"];
            $consignmentArray[$i]["consignmentService"] += (int)$row['total_consignment'] * (int)$row['service'] / 100;
            $consignmentArray[$i]["consignmentTax"] += ((int)$row['total_consignment'] + ((int)$row['total_consignment'] * ((int)$row['service'] / 100) )) * (int)$row['tax'] / 100;
            $i++;
        }
        
        while ($row = mysqli_fetch_assoc($trxQ)) {
            $paymentMethod = $row["tipe_bayar"];
            if ($i == 0 || $row['tipe_bayar'] != $tempCompare) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                $delivery_fee = 0;
                $delivery_fee_resto = 0;
                $delivery_fee_shipper = 0;
                $dpTotal = 0;
                $rounding = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $consignmentTax = 0;    
            $consignmentService = 0;    
            
            foreach($consignmentArray as $value){
                if($value["tipe_bayar"] == $row["tipe_bayar"]){
                    $consignmentTax = $value["consignmentTax"];
                    $consignmentService = $value["consignmentService"];
                }
            }
            
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $rounding += (int) $row['rounding'];
            $dpTotal += (int) $row['dpTotal'];
            // $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            // $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( int ) $row['tax'] / 100);
            $tempT = (float) $row['tax'];
            $delivery_fee += (int) $row['delivery_fee'];
            $delivery_fee_resto += (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
            $tax += $tempT;
            $result[$j]["consignmentService"] = $consignmentService;
            $result[$j]["consignmentTax"] = $consignmentTax;
            $result[$j]['dpTotal'] = $row['dpTotal'];
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['beforeDP'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + ceil($tax) + $delivery_fee_resto;
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + ceil($tax) + $delivery_fee_resto - $dpTotal + $rounding - $consignmentTax - $consignmentService;
            if((int) $result[$j]['beforeDP'] <= (int) $row['dpTotal']){
                $result[$j]['value'] = 0;
            }
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            $result[$j]['tipe'] = $row['tipe_bayar'];
            $result[$j]['delivery_fee'] = $delivery_fee;
            $result[$j]['rounding'] = $rounding;
            $result[$j]['delivery_fee_resto'] = $delivery_fee_resto;
            $result[$j]['delivery_fee_shipper'] = $delivery_fee_shipper;
            $charge_ewallet += ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + $tempT + (int) $row['charge_ur'] + (int) $row['delivery_fee'] - $consignmentTax - $consignmentService) * $row['charge_ewallet'] / 100;
            ///charge e-wallet pasti lebih gede soalnya delivery fee, shipper ditanggung resto
            if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == 2 || $row['tipe_bayar'] == 3 || $row['tipe_bayar'] == 4 || $row['tipe_bayar'] == 6 || $row['tipe_bayar'] == 10) {
                $result[$j]['charge_ewallet'] = ceil($charge_ewallet + ($charge_ewallet * 11 / 100));
            } else {
                $result[$j]['charge_ewallet'] = 0;
            }
            $tempCompare = $row['tipe_bayar'];
            $i += 1;
        }
        
        return $result;
        
    }

    public function getGroupPaymentMethodWithHour($id, $dateFrom, $dateTo, $idMaster)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "";

        if (isset($idMaster)) {
            $query = "SELECT SUM(transaksi.total) as total, SUM(transaksi.dp_total) AS dpTotal, SUM(transaksi.promo) as promo, SUM(transaksi.diskon_spesial) as diskon_spesial, SUM(transaksi.employee_discount) as employee_discount, SUM( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - transaksi.employee_discount - `transaksi`.point )* `transaksi`.service / 100 ) AS service, SUM( ( ( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 )+ `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point + `transaksi`.charge_ur )* `transaksi`.tax / 100 ) AS tax, SUM(transaksi.charge_ur) as charge_ur, SUM(transaksi.point) as point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, SUM(transaksi.program_discount) as program_discount, IFNULL(SUM(delivery.ongkir),0) as delivery_fee, IFNULL(SUM(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_resto, IFNULL(SUM(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_shipper FROM transaksi JOIN payment_method ON transaksi.tipe_bayar = payment_method.id LEFT JOIN delivery ON `transaksi`.`id` = delivery.transaksi_id JOIN partner p ON p.id=transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN (1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.tipe_bayar";
        } else {

    $query = "SELECT SUM(transaksi.total) as total,
SUM(
  CASE WHEN transaksi.dp_total < transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.tax / 100
  ) + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.service / 100
    ) + transaksi.rounding
  THEN transaksi.dp_total
  ELSE transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.tax / 100
  ) + (
    (
      transaksi.total - transaksi.diskon_spesial - transaksi.employee_discount - transaksi.program_discount - transaksi.point - transaksi.promo
    ) * transaksi.service / 100
  ) + transaksi.rounding END
) AS dpTotal
, SUM(transaksi.promo) as promo, SUM(transaksi.rounding) as rounding, SUM(transaksi.diskon_spesial) as diskon_spesial, SUM(transaksi.employee_discount) as employee_discount, SUM( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - transaksi.employee_discount - `transaksi`.point )* `transaksi`.service / 100 ) AS service, SUM( ( ( ( `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point )* `transaksi`.service / 100 )+ `transaksi`.total - `transaksi`.promo - `transaksi`.program_discount - `transaksi`.diskon_spesial - employee_discount - `transaksi`.point + `transaksi`.charge_ur )* `transaksi`.tax / 100 ) AS tax, SUM(transaksi.charge_ur) as charge_ur, SUM(transaksi.point) as point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, SUM(transaksi.program_discount) as program_discount, IFNULL(SUM(delivery.ongkir),0) as delivery_fee, IFNULL(SUM(CASE WHEN delivery.rate_id = 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_resto, IFNULL(SUM(CASE WHEN delivery.rate_id != 0 THEN delivery.ongkir ELSE 0 END),0) as delivery_fee_shipper FROM transaksi JOIN payment_method ON transaksi.tipe_bayar = payment_method.id LEFT JOIN delivery ON `transaksi`.`id` = delivery.transaksi_id WHERE transaksi.id_partner='$id' AND transaksi.status IN (1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.tipe_bayar";
        }
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $delivery_fee = 0;
        $delivery_fee_resto = 0;
        $delivery_fee_shipper = 0;
        $tempCompare = "";
        $dpTotal = 0;
        $rounding = 0;

        $queryConsignment = "SELECT
            SUM(dt.harga_satuan*dt.qty) as total_consignment, t.tax, t.service, t.tipe_bayar FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE t.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND c.is_consignment = 1 AND t.id_partner = '$id' GROUP BY t.tipe_bayar";

        $consignmentQ = mysqli_query($db_conn, $queryConsignment);

        $consignmentArray = array();
        $i = 0;
        while ($row = mysqli_fetch_assoc($consignmentQ)) {
            $consignmentArray[$i]["tipe_bayar"] = $row["tipe_bayar"];
            $consignmentArray[$i]["consignmentService"] += (int)$row['total_consignment'] * (int)$row['service'] / 100;
            $consignmentArray[$i]["consignmentTax"] += ((int)$row['total_consignment'] + ((int)$row['total_consignment'] * ((int)$row['service'] / 100))) * (int)$row['tax'] / 100;
            $i++;
        }

        while ($row = mysqli_fetch_assoc($trxQ)) {
            $paymentMethod = $row["tipe_bayar"];
            if ($i == 0 || $row['tipe_bayar'] != $tempCompare) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                $delivery_fee = 0;
                $delivery_fee_resto = 0;
                $delivery_fee_shipper = 0;
                $dpTotal = 0;
                $rounding = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $consignmentTax = 0;
            $consignmentService = 0;

            foreach ($consignmentArray as $value) {
                if ($value["tipe_bayar"] == $row["tipe_bayar"]) {
                    $consignmentTax = $value["consignmentTax"];
                    $consignmentService = $value["consignmentService"];
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $rounding += (int) $row['rounding'];
            $dpTotal += (int) $row['dpTotal'];
            // $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            // $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( int ) $row['tax'] / 100);
            $tempT = (float) $row['tax'];
            $delivery_fee += (int) $row['delivery_fee'];
            $delivery_fee_resto += (int) $row['delivery_fee_resto'];
            $delivery_fee_shipper += (int) $row['delivery_fee_shipper'];
            $tax += $tempT;
            $result[$j]["consignmentService"] = $consignmentService;
            $result[$j]["consignmentTax"] = $consignmentTax;
            $result[$j]['dpTotal'] = $row['dpTotal'];
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['beforeDP'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + ceil($tax) + $delivery_fee_resto;
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + ceil($tax) + $delivery_fee_resto - $dpTotal + $rounding - $consignmentTax - $consignmentService;
            if ((int) $result[$j]['beforeDP'] <= (int) $row['dpTotal']) {
                $result[$j]['value'] = 0;
            }
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            $result[$j]['tipe'] = $row['tipe_bayar'];
            $result[$j]['delivery_fee'] = $delivery_fee;
            $result[$j]['rounding'] = $rounding;
            $result[$j]['delivery_fee_resto'] = $delivery_fee_resto;
            $result[$j]['delivery_fee_shipper'] = $delivery_fee_shipper;
            $charge_ewallet += ((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + $tempT + (int) $row['charge_ur'] + (int) $row['delivery_fee'] - $consignmentTax - $consignmentService) * $row['charge_ewallet'] / 100;
            ///charge e-wallet pasti lebih gede soalnya delivery fee, shipper ditanggung resto
            if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == 2 || $row['tipe_bayar'] == 3 || $row['tipe_bayar'] == 4 || $row['tipe_bayar'] == 6 || $row['tipe_bayar'] == 10) {
                $result[$j]['charge_ewallet'] = ceil($charge_ewallet + ($charge_ewallet * 11 / 100));
            } else {
                $result[$j]['charge_ewallet'] = 0;
            }
            $tempCompare = $row['tipe_bayar'];
            $i += 1;
        }
        
        return $result;
    }

    public function getBySurcharge($id, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name,
        CASE
            WHEN SUM(detail_transaksi.is_program)>0 THEN 1
            ELSE 0
        END AS is_program
        FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id WHERE transaksi.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY detail_transaksi.id_transaksi  ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= "UNION ALL " ;
        //         $query .= "SELECT SUM(`$detail_transactions`.qty*`$detail_transactions`.harga_satuan) total, `$transactions`.promo, `$transactions`.program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.tax, `$transactions`.charge_ur, `$transactions`.point, payment_method.nama as name, `$transactions`.tipe_bayar, `$transactions`.charge_ewallet, `$transactions`.surcharge_id, `$transactions`.surcharge_percent, surcharges.name surcharge_name,
        //         CASE
        //             WHEN SUM(`$detail_transactions`.is_program)>0 THEN 1
        //             ELSE 0
        //         END AS is_program
        //         FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=`$transactions`.surcharge_id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id WHERE `$transactions`.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.surcharge_id!=0 GROUP BY `$detail_transactions`.id_transaksi  ";
        //     }
        // }
        // $query .=" ORDER BY surcharge_id, surcharge_percent ";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeWithHour($id, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name,
        CASE
            WHEN SUM(detail_transaksi.is_program)>0 THEN 1
            ELSE 0
        END AS is_program
        FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id WHERE transaksi.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY detail_transaksi.id_transaksi  ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= "UNION ALL " ;
        //         $query .= "SELECT SUM(`$detail_transactions`.qty*`$detail_transactions`.harga_satuan) total, `$transactions`.promo, `$transactions`.program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.tax, `$transactions`.charge_ur, `$transactions`.point, payment_method.nama as name, `$transactions`.tipe_bayar, `$transactions`.charge_ewallet, `$transactions`.surcharge_id, `$transactions`.surcharge_percent, surcharges.name surcharge_name,
        //         CASE
        //             WHEN SUM(`$detail_transactions`.is_program)>0 THEN 1
        //             ELSE 0
        //         END AS is_program
        //         FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=`$transactions`.surcharge_id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id WHERE `$transactions`.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.surcharge_id!=0 GROUP BY `$detail_transactions`.id_transaksi  ";
        //     }
        // }
        // $query .=" ORDER BY surcharge_id, surcharge_percent ";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeMaster($idMaster, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name, CASE WHEN SUM(detail_transaksi.is_program)>0 THEN 1 ELSE 0 END AS is_program FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY surcharge_name";

        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeMasterWithHour($idMaster, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name, CASE WHEN SUM(detail_transaksi.is_program)>0 THEN 1 ELSE 0 END AS is_program FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY surcharge_name";

        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeAll($idMaster, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name, CASE WHEN SUM(detail_transaksi.is_program)>0 THEN 1 ELSE 0 END AS is_program FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY detail_transaksi.id_transaksi";

        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeAllWithHour($idMaster, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name, CASE WHEN SUM(detail_transaksi.is_program)>0 THEN 1 ELSE 0 END AS is_program FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY detail_transaksi.id_transaksi";

        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeTenant($id, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name,
        CASE
            WHEN SUM(detail_transaksi.is_program)>0 THEN 1
            ELSE 0
        END AS is_program
        FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY detail_transaksi.id_transaksi  ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= "UNION ALL " ;
        //         $query .= "SELECT SUM(`$detail_transactions`.qty*`$detail_transactions`.harga_satuan) total, `$transactions`.promo, `$transactions`.program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.tax, `$transactions`.charge_ur, `$transactions`.point, payment_method.nama as name, `$transactions`.tipe_bayar, `$transactions`.charge_ewallet, `$transactions`.surcharge_id, `$transactions`.surcharge_percent, surcharges.name surcharge_name,
        //         CASE
        //             WHEN SUM(`$detail_transactions`.is_program)>0 THEN 1
        //             ELSE 0
        //         END AS is_program
        //         FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=`$transactions`.surcharge_id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.surcharge_id!=0 GROUP BY `$detail_transactions`.id_transaksi  ";
        //     }
        // }
        // $query .=" ORDER BY surcharge_id, surcharge_percent ";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getBySurchargeTenantWithHour($id, $dateFrom, $dateTo)
    {
        $result = array();
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        $query = "SELECT SUM(detail_transaksi.qty*detail_transaksi.harga_satuan) total, transaksi.promo, transaksi.program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.tax, transaksi.charge_ur, transaksi.point, payment_method.nama as name, transaksi.tipe_bayar, transaksi.charge_ewallet, transaksi.surcharge_id, transaksi.surcharge_percent, surcharges.name surcharge_name,
        CASE
            WHEN SUM(detail_transaksi.is_program)>0 THEN 1
            ELSE 0
        END AS is_program
        FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=transaksi.surcharge_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.surcharge_id!=0 GROUP BY detail_transaksi.id_transaksi  ";
        // $dateFromStr = str_replace("-","", $dateFrom);
        // $dateToStr = str_replace("-","", $dateTo);
        // $queryTrans = "SELECT table_name FROM information_schema.tables
        // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        // $transaksi = mysqli_query($db_conn, $queryTrans);
        // while($row=mysqli_fetch_assoc($transaksi)){
        //     $table_name = explode("_",$row['table_name']);
        //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
        //         $query .= "UNION ALL " ;
        //         $query .= "SELECT SUM(`$detail_transactions`.qty*`$detail_transactions`.harga_satuan) total, `$transactions`.promo, `$transactions`.program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.tax, `$transactions`.charge_ur, `$transactions`.point, payment_method.nama as name, `$transactions`.tipe_bayar, `$transactions`.charge_ewallet, `$transactions`.surcharge_id, `$transactions`.surcharge_percent, surcharges.name surcharge_name,
        //         CASE
        //             WHEN SUM(`$detail_transactions`.is_program)>0 THEN 1
        //             ELSE 0
        //         END AS is_program
        //         FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN surcharges ON surcharges.id=`$transactions`.surcharge_id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.surcharge_id!=0 GROUP BY `$detail_transactions`.id_transaksi  ";
        //     }
        // }
        // $query .=" ORDER BY surcharge_id, surcharge_percent ";
        $trxQ = mysqli_query($db_conn, $query);

        $i = 0;
        $j = 0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $service = 0;
        $tax = 0;
        $charge_ur = 0;
        $charge_ewallet = 0;
        $tempCompare = "";
        $tempCompare1 = "";

        while ($row = mysqli_fetch_assoc($trxQ)) {
            if ($i == 0 || ($row['surcharge_id'] != $tempCompare || $row['surcharge_percent'] != $tempCompare1)) {
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $charge_ewallet = 0;
                if ($i != 0) {
                    $j += 1;
                }
            }
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
            $tax += $tempT;
            $result[$j]['payment_method_name'] = $row['name'];
            $result[$j]['surcharge_id'] = $row['surcharge_id'];
            $result[$j]['surcharge_percent'] = $row['surcharge_percent'];
            $result[$j]['surcharge_name'] = $row['surcharge_name'];
            $result[$j]['is_program'] = $row['is_program'];
            $result[$j]['value'] = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
            $result[$j]['surcharge'] = $result[$j]['value'] - ceil(100 / (100 + $row['surcharge_percent']) * $subtotal);
            $result[$j]['charge_ur'] = $charge_ur;
            $result[$j]['point'] = $point;
            // $result[$j]['tipe']=$row['tipe_bayar'];

            $tempCompare = $row['surcharge_id'];
            $tempCompare1 = $row['surcharge_percent'];
            $i += 1;
        }

        return $result;
    }

    public function getByTransactionType($id, $dateFrom, $dateTo, $idMaster)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "";
        if (isset($idMaster)) {
            $query = "SELECT trx.name, trx.type, COUNT(trx.id) AS qty, SUM(trx.total) AS total , SUM(trx.promo) AS promo, SUM(trx.program_discount) AS program_discount, SUM(trx.diskon_spesial) AS diskon_spesial, SUM(trx.employee_discount) as employee_discount, SUM(trx.point) AS point, SUM(trx.service) AS service, SUM(trx.tax) AS tax, SUM(trx.charge_ur) AS charge_ur, SUM(trx.ongkir) AS delivery_fee, SUM( trx.total - trx.promo - trx.program_discount - trx.diskon_spesial - trx.point + trx.service + trx.tax + trx.charge_ur + trx.ongkir ) as sales FROM ( SELECT t.id, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'Dine In' WHEN t.takeaway = 1 THEN 'Takeaway' WHEN t.pre_order_id != 0 THEN 'Preorder' WHEN d.id IS NOT NULL THEN 'Delivery' ELSE 'none' END as name, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'dinein' WHEN t.takeaway = 1 THEN 'takeaway' WHEN t.pre_order_id != 0 THEN 'preorder' WHEN d.id IS NOT NULL THEN 'delivery' ELSE 'none' END as type, t.id_partner, t.jam, d.rate_id, t.total, t.promo, t.program_discount, t.diskon_spesial, t.employee_discount, t.point, ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 AS service, ( ( ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 )+ t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point + t.charge_ur )* t.tax / 100 AS tax, t.charge_ur, IFNULL( CASE WHEN d.rate_id = 0 THEN d.ongkir ELSE 0 END, 0 ) as ongkir FROM transaksi AS t LEFT JOIN delivery AS d ON t.id = d.transaksi_id JOIN partner p ON p.id=t.id_partner WHERE t.deleted_at IS NULL AND t.status IN (1, 2) AND p.id_master = '$idMaster' AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' ) AS trx GROUP BY trx.type";
        } else {
            $query = "SELECT trx.name, trx.type, COUNT(trx.id) AS qty, SUM(trx.total) AS total , SUM(trx.promo) AS promo, SUM(trx.program_discount) AS program_discount, SUM(trx.diskon_spesial) AS diskon_spesial, SUM(trx.employee_discount) as employee_discount, SUM(trx.point) AS point, SUM(trx.service) AS service, SUM(trx.tax) AS tax, SUM(trx.charge_ur) AS charge_ur, SUM(trx.ongkir) AS delivery_fee, SUM( trx.total - trx.promo - trx.program_discount - trx.diskon_spesial - trx.point + trx.service + trx.tax + trx.charge_ur + trx.ongkir ) as sales FROM ( SELECT t.id, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'Dine In' WHEN t.takeaway = 1 THEN 'Takeaway' WHEN t.pre_order_id != 0 THEN 'Preorder' WHEN d.id IS NOT NULL THEN 'Delivery' ELSE 'none' END as name, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'dinein' WHEN t.takeaway = 1 THEN 'takeaway' WHEN t.pre_order_id != 0 THEN 'preorder' WHEN d.id IS NOT NULL THEN 'delivery' ELSE 'none' END as type, t.id_partner, t.jam, d.rate_id, t.total, t.promo, t.program_discount, t.diskon_spesial, t.employee_discount, t.point, ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 AS service, ( ( ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 )+ t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point + t.charge_ur )* t.tax / 100 AS tax, t.charge_ur, IFNULL( CASE WHEN d.rate_id = 0 THEN d.ongkir ELSE 0 END, 0 ) as ongkir FROM transaksi AS t LEFT JOIN delivery AS d ON t.id = d.transaksi_id WHERE t.deleted_at IS NULL AND t.status IN (1, 2) AND t.id_partner = '$id' AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ) AS trx GROUP BY trx.type";
        }

        $sql     = mysqli_query($db_conn, $query);
        $data    = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $result  = array();
        foreach ($data as $v) {
            $item['name'] = $v['name'];
            $item['type'] = $v['type'];
            $item['qty'] = (int) $v['qty'];
            $item['subtotal'] = (int)  $v['total'];
            $item['sales'] = (int) ($v['total'] + $v['service'] + $v['tax'] + $v['charge_ur']);
            $item['promo'] = (int) $v['promo'];
            $item['program_discount'] = (int) $v['program_discount'];
            $item['diskon_spesial'] = (int) $v['diskon_spesial'];
            $item['employee_discount'] = (int) $v['employee_discount'];
            $item['point'] = (int) $v['point'];
            $item['clean_sales'] = (int) ($item['sales'] - $v['promo'] - $v['program_discount'] - $v['diskon_spesial'] - $v['employee_discount'] - $v['point'] + $v['delivery_fee']);
            $item['service'] = (int) $v['service'];
            $item['charge_ur'] = (int) $v['charge_ur'];
            $item['tax'] = (float) $v['tax'];
            $item['delivery_fee_resto'] = (int) $v['delivery_fee'];
            $item['total'] = (int) $v['sales'];
            array_push($result, $item);
        }
        return $result;
    }

    public function getByTransactionTypeWithHour($id, $dateFrom, $dateTo, $idMaster)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "";
        if (isset($idMaster)) {
            $query = "SELECT trx.name, trx.type, COUNT(trx.id) AS qty, SUM(trx.total) AS total , SUM(trx.promo) AS promo, SUM(trx.program_discount) AS program_discount, SUM(trx.diskon_spesial) AS diskon_spesial, SUM(trx.employee_discount) as employee_discount, SUM(trx.point) AS point, SUM(trx.service) AS service, SUM(trx.tax) AS tax, SUM(trx.charge_ur) AS charge_ur, SUM(trx.ongkir) AS delivery_fee, SUM( trx.total - trx.promo - trx.program_discount - trx.diskon_spesial - trx.point + trx.service + trx.tax + trx.charge_ur + trx.ongkir ) as sales FROM ( SELECT t.id, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'Dine In' WHEN t.takeaway = 1 THEN 'Takeaway' WHEN t.pre_order_id != 0 THEN 'Preorder' WHEN d.id IS NOT NULL THEN 'Delivery' ELSE 'none' END as name, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'dinein' WHEN t.takeaway = 1 THEN 'takeaway' WHEN t.pre_order_id != 0 THEN 'preorder' WHEN d.id IS NOT NULL THEN 'delivery' ELSE 'none' END as type, t.id_partner, t.jam, d.rate_id, t.total, t.promo, t.program_discount, t.diskon_spesial, t.employee_discount, t.point, ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 AS service, ( ( ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 )+ t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point + t.charge_ur )* t.tax / 100 AS tax, t.charge_ur, IFNULL( CASE WHEN d.rate_id = 0 THEN d.ongkir ELSE 0 END, 0 ) as ongkir FROM transaksi AS t LEFT JOIN delivery AS d ON t.id = d.transaksi_id JOIN partner p ON p.id=t.id_partner WHERE t.deleted_at IS NULL AND t.status IN (1, 2) AND p.id_master = '$idMaster' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' ) AS trx GROUP BY trx.type";
        } else {
            $query   = "SELECT trx.name, trx.type, COUNT(trx.id) AS qty, SUM(trx.total) AS total , SUM(trx.promo) AS promo, SUM(trx.program_discount) AS program_discount, SUM(trx.diskon_spesial) AS diskon_spesial, SUM(trx.employee_discount) as employee_discount, SUM(trx.point) AS point, SUM(trx.service) AS service, SUM(trx.tax) AS tax, SUM(trx.charge_ur) AS charge_ur, SUM(trx.ongkir) AS delivery_fee, SUM( trx.total - trx.promo - trx.program_discount - trx.diskon_spesial - trx.point + trx.service + trx.tax + trx.charge_ur + trx.ongkir ) as sales FROM ( SELECT t.id, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'Dine In' WHEN t.takeaway = 1 THEN 'Takeaway' WHEN t.pre_order_id != 0 THEN 'Preorder' WHEN d.id IS NOT NULL THEN 'Delivery' ELSE 'none' END as name, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'dinein' WHEN t.takeaway = 1 THEN 'takeaway' WHEN t.pre_order_id != 0 THEN 'preorder' WHEN d.id IS NOT NULL THEN 'delivery' ELSE 'none' END as type, t.id_partner, t.jam, d.rate_id, t.total, t.promo, t.program_discount, t.diskon_spesial, t.employee_discount, t.point, ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 AS service, ( ( ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 )+ t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point + t.charge_ur )* t.tax / 100 AS tax, t.charge_ur, IFNULL( CASE WHEN d.rate_id = 0 THEN d.ongkir ELSE 0 END, 0 ) as ongkir FROM transaksi AS t LEFT JOIN delivery AS d ON t.id = d.transaksi_id WHERE t.deleted_at IS NULL AND t.status IN (1, 2) AND t.id_partner = '$id' AND t.paid_date BETWEEN '$dateFrom' AND '$dateTo' ) AS trx GROUP BY trx.type";
        }

        $sql     = mysqli_query($db_conn, $query);
        $data    = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $result  = array();
        foreach ($data as $v) {
            $item['name'] = $v['name'];
            $item['type'] = $v['type'];
            $item['qty'] = (int) $v['qty'];
            $item['subtotal'] = (int)  $v['total'];
            $item['sales'] = (int) ($v['total'] + $v['service'] + $v['tax'] + $v['charge_ur']);
            $item['promo'] = (int) $v['promo'];
            $item['program_discount'] = (int) $v['program_discount'];
            $item['diskon_spesial'] = (int) $v['diskon_spesial'];
            $item['employee_discount'] = (int) $v['employee_discount'];
            $item['point'] = (int) $v['point'];
            $item['clean_sales'] = (int) ($item['sales'] - $v['promo'] - $v['program_discount'] - $v['diskon_spesial'] - $v['employee_discount'] - $v['point'] + $v['delivery_fee']);
            $item['service'] = (int) $v['service'];
            $item['charge_ur'] = (int) $v['charge_ur'];
            $item['tax'] = (float) $v['tax'];
            $item['delivery_fee_resto'] = (int) $v['delivery_fee'];
            $item['total'] = (int) $v['sales'];
            array_push($result, $item);
        }
        return $result;
    }
}
