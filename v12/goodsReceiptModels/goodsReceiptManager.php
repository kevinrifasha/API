<?php

require_once("goodsReceipt.php");

class GoodsReceiptManager
{
  private $_db;

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `goods_receipt` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $data = new GoodsReceipt($donnees);
    return $data;
  }

  public function getAll()
  {
    $q = $this->_db->query("SELECT * FROM `goods_receipt`");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $data = new GoodsReceipt($donnee);
      array_push($res, $data);
    }
    return $res;
  }

  public function getByIdPartner($idPartner)
  {
    $q = $this->_db->query("SELECT * FROM `goods_receipt` WHERE `id_partner`='$idPartner' AND `deleted_at` IS NULL");
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $data = new GoodsReceipt($donnee);
      array_push($res, $data);
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
