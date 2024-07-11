<?php



date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class ForgotPass
{
    private $conn;
    private $conn2;

    //Constructor
    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';

        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
        $this->conn2 = $db->connect();
    }

    public function forgotPassword($email, $source)
    {
        date_default_timezone_set('Asia/Jakarta');
        $dates1 = date('Y-m-d h:i:s');
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        $tablename = $source;

        for ($i = 0; $i < 12; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        if ($tablename == "users" || $tablename == "sa_users") {
            $getUser = $this->conn->query("SELECT name, phone, email FROM `$tablename` WHERE email='$email'");
        } else if ($tablename == "employees") {
            $getUser = $this->conn->query("SELECT nama, phone, email, id_partner FROM `$tablename` WHERE email='$email'");
        }
        if ($getUser->num_rows > 0) {

            $stmt = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $tablename, $randomString, $dates1, $email);
            $inserReset = $stmt->execute();

            if ($inserReset) {
                if ($tablename == "users" || $tablename == "sa_users") {
                    $name = $getUser->fetch_assoc()['name'];
                } else if ($tablename == "employees") {
                    $name = $getUser->fetch_assoc()['nama'];
                }
                // disini ambil template
                $query = "SELECT template FROM `email_template` WHERE id=4";
                $getTemplate = $this->conn->query($query);
                $template = $getTemplate->fetch_assoc()['template'];

                // ganti $name dengan nama yang sudah di dapatkan
                $template = str_replace('$name', $name, $template);
                $template = str_replace('$randomString', $randomString, $template);

                // masukan ke pending email
                $emailSubject = "RESET PASSWORD UR";

                $partner_id = $getUser->fetch_assoc()['id_partner'];
                $insertPending = $this->conn->prepare("INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`) VALUES (?, ?, ?, ?)");
                $insertPending->bind_param("ssss", $email, $partner_id, $emailSubject, $template);
                $insertPending->execute();
            } else {
                return "ERROR";
            }
        } else {
            return "USER_NOT_FOUND";
        }

        return "SUCCESS";
    }
}
