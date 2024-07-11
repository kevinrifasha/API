<?php

require_once("partnerTable.php");

class PartnerTableManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function delete($id)
  {
    $q = $this->_db->prepare("UPDATE `meja` SET deleted_at=NOW() WHERE `id`='{$id}' ");
    return $q->execute();
  }

  public function update(partnertable $tbl)
  {
    $id = $tbl->getId();
    $idMeja = $tbl->getIdmeja();
    $idPartner = $tbl->getIdpartner();
    $isQueue = $tbl->getIs_queue();

    $q = $this->_db->prepare("UPDATE `meja` SET `idmeja`='{$idMeja}',`idpartner`='{$idPartner}',`is_queue`='{$isQueue}' WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function add(partnertable $tbl)
  {
    $idMeja = $tbl->getIdmeja();
    $idPartner = $tbl->getIdpartner();
    $isQueue = $tbl->getIs_queue();

    $q = $this->_db->prepare("INSERT INTO `meja` SET `idmeja`='{$idMeja}',`idpartner`='{$idPartner}',`is_queue`='{$isQueue}'");
    return $q->execute();
  }

  public function getByPartnerId($idPartner)
  {
    $q = $this->_db->query("SELECT * FROM `meja` WHERE idpartner='{$idPartner}' AND deleted_at IS NULL");
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $data = new PartnerTable($donnee);
      array_push($res, $data);
    }
    return $res;
  }

  public function getByPartnerIdAndMejaId($idPartner, $idMeja)
  {
    $q = $this->_db->query("SELECT * FROM `meja` WHERE idpartner='{$idPartner}' AND idmeja='{$idMeja}'");
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $donnee = new PartnerTable($donnees);
    return $donnee;
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
