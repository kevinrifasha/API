<?php



date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class DeleteUser
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

    public function deleteUser($email, $org)
    {
        date_default_timezone_set('Asia/Jakarta');
        $dates1 = date('Y-m-d h:i:s');
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        $tablename = "users";
        $operation = "delete-account";

        for ($i = 0; $i < 12; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $getUser = $this->conn->query("SELECT name, phone, email, organization FROM `users` WHERE email='$email' AND organization='$org' AND deleted_at IS NULL");
        if ($getUser->num_rows > 0) {

            $stmt = $this->conn->prepare("INSERT INTO reset_password (tablename, operation, token, created_at, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $tablename, $operation, $randomString, $dates1, $email);
            $inserReset = $stmt->execute();

            if ($inserReset) {
                $user = $getUser->fetch_assoc();
                // disini ambil template
                $templateName = "delete-account-ur";
                $name = $user['name'];
                $subjectOrg = "UR";
                if (($tablename == "users" || $tablename == "employees") && ($org == "Natta")){
                    $idTemplate = "delete-account-natta";
                    $subjectOrg = "Natta";
                }
                $query = "SELECT template FROM `email_template` WHERE name='$templateName'";
                $getTemplate = $this->conn->query($query);
                $template = $getTemplate->fetch_assoc()['template'];

                // ganti $name dengan nama yang sudah di dapatkan
                $template = str_replace('$name', $name, $template);
                $template = str_replace('$randomString', $randomString, $template);

                // masukan ke pending email
                
                $emailSubject = "DELETE ACCOUNT - $subjectOrg";

                $partner_id = null;
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
    
    public function deleteEmployee($email, $partnerId, $org){
        date_default_timezone_set('Asia/Jakarta');
        $dates1 = date('Y-m-d h:i:s');
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        $tablename = "employees";
        $operation = "delete-account";

        for ($i = 0; $i < 12; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $getUser = $this->conn->query("SELECT nama AS name, phone, email, organization FROM employees WHERE email='$email' AND id_partner='$partnerId' AND organization='$org' AND deleted_at IS NULL");
        if ($getUser->num_rows > 0) {
            $stmt = $this->conn->prepare("INSERT INTO reset_password (tablename, operation, token, created_at, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $tablename, $operation, $randomString, $dates1, $email);
            $inserReset = $stmt->execute();

            if ($inserReset) {
                $user = $getUser->fetch_assoc();
                // disini ambil template
                $templateName = "delete-employee-ur";
                $name = $user['name'];
                $subjectOrg = "UR";
                if (($tablename == "users" || $tablename == "employees") && ($org == "Natta")){
                    $templateName = "delete-employee-natta";
                    $subjectOrg = "Natta";
                }
                $query = "SELECT template FROM `email_template` WHERE name='$templateName'";
                $getTemplate = $this->conn->query($query);
                $template = $getTemplate->fetch_assoc()['template'];

                // ganti $name dengan nama yang sudah di dapatkan
                $template = str_replace('$name', $name, $template);
                $template = str_replace('$partnerId', $partnerId, $template);
                $template = str_replace('$randomString', $randomString, $template);

                // masukan ke pending email
                
                $emailSubject = "DELETE ACCOUNT - $subjectOrg";

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
    
    public function applyDelete($token, $org){
        $getUser = $this->conn->query("
            SELECT
            	u.id,
            	u.phone
            FROM users u
            INNER JOIN reset_password rp ON rp.email = u.email
            WHERE rp.token='$token'
            AND u.organization='$org'
            AND u.deleted_at IS NULL"
        );
        if ($getUser->num_rows > 0) {
            $user = $getUser->fetch_assoc();
            $id = $user['id'];
            $phone = $user['phone'];
            $stmt = $this->conn->query("UPDATE users SET deleted_at=NOW() WHERE id='$id'");
            $deviceTokens = $this->conn->query("
                SELECT
                    dt.tokens
                FROM device_tokens dt
                LEFT JOIN users u ON u.phone=dt.user_phone
                WHERE dt.user_phone = '$phone'
                AND dt.deleted_at IS NULL
                AND u.organization='$org'");
            while ($device = $deviceTokens->fetch_assoc()){
                $dt = $device['tokens'];
                $this->conn->query("
                    INSERT INTO pending_notification (
                        dev_token,
                        title,
                        message,
                        channel_id
                    ) VALUES (
                        '$dt',
                        'DELETE ACCOUNT',
                        'Hapus Akun Berhasil',
                        'ur-user'
                    )
                ");
            }
        } else {
            return "USER_NOT_FOUND";
        }

        return "SUCCESS";
    }
    
    public function applyDeleteEmployee($token, $partner_id, $org){
        $getUser = $this->conn->query("
            SELECT
                e.id
            FROM employees e
            INNER JOIN partner p ON p.id=e.id_partner
            INNER JOIN reset_password rp ON rp.email = e.email
            WHERE rp.token='$token'
            AND e.id_partner='$partner_id'
            AND e.organization='$org'
            AND e.deleted_at IS NULL"
        );
        if ($getUser->num_rows > 0) {
            $id = $getUser->fetch_assoc()['id'];

            $stmt = $this->conn->query("UPDATE employees SET deleted_at=NOW() WHERE id='$id'");
            
            $deviceTokens = $this->conn->query("
                SELECT
                    dt.tokens
                FROM device_tokens dt
                LEFT JOIN employees e ON e.id=dt.employee_id
                WHERE dt.employee_id = '$id'
                AND dt.deleted_at IS NULL
                AND e.organization='$org'");
            while ($device = $deviceTokens->fetch_assoc()){
                $dt = $device['tokens'];
                $this->conn->query("
                    INSERT INTO pending_notification (
                        dev_token,
                        title,
                        message,
                        channel_id
                    ) VALUES (
                        '$dt',
                        'DELETE ACCOUNT',
                        'Hapus Akun Berhasil',
                        'rn-push-notification-channel'
                    )
                ");
            }
        } else {
            return "USER_NOT_FOUND";
        }

        return "SUCCESS";
    }
}
