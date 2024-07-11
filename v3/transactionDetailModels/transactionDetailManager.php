<?php

require_once("transactionDetail.php");
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();


class TransactionDetailManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function update(transactiondetail $transactiondetails)
  {
    $id = $transactiondetails->getId();
    $id_transaksi = $transactiondetails->getId_transaksi();
    $id_menu = $transactiondetails->getId_menu();
    $harga_satuan = $transactiondetails->getHarga_satuan();
    $qty = $transactiondetails->getQty();
    $notes = $transactiondetails->getNotes();
    $harga = $transactiondetails->getHarga();
    $variant = $transactiondetails->getVariant();
    $status = $transactiondetails->getStatus();


    $q = $this->_db->prepare("UPDATE `detail_transaksi` SET `id_transaksi`='{$id_transaksi}',`id_menu`='{$id_menu}',`harga_satuan`='{$harga_satuan}',`qty`='{$qty}',`notes`='{$notes}',`harga`='{$harga_satuan}',`variant`='{$variant}',`status`='{$status}' WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function getDetail($id)
  {

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM detail_transaksi WHERE id_transaksi='{$id}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$detail_transactions` WHERE id_transaksi='{$id}' ";
      }
    }
    $q = $this->_db->query($query);

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $detail = new TransactionDetail($val);
        $res[$i] = $detail;
        $i += 1;
      }
    }
    return $res;
  }
  public function getDetailTenant($id, $pid)
  {

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT detail_transaksi.* FROM detail_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='{$id}' AND menu.id_partner='{$pid}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT `$detail_transactions`.* FROM `$detail_transactions` JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE `$detail_transactions`.id_transaksi='{$id}' AND menu.id_partner='{$pid}'  ";
      }
    }
    $q = $this->_db->query($query);

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $detail = new TransactionDetail($val);
        $res[$i] = $detail;
        $i += 1;
      }
    }
    return $res;
  }

  public function getDetailById($id)
  {
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT * FROM detail_transaksi WHERE id='{$id}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT * FROM `$detail_transactions` WHERE id='{$id}' ";
      }
    }
    $q = $this->_db->query($query);
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $detail = new TransactionDetail($donnees);
      return $detail;
    }
  }

  public function getDetailSortByIdCategory($id)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '" . $_ENV['DB_NAME'] . "' AND table_name LIKE 'transactions_%'";
    $q = $this->_db->query($queryTrans);

    $donnees1 = $q->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    $query = "SELECT detail_transaksi.* FROM detail_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN ip_categories ON ip_categories.id_category=menu.id_category WHERE id_transaksi='{$id}' ";
    $i = 0;
    if (!is_null($donnees1) || $donnees1 != false) {
      foreach ($donnees1 as $val) {
        $table_name = explode("_", $val['table_name']);
        $detail_transactions = "detail_transactions_" . $table_name[1] . "_" . $table_name[2];
        $query .= "UNION ALL ";
        $query .= "SELECT `$detail_transactions`.* FROM `$detail_transactions` JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN ip_categories ON ip_categories.id_category=menu.id_category WHERE id_transaksi='{$id}' ";
      }
    }
    $query .= " ORDER BY ip ASC ";
    $q = $this->_db->query($query);

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else {
      $res = array();
      $i = 0;
      foreach ($donnees as $val) {
        $detail = new TransactionDetail($val);
        $res[$i] = $detail;
        $i += 1;
      }
      return $res;
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
