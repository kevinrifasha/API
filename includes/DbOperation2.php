<?php
date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Endroid\QrCode\QrCode;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

class DbOperation
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

    public function getDetailTrxByIDTrx($id)
    {
        $stmt = $this->conn->prepare("SELECT a.id, a.id_transaksi, a.id_menu, a.harga_satuan, a.qty, a.notes, a.harga, a.variant, a.status, b.nama, c.queue, c.takeaway FROM detail_transaksi a, menu b, transaksi c WHERE a.id_transaksi = ? AND a.id_menu = b.id AND a.id_transaksi = c.id");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->bind_result($id, $id_transaksi, $id_menu, $harga_satuan, $qty, $notes, $harga, $variant, $status, $menu_nama, $queue, $takeaway);
        $dtrx = array();
        $i = 0;
        while ($data = $stmt->fetch()) {
            $dtrx[$i]['id'] = $id;
            $dtrx[$i]['id_transaksi'] = $id_transaksi;
            $dtrx[$i]['id_menu'] = $id_menu;
            $dtrx[$i]['harga_satuan'] = $harga_satuan;
            $dtrx[$i]['qty'] = $qty;
            $dtrx[$i]['notes'] = $notes;
            $dtrx[$i]['harga'] = $harga;
            $dtrx[$i]['status'] = $status;
            $dtrx[$i]['menu_nama'] = $menu_nama;
            $dtrx[$i]['queue'] = $queue;
            $dtrx[$i]['takeaway'] = $takeaway;
            $variant = str_replace("'", '"', $variant);
            $variant = substr($variant, 11, -1);
            $variant = json_decode($variant);
            $dtrx[$i]['variant'] = $variant;
            $i++;
        }
        return $dtrx;
    }
}
