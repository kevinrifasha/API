<?php

require_once("rawMaterialStock.php");

class RawMaterialStockManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getByRawId($id)
  {
    $today = date("Y-m-d");
    $q = $this->_db->query("SELECT * FROM `raw_material_stock` WHERE id_raw_material='{$id}' AND exp_date>='$today' ORDER BY exp_date ASC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    foreach ($donnees as $value) {
      $res[$i] = new RawMaterialStock($value);
      $i += 1;
    }
    return $res;
  }

  public function getByRawIdAll($id)
  {
    $today = date("Y-m-d");
    $q = $this->_db->query("SELECT * FROM `raw_material_stock` WHERE id_raw_material='{$id}' ORDER BY exp_date ASC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    foreach ($donnees as $value) {
      $res[$i] = new RawMaterialStock($value);
      $i += 1;
    }
    return $res;
  }

  public function insertInit(rawmaterialstock $rms)
  {
    $q = $this->_db->prepare('INSERT INTO raw_material_stock SET id_raw_material = :id_raw_material, stock = :stock, id_metric = :id_metric, exp_date = :exp_date');
    $q->bindValue(':id_raw_material', $rms->getId_raw_material());
    $q->bindValue(':stock', $rms->getStock());
    $q->bindValue(':id_metric', $rms->getId_metric());
    $q->bindValue(':exp_date', $rms->getExp_date());
    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function deleteByRawId($id)
  {
    $q = $this->_db->prepare("DELETE FROM raw_material_stock WHERE id_raw_material = '{$id}' ");
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
