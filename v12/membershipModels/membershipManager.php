<?php

require_once("membership.php");

class MembershipManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getMembership($user_phone, $master_id)
  {
    $q = $this->_db->query("SELECT * FROM memberships WHERE user_phone='{$user_phone}' AND master_id='{$master_id}'");
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $membership = new Membership($donnees);
    return $membership;
  }

  public function getByMasterId($master_id)
  {
    $q = $this->_db->query("SELECT * FROM memberships WHERE master_id='{$master_id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $value) {
      $membership = new Membership($value);
      array_push($res, $membership);
    }
    return $res;
  }

  public function add(membership $membership)
  {
    $user_phone = $membership->getUser_phone();
    $master_id = $membership->getMaster_id();
    $point = $membership->getPoint();

    $q = $this->_db->prepare("INSERT INTO `memberships`(`user_phone`, `master_id`, `point`) VALUES ('{$user_phone}', '{$master_id}', '{$point}')");

    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(membership $membership)
  {
    $id = $membership->getId();
    $point = $membership->getPoint();

    $q = $this->_db->prepare("UPDATE `memberships` SET `point`='{$point}' WHERE `id`='{$id}'");

    $a = $q->execute();
    return $a;
  }

  public function delete(membership $membership)
  {
    $id = $membership->getId();
    $q = $this->_db->prepare("UPDATE `memberships` SET deleted_at=NOW() WHERE `id`='{$id}'");
    return $q->execute();
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
