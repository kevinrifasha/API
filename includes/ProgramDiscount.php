<?php

date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Endroid\QrCode\QrCode;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

class Programs
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

    public function ProgramDiscount($partnerID, $transaction_type, $total, $dataDetail)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $today = date("Y-m-d");
        $time = date("H:i:s");
        $dayNameEng = date('D');
        $dayNameInd = "";
        if ($dayNameEng == "Mon") {
            $dayNameInd = "SENIN";
        } else if ($dayNameEng == "Tue") {
            $dayNameInd = "SELASA";
        } else if ($dayNameEng == "Wed") {
            $dayNameInd = "RABU";
        } else if ($dayNameEng == "Thu") {
            $dayNameInd = "KAMIS";
        } else if ($dayNameEng == "Fri") {
            $dayNameInd = "JUMAT";
        } else if ($dayNameEng == "Sat") {
            $dayNameInd = "SABTU";
        } else {
            $dayNameInd = "MINGGU";
        }
        $typeT = $transaction_type;
        
        $menuList = [];
        
        $qP = mysqli_query($db_conn, "SELECT `id`, `master_program_id`,`title`, `prerequisite_menu`, `prerequisite_category`, `discount_type`, `discount_percentage`, `maximum_discount`, `payment_method`, `minimum_value` FROM `programs` WHERE `partner_id`='$partnerID' AND master_program_id!='1' AND `deleted_at` IS NULL AND `enabled`='1' AND '$today' BETWEEN `valid_from` AND  `valid_until` AND ((`start_hour`='00:00:00' AND `end_hour`='00:00:00') OR ('$time' BETWEEN `start_hour` AND `end_hour`)) AND (`day`='' OR `day` IS NULL OR `day` LIKE '%$dayNameInd%') AND (`transaction_type`='' OR `transaction_type` IS NULL OR `transaction_type` LIKE '%$typeT%') AND `minimum_value`<='$total' ORDER BY id DESC LIMIT 1");
        if (mysqli_num_rows($qP) > 0) {
            $resP = mysqli_fetch_all($qP, MYSQLI_ASSOC);
            if ($resP[0]['discount_type'] == '1') {
                $value = ($total * (int) $resP[0]['discount_percentage']) / 100;
                if (isset($resP[0]['maximum_discount']) && !empty($resP[0]['maximum_discount'])) {
                    if ($value > (int) $resP[0]['maximum_discount']) {
                        $array = array();
                        $array['payment_method'] = $resP[0]['payment_method'];
                        $array['id'] = $resP[0]['id'];
                        $array['discount'] = (int) $resP[0]['maximum_discount'];
                        $array['discountType'] = '1';
                        $array['title'] = $resP[0]['title'];
                        $array['percentage'] = $resP[0]['discount_percentage'];
                        return $array;
                    } else {
                        $array = array();
                        $array['payment_method'] = $resP[0]['payment_method'];
                        $array['id'] = $resP[0]['id'];
                        $array['discount'] = $value;
                        $array['discountType'] = '1';
                        $array['title'] = $resP[0]['title'];
                        $array['percentage'] = $resP[0]['discount_percentage'];
                        return $array;
                    }
                } else {
                    $array = array();
                    $array['payment_method'] = $resP[0]['payment_method'];
                    $array['id'] = $resP[0]['id'];
                    $array['discount'] = $value;
                    $array['discountType'] = '1';
                    $array['title'] = $resP[0]['title'];
                    $array['percentage'] = $resP[0]['discount_percentage'];
                    return $array;
                }
            } else if ($resP[0]['discount_type'] == '2') {
                $json_menu = json_decode($resP[0]['prerequisite_menu']);
                $tempTot = 0;
                
                foreach ($dataDetail as $cart) {
                    if (isset($cart->is_program) && !empty($cart->is_program)) {
                    } else {
                        foreach ($json_menu as $value) {
                            // ini untuk v8
                            if ($cart->id == $value->id) {
                                $tempTot += (int) $cart->price * (int) $cart->qty;
                                $menu = [];
                                $menu['id_menu'] = $cart->id;
                                $menu['menu'] = $cart->data->nama;
                                $menu['discountTotalMenu'] = ($cart->totalPrice * (int) $resP[0]['discount_percentage']) / 100;
                                $menu['discountPerQty'] = ($cart->price * (int) $resP[0]['discount_percentage']) / 100;
                                
                                array_push($menuList, $menu);
                            } 
                            // ini untuk v5
                            else if ($cart->id_menu == $value->id) {
                                $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                $menu = [];
                                $menu['id_menu'] = $cart->id_menu;
                                $menu['menu'] = $cart->data->nama;
                                $menu['discountTotalMenu'] = ($cart->harga * (int) $resP[0]['discount_percentage']) / 100;
                                $menu['discountPerQty'] = ($cart->harga_satuan * (int) $resP[0]['discount_percentage']) / 100;
                                
                                array_push($menuList, $menu);
                            }
                        }
                    }
                }
                if ($tempTot >= $resP[0]['minimum_value']) {
                    $value = ($tempTot * (int) $resP[0]['discount_percentage']) / 100;
                    if (isset($resP[0]['maximum_discount']) && !empty($resP[0]['maximum_discount'])) {
                        if ($value > (int) $resP[0]['maximum_discount']) {
                            $array = array();
                            $array['payment_method'] = $resP[0]['payment_method'];
                            $array['id'] = $resP[0]['id'];
                            $array['discount'] = (int) $resP[0]['maximum_discount'];
                            $array['discountType'] = '2';
                            $array['title'] = $resP[0]['title'];
                            $array['percentage'] = $resP[0]['discount_percentage'];
                            $array['menuList'] = $menuList;
                            return $array;
                        } else {
                            $array = array();
                            $array['payment_method'] = $resP[0]['payment_method'];
                            $array['id'] = $resP[0]['id'];
                            $array['discount'] = $value;
                            $array['discountType'] = '2';
                            $array['title'] = $resP[0]['title'];
                            $array['percentage'] = $resP[0]['discount_percentage'];
                            $array['menuList'] = $menuList;
                            return $array;
                        }
                    } else {
                        $array = array();
                        $array['payment_method'] = $resP[0]['payment_method'];
                        $array['id'] = $resP[0]['id'];
                        $array['discount'] = $value;
                        $array['discountType'] = '2';
                        $array['title'] = $resP[0]['title'];
                        $array['percentage'] = $resP[0]['discount_percentage'];
                        $array['menuList'] = $menuList;
                        return $array;
                    }
                } else {
                    $array = array();
                    // $array['payment_method'] = $resP[0]['payment_method'];
                    $array['payment_method'] = [];
                    // $array['id'] = $resP[0]['id'];
                    $array['id'] = "";
                    $array['discount'] = 0;
                    // $array['discountType'] = '2';
                    $array['discountType'] = '0';
                    // $array['title'] = $resP[0]['title'];
                    $array['title'] = "";
                    // $array['percentage'] = $resP[0]['discount_percentage'];
                    $array['percentage'] = 0;
                    $array['menuList'] = $menuList;
                    return $array;
                }
            } else if ($resP[0]['discount_type'] == '3') {
                $json_categories = json_decode($resP[0]['prerequisite_category']);
                $tempTot = 0;

                foreach ($dataDetail as $cart) {
                    if (isset($cart->is_program) && !empty($cart->is_program)) {
                    } else {
                        
                        if(isset($cart->id)) {
                            // if v8
                            $qC = mysqli_query($db_conn, "SELECT id_category FROM `menu` WHERE id='$cart->id'");
                        } else {
                            // if v5
                            $qC = mysqli_query($db_conn, "SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                        }
                        
                        if (mysqli_num_rows($qC) > 0) {
                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                            foreach ($json_categories as $value) {
                                if ($resC[0]['id_category'] == $value->id) {
                                    $menu = [];
                                    if(isset($cart->id)) {
                                        $tempTot += (int) $cart->price * (int) $cart->qty;
                                        $menu['id_menu'] = $cart->id;
                                    } else {
                                        $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                        $menu['id_menu'] = $cart->id_menu;
                                    }
                                    $menu['menu'] = $cart->data->nama;
                                    $menu['discountTotalMenu'] = ($cart->totalPrice * (int) $resP[0]['discount_percentage']) / 100;
                                    $menu['discountPerQty'] = ($cart->price * (int) $resP[0]['discount_percentage']) / 100;
                                    
                                    array_push($menuList, $menu);
                                }
                            }
                        }
                    }
                }
                if ($tempTot >= $resP[0]['minimum_value']) {
                    $value = ($tempTot * (int) $resP[0]['discount_percentage']) / 100;
                    if (isset($resP[0]['maximum_discount']) && !empty($resP[0]['maximum_discount'])) {
                        if ($value > (int) $resP[0]['maximum_discount']) {
                            $array = array();
                            $array['payment_method'] = $resP[0]['payment_method'];
                            $array['id'] = $resP[0]['id'];
                            $array['discount'] = (int) $resP[0]['maximum_discount'];
                            $array['discountType'] = '3';
                            $array['title'] = $resP[0]['title'];
                            $array['percentage'] = $resP[0]['discount_percentage'];
                            $array['menuList'] = $menuList;
                            return $array;
                        } else {
                            $array = array();
                            $array['payment_method'] = $resP[0]['payment_method'];
                            $array['id'] = $resP[0]['id'];
                            $array['discount'] = $value;
                            $array['discountType'] = '3';
                            $array['title'] = $resP[0]['title'];
                            $array['percentage'] = $resP[0]['discount_percentage'];
                            $array['menuList'] = $menuList;
                            return $array;
                        }
                    } else {
                        $array = array();
                        $array['payment_method'] = $resP[0]['payment_method'];
                        $array['id'] = $resP[0]['id'];
                        $array['discount'] = $value;
                        $array['discountType'] = '3';
                        $array['title'] = $resP[0]['title'];
                        $array['percentage'] = $resP[0]['discount_percentage'];
                        $array['menuList'] = $menuList;
                        return $array;
                    }
                } else {
                    $array = array();
                    // $array['payment_method'] = $resP[0]['payment_method'];
                    $array['payment_method'] = [];
                    // $array['id'] = $resP[0]['id'];
                    $array['id'] = "";
                    $array['discount'] = 0;
                    // $array['discountType'] = '3';
                    $array['discountType'] = '0';
                    // $array['title'] = $resP[0]['title'];
                    $array['title'] = "";
                    // $array['percentage'] = $resP[0]['discount_percentage'];
                    $array['percentage'] = 0;
                    $array['menuList'] = $menuList;
                    return $array;
                }
            }
        } else {
            $array = array();
            $array['payment_method'] = array();
            $array['id'] = '0';
            $array['discount'] = '0';
            $array['discountType'] = '0';
            $array['title'] = "";
            $array['percentage'] = '0';
            $array['menuList'] = $menuList;
            return $array;
        }
    }
}
