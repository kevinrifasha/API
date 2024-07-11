<?php

require_once("user.php");

class UserManager
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

  public function getUser($phone)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $q = $this->_db->query("SELECT * FROM users WHERE phone='{$phone}' ");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      $user = new user($donnees);
    return $user;
  }

  public function getOrder($id, $start, $load)
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $q = $this->_db->query(
      "SELECT * FROM transaksi
                            WHERE id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=5
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=7
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=8
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND tipe_bayar=9
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=1
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=2
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=3
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=4
                            OR id_partner='{$id}' AND transaksi.deleted_at IS NULL AND status<2 AND status>0 AND tipe_bayar=6
                            ORDER BY jam DESC LIMIT $start,$load"
    );

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      $res = array();
    $i = 0;
    foreach ($donnees as $val) {
      $order = new Transaction($val);
      $res[$i] = $order->getDetails();
      $i += 1;
    }
    return $res;
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
