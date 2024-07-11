<?php

require_once("rawMaterial.php");

class RawMaterialManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `raw_material` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $rawMaterial = new RawMaterial($donnees);
    return $rawMaterial;
  }

  public function getByPartnerId($id)
  {
    $q = $this->_db->query("SELECT * FROM `raw_material` WHERE id_partner='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $rawMaterial = new RawMaterial($donnee);
      array_push($res, $rawMaterial);
    }
    return $res;
  }

  public function getByMasterId($id)
  {
    $q = $this->_db->query("SELECT * FROM `raw_material` WHERE id_master='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $rawMaterial = new RawMaterial($donnee);
      array_push($res, $rawMaterial);
    }
    return $res;
  }

  public function add(rawmaterial $rwm)
  {
    $q = $this->_db->prepare('INSERT INTO raw_material SET id_master = :id_master, id_partner = :id_partner, name = :name, reminder_allert = :reminder_allert, id_metric = :id_metric, unit_price=:unit_price, id_metric_price = :id_metric_price, category_id = :category_id');
    $q->bindValue(':id_master', $rwm->getId_master());
    $q->bindValue(':id_partner', $rwm->getId_partner());
    $q->bindValue(':name', $rwm->getName());
    $q->bindValue(':reminder_allert', $rwm->getReminder_allert());
    $q->bindValue(':id_metric', $rwm->getId_metric());
    $q->bindValue(':unit_price', $rwm->getUnit_price());
    $q->bindValue(':id_metric_price', $rwm->getId_metric_price());
    $q->bindValue(':category_id', $rwm->getCategory_id());
    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(rawmaterial $rwm)
  {
    $id = $rwm->getId();
    $id_master = $rwm->getId_master();
    $id_partner = $rwm->getId_partner();
    $name = $rwm->getName();
    $reminder_allert = $rwm->getReminder_allert();
    $id_metric = $rwm->getId_metric();
    $unit_price = $rwm->getUnit_price();
    $id_metric_price = $rwm->getId_metric_price();
    $category_id = $rwm->getCategory_id();
    $yieldRM = $rwm->getYieldRM();
    $q = $this->_db->prepare("UPDATE raw_material SET id_master = '{$id_master}', id_partner = '{$id_partner}', name = '{$name}', reminder_allert = '{$reminder_allert}', id_metric = '{$id_metric}', unit_price = '{$unit_price}', id_metric_price='{$id_metric_price}', category_id='{$category_id}', yield='{$yieldRM}' WHERE id = '{$id}'");
    $a = $q->execute();
    return $a;
  }

  public function delete(rawmaterial $rwm)
  {
    $id = $rwm->getId();

    $q = $this->_db->prepare("UPDATE raw_material SET deleted_at = NOW() WHERE id = '{$id}'");
    $a = $q->execute();
    return $a;
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
