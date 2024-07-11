<?php
require_once("transaction.php");
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();


class TransactionManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function addPartner(user $usr)
  {
    $q = $this->_db->prepare('INSERT INTO partner SET email = :email, password = :password');
    $q->bindValue(':email', $usr->email());
    $q->bindValue(':password', $usr->password());
    $q->execute();
  }

  public function update(transaction $transaction)
  {
    $id = $transaction->getId();
    $jam = $transaction->getJam();
    $phone = $transaction->getPhone();
    $id_partner = $transaction->getId_partner();
    $no_meja = $transaction->getNo_meja();
    $no_meja_foodcourt = $transaction->getNo_meja_foodcourt();
    $status = $transaction->getStatus();
    $total = $transaction->getTotal();
    $id_voucher = $transaction->getId_voucher();
    $id_voucher_redeemable = $transaction->getId_voucher_redeemable();
    $tipe_bayar = $transaction->getTipe_bayar();
    $promo = $transaction->getPromo();
    $diskon_spesial = $transaction->getDiskon_spesial();
    $employee_discount = $transaction->getEmployee_discount();
    $point = $transaction->getPoint();
    $queue = $transaction->getQueue();
    $takeaway = $transaction->getTakeaway();
    $notes = $transaction->getNotes();
    $id_foodcourt = $transaction->getId_foodcourt();
    $tax = $transaction->getTax();
    $service = $transaction->getService();
    $charge_ewallet = $transaction->getCharge_ewallet();
    $charge_xendit = $transaction->getCharge_xendit();
    $charge_ur = $transaction->getCharge_ur();
    $confirm_at = $transaction->getConfirm_at();
    $status_callback = $transaction->getStatus_callback();
    $callback_hit = $transaction->getCallback_hit();

    $q = $this->_db->prepare("UPDATE `transaksi` SET `jam`='{$jam}',`phone`='{$phone}',`id_partner`='{$id_partner}',`no_meja`='{$no_meja}',`no_meja_foodcourt`='{$no_meja_foodcourt}',`status`='{$status}',`total`='{$total}',`id_voucher`='{$id_voucher}',`id_voucher_redeemable`='{$id_voucher_redeemable}',`tipe_bayar`='{$tipe_bayar}',`promo`='{$promo}',`diskon_spesial`='{$diskon_spesial}',`point`='{$point}',`queue`='{$queue}',`takeaway`='{$takeaway}',`notes`='{$notes}',`id_foodcourt`={$id_foodcourt},`tax`='{$tax}',`service`='{$service}',`charge_ewallet`='{$charge_ewallet}',`charge_xendit`='{$charge_xendit}',`charge_ur`='{$charge_ur}',`confirm_at`='{$confirm_at}',`status_callback`='{$status_callback}',`callback_hit`='{$callback_hit}', `employee_discount`='{$employee_discount}'  WHERE id= '{$id}'");
    return $q->execute();
  }

  public function getTransaction($id)
  {
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi WHERE id='{$id}' AND transaksi.deleted_at IS NULL ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions` WHERE id='{$id}' AND `$transactions`.deleted_at IS NULL ";
      }
    }
    $q = $this->_db->query($query);
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $trans = new Transaction($donnees);
      return $trans;
    }
  }

  public function getQueue($id_partner)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $var = 0;

    $q = $this->_db->query("SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '{$id_partner}' AND DATE(jam) = '{$dates1}' AND transaksi.deleted_at IS NULL LIMIT 1");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return 1;
    } else
      $queue = $donnees['LastQueue'] + 1;
    return $queue;
  }

  public function getOrder($id, $start, $load)
  {

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
      WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=5
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=7
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=8
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=9
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=1
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=2
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=3
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=4
      OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=6 ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions`
          WHERE id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=5
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=7
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=8
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=9
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=1
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=2
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=3
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=4
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=6 ";
      }
    }
    $query .= "ORDER BY jam DESC LIMIT $start,$load ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getOrderByCategoryId($id, $start, $load, $categoryId)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT transaksi.* FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
    WHERE menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi. tipe_bayar=5
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.tipe_bayar=7
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.tipe_bayar=8
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.tipe_bayar=9
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.status>0 AND transaksi.tipe_bayar=1
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.status>0 AND transaksi.tipe_bayar=2
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.status>0 AND transaksi.tipe_bayar=3
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.status>0 AND transaksi.tipe_bayar=4
    OR menu.id_category='{$categoryId}' AND transaksi.id_partner='{$id}' AND transaksi.deleted_at IS NULL AND transaksi.status<2 AND transaksi.status>0 AND transaksi.tipe_bayar=6 ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT `$transactions`.* FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
          WHERE menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`. tipe_bayar=5
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.tipe_bayar=7
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.tipe_bayar=8
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.tipe_bayar=9
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.status>0 AND `$transactions`.tipe_bayar=1
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.status>0 AND `$transactions`.tipe_bayar=2
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.status>0 AND `$transactions`.tipe_bayar=3
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.status>0 AND `$transactions`.tipe_bayar=4
          OR menu.id_category='{$categoryId}' AND `$transactions`.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<2 AND `$transactions`.status>0 AND `$transactions`.tipe_bayar=6 ";
      }
    }
    $query .= "ORDER BY jam DESC LIMIT $start,$load ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getHistory($id, $start, $load, $from, $to)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
    WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL
    AND DATE(jam) BETWEEN '{$from}' AND '{$to}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transaction`
          WHERE id_partner='{$id}' AND `$transaction`.deleted_at IS NULL
          AND DATE(jam) BETWEEN '{$from}' AND '{$to}'  ";
      }
    }
    $query .= "ORDER BY jam DESC LIMIT $start,$load ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getHistoryWithHour($id, $start, $load, $from, $to)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
    WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL
    AND jam BETWEEN '{$from}' AND '{$to}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transaction`
          WHERE id_partner='{$id}' AND `$transaction`.deleted_at IS NULL
          AND jam BETWEEN '{$from}' AND '{$to}'  ";
      }
    }
    $query .= "ORDER BY jam DESC LIMIT $start,$load ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getHistoryMaster($idMaster, $start, $load, $from, $to)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT t.* FROM transaksi t JOIN partner p ON p.id = t.id_partner
    WHERE p.id_master = '$idMaster' AND t.deleted_at IS NULL
    AND DATE(t.jam) BETWEEN '{$from}' AND '{$to}' ORDER BY t.id_partner ASC ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transaction` t JOIN partner p ON p.id = t.id_partner WHERE p.id_master='{$idMaster}' AND t.deleted_at IS NULL AND DATE(t.jam) BETWEEN '{$from}' AND '{$to}'  ";
      }
    }
    $query .= "ORDER BY jam DESC LIMIT $start,$load ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getHistoryMasterWithHour($idMaster, $start, $load, $from, $to)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT t.* FROM transaksi t JOIN partner p ON p.id = t.id_partner
    WHERE p.id_master = '$idMaster' AND t.deleted_at IS NULL
    AND t.jam BETWEEN '{$from}' AND '{$to}' ORDER BY t.id_partner ASC ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transaction` t JOIN partner p ON p.id = t.id_partner WHERE p.id_master='{$idMaster}' AND t.deleted_at IS NULL AND t.jam BETWEEN '{$from}' AND '{$to}'  ";
      }
    }
    $query .= "ORDER BY jam DESC LIMIT $start,$load ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getByTenantId($id, $from, $to, $type)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $field = 'paid_date';
    if ($type == 1) {
      $field = 'jam';
    }

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT transaksi.`id`, transaksi.`jam`, transaksi.`paid_date`, transaksi.`phone`, transaksi.`customer_name`, transaksi.`customer_email`, transaksi.`reference_id`, transaksi.`id_partner`, transaksi.`tenant_id`, transaksi.`shift_id`, transaksi.`no_meja`, transaksi.`no_meja_foodcourt`, transaksi.`status`, transaksi.`id_voucher`, transaksi.`id_voucher_redeemable`, transaksi.`program_id`, transaksi.`program_discount`, transaksi.`tipe_bayar`, transaksi.`promo`, transaksi.`diskon_spesial`, transaksi.`employee_discount`, transaksi.`point`, transaksi.`queue`, transaksi.`takeaway`, transaksi.`notes`, transaksi.`id_foodcourt`, transaksi.`tax`, transaksi.`service`, transaksi.`charge_ewallet`, transaksi.`charge_xendit`, transaksi.`charge_ur`, transaksi.`confirm_at`, transaksi.`status_callback`, transaksi.`callback_at`, transaksi.`callback_hit`, transaksi.`qr_string`, transaksi.`partner_note`, transaksi.`group_id`, transaksi.`pre_order_id`, transaksi.`surcharge_id`, transaksi.`surcharge_percent`, transaksi.`rated`, transaksi.`created_at`, transaksi.`updated_at`, transaksi.`deleted_at`, SUM(detail_transaksi.harga) AS total FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON detail_transaksi.id_menu=menu.id
    WHERE menu.id_partner='{$id}' AND transaksi.deleted_at IS NULL
    AND DATE($field) BETWEEN '{$from}' AND '{$to}' GROUP BY transaksi.id ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= " UNION ALL ";
        $query .= "SELECT `$transactions`.`id`, `$transactions`.`jam`, `$transactions`.`paid_date`, `$transactions`.`phone`, `$transactions`.`customer_name`, `$transactions`.`customer_email`, `$transactions`.`reference_id`, `$transactions`.`id_partner`, `$transactions`.`tenant_id`, `$transactions`.`shift_id`, `$transactions`.`no_meja`, `$transactions`.`no_meja_foodcourt`, `$transactions`.`status`, `$transactions`.`id_voucher`, `$transactions`.`id_voucher_redeemable`, `$transactions`.`program_id`, `$transactions`.`program_discount`, `$transactions`.`tipe_bayar`, `$transactions`.`promo`, `$transactions`.`diskon_spesial`, `$transactions`.`employee_discount`, `$transactions`.`point`, `$transactions`.`queue`, `$transactions`.`takeaway`, `$transactions`.`notes`, `$transactions`.`id_foodcourt`, `$transactions`.`tax`, `$transactions`.`service`, `$transactions`.`charge_ewallet`, `$transactions`.`charge_xendit`, `$transactions`.`charge_ur`, `$transactions`.`confirm_at`, `$transactions`.`status_callback`, `$transactions`.`callback_at`, `$transactions`.`callback_hit`, `$transactions`.`qr_string`, `$transactions`.`partner_note`, `$transactions`.`group_id`, `$transactions`.`pre_order_id`, `$transactions`.`surcharge_id`, `$transactions`.`surcharge_percent`, `$transactions`.`rated`, `$transactions`.`created_at`, `$transactions`.`updated_at`, `$transactions`.`deleted_at`, SUM(`$detail_transactions`.harga) AS total FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON `$detail_transactions`.id_menu=menu.id
          WHERE menu.id_partner='{$id}' AND `$transactions`.deleted_at IS NULL
          AND DATE($field) BETWEEN '{$from}' AND '{$to}' GROUP BY `$transactions`.id ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getByPartnerId($id, $from, $to, $type)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $field = 'paid_date';
    if ($type == 1) {
      $field = 'jam';
    }

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
    WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL
    AND DATE($field) BETWEEN '{$from}' AND '{$to}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions`
          WHERE id_partner='{$id}' AND `$transactions`.deleted_at IS NULL
          AND DATE($field) BETWEEN '{$from}' AND '{$to}' ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getByPartnerIdWithHour($id, $from, $to, $type)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $field = 'paid_date';
    if ($type == 1) {
      $field = 'jam';
    }

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
    WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL
    AND $field BETWEEN '{$from}' AND '{$to}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions`
          WHERE id_partner='{$id}' AND `$transactions`.deleted_at IS NULL
          AND $field BETWEEN '{$from}' AND '{$to}' ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getByPartnerIdAllStatus($id, $from, $to, $type)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    // $field = 'paid_date';
    // if ($type == 1) {
    //   $field = 'jam';
    // }

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);
    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
    WHERE transaksi.status IN(0,1,2,3,4,5) AND id_partner='{$id}' AND DATE(transaksi.jam) BETWEEN '{$from}' AND '{$to}'";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions`
          WHERE AND id_partner='{$id}' AND `$transactions`.deleted_at IS NULL
          AND DATE($field) BETWEEN '{$from}' AND '{$to}' ";
      }
    }
    $query .= "ORDER BY transaksi.jam DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();

        $i += 1;
      }
      return $res;
    }
  }
  
    public function getByPartnerIdAllStatusWithHour($id, $from, $to, $type)
    {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $field = 'paid_date';
    if ($type == 1) {
      $field = 'jam';
    }
    
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);
    
    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "
        SELECT transaksi.*, SUM(m.hpp * detail_transaksi.qty) as cogs, SUM(detail_transaksi.harga_satuan * detail_transaksi.qty) AS all_total, payment_method.nama as paymentName, employees.nama as collected_by , CASE WHEN transaksi.surcharge_id =0 then '' ELSE (SELECT surcharges.name FROM surcharges WHERE surcharges.id=transaksi.surcharge_id) END AS surcharge_name, c.is_consignment,   SUM(CASE WHEN detail_transaksi.status = '4' THEN detail_transaksi.qty * detail_transaksi.harga_satuan ELSE 0 END) as partial_refund, e.nama as served_by FROM transaksi LEFT JOIN detail_transaksi ON detail_transaksi.id_transaksi = transaksi.id LEFT JOIN menu m ON m.id = detail_transaksi.id_menu LEFT JOIN categories c ON c.id = m.id_category LEFT JOIN payment_method ON payment_method.id = transaksi.tipe_bayar LEFT JOIN shift ON shift.id = transaksi.shift_id LEFT JOIN employees ON employees.id = shift.employee_id LEFT JOIN employees e ON detail_transaksi.server_id = e.id WHERE transaksi.status IN(0, 1, 2, 3, 4, 5) AND transaksi.id_partner = '{$id}' AND transaksi.deleted_at IS NULL AND transaksi." . $field .  " BETWEEN '{$from}' AND '{$to}' GROUP BY detail_transaksi.id_transaksi UNION ALL SELECT transaksi.*, SUM(m.hpp * detail_transaksi.qty) as cogs, SUM(detail_transaksi.harga_satuan * detail_transaksi.qty) AS all_total, payment_method.nama as paymentName, employees.nama as collected_by , CASE WHEN transaksi.surcharge_id =0 then '' ELSE (SELECT surcharges.name FROM surcharges WHERE surcharges.id=transaksi.surcharge_id) END AS surcharge_name, c.is_consignment, SUM(CASE WHEN detail_transaksi.status = '4' THEN detail_transaksi.qty * detail_transaksi.harga_satuan ELSE 0 END) as partial_refund, e.nama as served_by FROM transaksi LEFT JOIN detail_transaksi ON detail_transaksi.id_transaksi = transaksi.id LEFT JOIN menu m ON m.id = detail_transaksi.id_menu LEFT JOIN categories c ON c.id = m.id_category LEFT JOIN payment_method ON payment_method.id = transaksi.tipe_bayar LEFT JOIN shift ON shift.id = transaksi.shift_id LEFT JOIN employees ON employees.id = shift.employee_id LEFT JOIN employees e ON detail_transaksi.server_id = e.id WHERE transaksi.status IN(3, 4) AND transaksi." . $field . " IS NULL AND transaksi.id_partner = '{$id}' AND transaksi.jam BETWEEN '{$from}' AND '{$to}' AND transaksi.deleted_at IS NULL GROUP BY detail_transaksi.id_transaksi

    ";
    
    // SUM(m.hpp * detail_transaksi.qty) as cogs, SUM((detail_transaksi.harga_satuan * detail_transaksi.qty) - (m.hpp * detail_transaksi.qty)) as gross_profit, 
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions`
          WHERE AND id_partner='{$id}' AND `$transactions`.deleted_at IS NULL
          AND $field BETWEEN '{$from}' AND '{$to}' ";
      }
    }
    $query .= "ORDER BY " . $field . " DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();

      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $res[$i]['all_total'] = $val['all_total'] ?? $val['total'];
        $res[$i]['paymentName'] = $val['paymentName'];
        $res[$i]['collected_by'] = $val['collected_by'];
        $res[$i]['surcharge_name'] = $val['surcharge_name'];
        $res[$i]['is_consignment'] = $val['is_consignment'];
        $res[$i]['program_discount'] = $val['program_discount'];
        $res[$i]['rounding'] = $val['rounding'];
        $res[$i]['cogs'] = $val['cogs'];
        $res[$i]['consignmentService'] = 0;
        $res[$i]['consignmentTax'] = 0;
        $res[$i]['refund'] = 0;
        $res[$i]['consignment'] = 0;
        $res[$i]['dp_menu'] = array();
        $res[$i]['refund'] = 0;
        if ($res[$i]['status'] == 4) 
        {
            $res[$i]['refund'] += (int)$val['all_total'];
        } 
        $res[$i]['refund'] += (int)$val['partial_refund'];
        
        $id_transaksi = $val['id'];
        
        if($res[$i]['is_consignment'] == 1 || $res[$i]['is_consignment'] == "1" ){
            $res[$i]['consignment'] = (int)$val['all_total'];
        }
        
        $res[$i]['query_result'] = $query_result;

        array_push($res[$i]['dp_menu'], ["amount"=>(int)$val['dp_total']]);
                
        $i += 1;
        
        }
            

      }
      return $res;
    
    }
    
    // public function getByPartnerIdAllStatusWithHour($id, $from, $to, $type)
    // {
    // // the password should'nt  stocked clair hash it with sha1 for more security
    // // $field = 'paid_date';
    // // if ($type == 1) {
    // //   $field = 'jam';
    // // }
    
    // $queryTrans = "SELECT table_name FROM information_schema.tables
    // WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    // $q = $this->_db->query($queryTrans);
    
    // $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    // $res = array();
    // $query = "SELECT transaksi.*, SUM(detail_transaksi.harga_satuan * detail_transaksi.qty) AS all_total, detail_transaksi.is_consignment  FROM transaksi LEFT join detail_transaksi ON detail_transaksi.id_transaksi = transaksi.id  WHERE transaksi.status IN(0, 1, 2, 3, 4, 5) AND transaksi.id_partner = '{$id}' AND transaksi.paid_date BETWEEN '{$from}' AND '{$to}' GROUP BY detail_transaksi.id_transaksi ";
    // $i = 0;
    // if (!is_null($donnees1) || $donnees1 != false) {
    //   foreach ($donnees1 as $val) {
    //     $table_name = explode("_", $val['table_name']);
    //     $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
    //     $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
    //     $query .= "UNION ALL ";
    //     $query .= "SELECT * FROM `$transactions`
    //       WHERE AND id_partner='{$id}' AND `$transactions`.deleted_at IS NULL
    //       AND $field BETWEEN '{$from}' AND '{$to}' ";
    //   }
    // }
    // $query .= "ORDER BY transaksi.paid_date DESC  ";
    // $q = $this->_db->query($query);
    // $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    // if (is_null($donnees) || $donnees == false) {
    //   return false;
    // } else {
    //   $res = array();
    //   $i = 0;
      
    //   foreach ($donnees as $val) {
    //     $order = new Transaction($val);
    //     $res[$i] = $order->getDetails();
    //     $res[$i]['all_total'] = $val['all_total'];
    //     $res[$i]['is_consignment'] = $val['is_consignment'];
    //     $res[$i]['rounding'] = $val['rounding'];
    //     $res[$i]['consignmentService'] = 0;
    //     $res[$i]['consignmentTax'] = 0;
    
    //     $id_transaksi = $val['id'];
    //         $queryConsignment = "SELECT t.id, SUM(dt.harga_satuan*dt.qty) as total_consignment, t.tax, t.service FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE t.paid_date BETWEEN '$from' AND '$to' AND c.is_consignment = 1 GROUP BY t.id";
            
    //         $q = $this->_db->query($queryConsignment);
    //         $consignmentQ = $q->fetchAll(PDO::FETCH_ASSOC);
        
    //         foreach($consignmentQ as $item){
    //             if($item["id"] == $res[$i]["id"]){
    //             $res[$i]["consignmentService"] = (int)$item['total_consignment'] * (int)$item['service'] / 100;
    //             $res[$i]["consignmentTax"] = ((int)$item['total_consignment'] + ((int)$item['total_consignment'] * ((int)$item['service'] / 100) )) * (int)$item['tax'] / 100;
    //             }
    //         }
        
    //         $query_detail_trx = "SELECT is_consignment, status, harga_satuan, qty FROM detail_transaksi WHERE id_transaksi = '{$id_transaksi}'";
            
    //         $q = $this->_db->query($query_detail_trx);
    //         $query_result = $q->fetchAll(PDO::FETCH_ASSOC);
            
    //         $res[$i]['refund'] = 0;
    //         $res[$i]['consignment'] = 0;
    //         $res[$i]['dp_menu'] = 0;
            
    //         $res[$i]['query_result'] = $query_result;
    //         foreach($query_result as $item){
    //             if($item['status'] == 4){
    //                 $res[$i]['refund'] += (int)$item['harga_satuan']*(int)$item['qty'];
    //             } 
    //             else if ($res[$i]['status'] == 4) 
    //             {
    //                 $res[$i]['refund'] += (int)$item['harga_satuan']*(int)$item['qty'];
    //             } 
    //             else {
    //                 $res[$i]['refund'] += 0;
    //             }
                
    //             if($item['is_consignment'] == 1){
    //                 $res[$i]['consignment'] += (int)$item['harga_satuan']*(int)$item['qty'];
    //             } else {
    //                 $res[$i]['consignment'] += 0;
    //             }
                
    //             $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$id_transaksi' AND deleted_at IS NULL";
    //             $q = $this->_db->query($queryDP);
    //             $sqlDP = $q->fetchAll(PDO::FETCH_ASSOC);
    
    //             $res[$i]['dp_menu'] = $sqlDP;
    //         }
            
    //     $i += 1;
    //   }
    //   return $res;
    // }
    // }

  public function getByMasterId($idMaster, $from, $to, $type)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $field = 't.paid_date';
    if ($type == 1) {
      $field = 't.jam';
    }

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT t.* FROM transaksi t JOIN partner p ON p.id = t.id_partner
    WHERE p.id_master='$idMaster' AND t.deleted_at IS NULL
    AND DATE($field) BETWEEN '$from' AND '$to' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions` t JOIN partner p ON p.id = $table_name.id_partner WHERE p.id_master='$idMaster' AND `t.deleted_at IS NULL AND DATE($field) BETWEEN '$from' AND '$to' ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getByMasterIdWithHour($idMaster, $from, $to, $type)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $field = 't.paid_date';
    if ($type == 1) {
      $field = 't.jam';
    }

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT t.* FROM transaksi t JOIN partner p ON p.id = t.id_partner
    WHERE p.id_master='$idMaster' AND t.deleted_at IS NULL
    AND $field BETWEEN '$from' AND '$to' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions` t JOIN partner p ON p.id = $table_name.id_partner WHERE p.id_master='$idMaster' AND t.deleted_at IS NULL AND $field BETWEEN '$from' AND '$to' ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getByPhoneAndMasterId($phone, $masterId)
  {
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT transaksi.* FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id WHERE partner.id_master='$masterId' AND transaksi.phone='$phone' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT `$transactions`.* FROM `$transactions` JOIN partner ON `$transactions`.id_partner=partner.id WHERE partner.id_master='$masterId' AND `$transactions`.phone='$phone' ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $order = new Transaction($val);
        $res[$i] = $order->getDetails();
        $i += 1;
      }
      return $res;
    }
  }

  public function getOrderLength($id)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM transaksi
    WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=5
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=7
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=8
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=9
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=1
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=2
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=3
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=4
    OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=6 ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $transactions = "transactions_" . $table_name[1] . "_" . $table_name[2];
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$transactions`
          WHERE id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=5
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=7
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=8
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND tipe_bayar=9
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=1
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=2
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=3
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=4
          OR id_partner='{$id}' AND `$transactions`.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=6 ";
      }
    }
    $query .= "ORDER BY $field DESC  ";
    $q = $this->_db->query($query);
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return 0;
    } else {
      $i = 0;
      foreach ($donnees as $val) {
        $i += 1;
      }
      return $i;
    }
  }

  public function getAutoId()
  {
    $max = 0;
    // get AI from DB schema
    $q = $this->_db->query("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'shops'
                                                                      AND   TABLE_NAME = 'users' ");

    while ($donnees = $q->fetch(PDO::FETCH_ASSOC)) {
      if ($donnees['AUTO_INCREMENT'] > $max) {
        $max = $donnees['AUTO_INCREMENT'];
      }
    }
    return $max;
  }

  public function setDb(PDO $db)
  {
    $this->_db = $db;
  }
}
