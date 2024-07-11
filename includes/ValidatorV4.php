<?php
class ValidatorV4{
    private $conn;
    private $db;
    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
    public function CheckShiftID($token, $ShiftID)
    {
        $active = false;
        $msg    = "Tidak ada pesan";
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $query = "SELECT id, end FROM shift WHERE partner_id = '$token->id_partner' and id='$ShiftID' ";
        $trxQ = mysqli_query($db_conn, $query);
        while ($row = mysqli_fetch_assoc($trxQ)) {
            if($row["end"] != null){
                $active = true;
                $msg = "Shift anda sudah diselesaikan pada jam ";
                $msg .= $row["end"];
                $msg .= " silakan cek kembali shift Anda";
            }
        }
        if($active == true){
            echo json_encode(["success"=>0, "status"=>204, "msg"=>$msg]);
        }
    }
    public function checkShiftIDActive($db_conn,$token)
    {
        $active = false;
        $msg    = "Tidak ada pesan";
        $query = "SELECT id FROM shift WHERE partner_id = '$token->id_partner' AND end IS NULL";
        $trxQ = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($trxQ) == 0){
            $active = true;
            $msg = "Maaf tidak ada shift yang berjalan silakan cek shift anda/mulai shift baru";
        }
        if($active == true){
            echo json_encode(["success"=>0, "status"=>204, "msg"=>$msg]);
            exit();
        }
    }
}
?>