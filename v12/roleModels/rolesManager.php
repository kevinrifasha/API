<?php

require_once("roles.php");

class RolesManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `roles` WHERE id='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $roles = new Role($donnees);
    return $roles;
  }

  public function getAllRoles()
  {
    // the password should'nt  stocked clair hash it with sha1 for more security

    $q = $this->_db->query("SELECT * FROM `roles` WHERE deleted=!=1 ");


    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      $res = array();
    foreach ($donnees as $donnee) {
      $data = new Role($donnee);
      array_push($res, $data);
    }
    return $res;
  }

  public function setDb(PDO $db)
  {
    $this->_db = $db;
  }
}
