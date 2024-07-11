<?php

require_once("transactionDelivery.php");

class TransactionDeliveryManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function addPartner(user $usr)
  {
    $q = $this->_db->prepare('INSERT INTO partner SET email = :email, password = :password, organization = :organization');
    $q->bindValue(':email', $usr->email());
    $q->bindValue(':password', $usr->password());
    $q->bindValue(':organization', 'Natta');
    $q->execute();
  }

  public function getDelivery($transaksi_id)
  {
    $q = $this->_db->query("SELECT * FROM delivery WHERE transaksi_id='{$transaksi_id}'");
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $deliv = new TransactionDelivery($donnees);
    return $deliv->getDetails();
  }

  public function getAllPartner()
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $q = $this->_db->query("SELECT * FROM partner AND organization = 'Natta'");


    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      return new Partner($donnees);
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
