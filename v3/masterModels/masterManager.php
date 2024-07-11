<?php

require_once("master.php");

class MasterManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(master $master)
  {
    $email = $master->getEmail();
    $password = $master->getPassword();
    $name = $master->getName();
    $phone = $master->getPhone();
    $referrer = $master->getReferrer();
    $q = $this->_db->prepare("INSERT INTO master SET email = '{$email}', password = '{$password}', name = '{$name}', phone = '{$phone}', `referrer`='{$referrer}'");
    // var_dump($q);
    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(master $mstr)
  {
    $q = $this->_db->prepare("UPDATE `master` SET `password`='{$mstr->getPassword()}',`email`='{$mstr->getEmail()}',`name`='{$mstr->getName()}',`phone`='{$mstr->getPhone()}',`office_number`='{$mstr->getOffice_number()}',`address`='{$mstr->getAddress()}',`harga_point`='{$mstr->getHarga_point()}',`point_pay`='{$mstr->getPoint_pay()}',`transaction_point_max`='{$mstr->getTransaction_point_max()}',`img`='{$mstr->getImg()}',`is_foodcourt`='{$mstr->getIs_foodcourt()}',`no_rekening`='{$mstr->getNo_rekening()}',`status`='{$mstr->getStatus()}',`created_at`='{$mstr->getCreated_at()}',`referrer`='{$mstr->getReferrer()}',`deposit_balance`='{$mstr->getDeposit_balance()}' WHERE `id`='{$mstr->getId()}'");
    return $q->execute();
  }

  public function getMasterByEmail($email)
  {
    $q = $this->_db->query("SELECT id FROM master WHERE email='{$email}' ");
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $newPartner = new Master($donnees);
    return $newPartner;
  }

  public function login($email, $password)
  {
    // dont_take_it allow us to check if email is already exist or not we dont care about password
    if ($password == 'dont_take_it') {
    } else {
      $q = $this->_db->query("SELECT * FROM master WHERE email='{$email}' AND password='{$password}'");
    }

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $newPartner = new Master($donnees);
    return $newPartner;
  }

  public function getMasterById($id)
  {
    $q = $this->_db->query("SELECT * FROM master WHERE id='{$id}'");

    $result = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($result) || $result == false)
      return false;
    else
      $newPartner = new Master($result);
    return $newPartner;
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
