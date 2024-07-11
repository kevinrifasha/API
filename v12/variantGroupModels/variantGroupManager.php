<?php

require_once("variantGroup.php");

class VariantGroupManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `variant_group` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menu = new VariantGroup($donnees);
    return $menu;
  }

  public function getByMasterId($id)
  {
    $q = $this->_db->query("SELECT * FROM `variant_group` WHERE id_master='{$id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $data = new VariantGroup($donnee);
      array_push($res, $data);
    }
    return $res;
  }

  public function delete($id)
  {
    $q = $this->_db->prepare("DELETE FROM `variant_group` WHERE id='{$id}'");
    $a = $q->execute();
    return $a;
  }

  public function add(variantgroup $variant)
  {
    $id_menu = $variant->getId_menu();
    $id_master = $variant->getId_master();
    $name = $variant->getName();
    $type = $variant->getType();
    $partner_id = $variant->getPartner_id();

    $q = $this->_db->prepare("INSERT INTO `variant_group` SET `id_menu`='{$id_menu}',`id_master`='{$id_master}',`partner_id`='{$partner_id}',`name`='{$name}',`type`='{$type}'");
    
    $a = $q->execute();
    
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(variantgroup $variant)
  {
    $id = $variant->getId();
    $id_menu = $variant->getId_menu();
    $id_master = $variant->getId_master();
    $name = $variant->getName();
    $type = $variant->getType();

    $q = $this->_db->prepare("UPDATE `variant_group` SET `id_menu`='{$id_menu}',`id_master`='{$id_master}',`name`='{$name}',`type`='{$type}' WHERE id='{$id}'");
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
