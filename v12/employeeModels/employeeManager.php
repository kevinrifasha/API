<?php

require_once("employee.php");

class EmployeeManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function delete($emply)
  {
    $id = $emply->getId();
    $q = $this->_db->prepare("UPDATE `employees` SET `deleted_at`=NOW() WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function update(employee $emply)
  {
    $id = $emply->getId();
    $nik = $emply->getNik();
    $nama = $emply->getNama();
    $gender = $emply->getGender();
    $phone = $emply->getPhone();
    $email = $emply->getEmail();
    $pin = $emply->getPin();
    $id_master = $emply->getId_master();
    $id_partner = $emply->getId_partner();
    $role_id = $emply->getRole_id();
    $pattern_id = $emply->getPattern_id();
    $show_as_server = $emply->getShow_as_server();
    $organization = $emply->getOrganization();
    $q = $this->_db->prepare("UPDATE `employees` SET `nik`='{$nik}',`nama`='{$nama}',`gender`='{$gender}',`phone`='{$phone}',`email`='{$email}',`pin`='{$pin}',`id_master`='{$id_master}',`id_partner`='{$id_partner}',`role_id`='{$role_id}',`pattern_id`='{$pattern_id}',`show_as_server`='{$show_as_server}', `organization`='{$organization}' WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function add(employee $emply)
  {
    $nik = $emply->getNik();
    $nama = $emply->getNama();
    $gender = $emply->getGender();
    $phone = $emply->getPhone();
    $email = $emply->getEmail();
    $pin = $emply->getPin();
    $id_master = $emply->getId_master();
    $id_partner = $emply->getId_partner();
    $role_id = $emply->getRole_id();
    $show_as_server = $emply->getShow_as_server();
    $organization = $emply->getOrganization();
    if (!empty($emply->getPattern_id())) {
      $pattern_id = $emply->getPattern_id();
    } else {
      $pattern_id = "0";
    }
    $q = $this->_db->prepare("INSERT INTO `employees` SET `nik`='{$nik}',`nama`='{$nama}',`gender`='{$gender}',`phone`='{$phone}',`email`='{$email}',`pin`='{$pin}',`id_master`='{$id_master}',`id_partner`='{$id_partner}',`role_id`='{$role_id}',`pattern_id`='{$pattern_id}',`show_as_server`='{$show_as_server}', `organization`='{$organization}'");
    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function getByPartnerId($idPartner)
  {
    $q = $this->_db->query("SELECT employees.* FROM `employees` JOIN `partner` ON employees.id_partner=partner.id WHERE (partner.id='{$idPartner}' OR partner.fc_parent_id='{$idPartner}') AND employees.deleted_at IS NULL AND partner.deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $value) {
      $employee = new Employee($value);
      array_push($res, $employee);
    }
    return $res;
  }

  public function getByMasterId($idMaster)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE id_master='{$idMaster}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $value) {
      $employee = new Employee($value);
      array_push($res, $employee);
    }
    return $res;
  }

  public function getByNik($nik)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE nik='{$nik}' AND deleted_at IS NULL AND organization='Natta'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $employee = new Employee($donnees);
    return $employee;
  }

  public function getByPhone($phone)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE phone='{$phone}' AND deleted_at IS NULL AND organization='Natta'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $employee = new Employee($donnees);
    return $employee;
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE id='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $employee = new Employee($donnees);
    return $employee;
  }

  public function getByEmail($email)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE email='{$email}' AND deleted_at IS NULL AND organization='Natta'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $employee = new Employee($donnees);
    return $employee;
  }

  public function loginPhone($phone, $pin)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE phone='{$phone}' AND pin='{$pin}' AND deleted_at IS NULL AND organization='Natta'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $employee = new Employee($donnees);
    return $employee;
  }

  public function loginEmail($email, $pin)
  {
    $q = $this->_db->query("SELECT * FROM `employees` WHERE email='{$email}' AND pin='{$pin}' AND deleted_at IS NULL AND organization='Natta'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $employee = new Employee($donnees);
    return $employee;
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
