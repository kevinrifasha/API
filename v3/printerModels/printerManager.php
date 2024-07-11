<?php

require_once("printer.php");

class PrinterManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(printer $printer)
  {
    $ip = $printer->getIp();
    $partnerId = $printer->getPartnerId();
    $name = $printer->getName();
    $isReceipt = $printer->getIsReceipt();
    $isFullChecker = $printer->getIsFullChecker();
    $isCategoryChecker = $printer->getIsCategoryChecker();

    $q = $this->_db->prepare("INSERT INTO printer SET ip='{$ip}', partnerId='{$partnerId}', name='{$name}', isReceipt='{$isReceipt}',  isFullChecker='{$isFullChecker}', isCategoryChecker='{$isCategoryChecker}'");
    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(printer $printer)
  {
    $id = $printer->getId();
    $ip = $printer->getIp();
    $partnerId = $printer->getPartnerId();
    $name = $printer->getName();
    $isReceipt = $printer->getIsReceipt();
    $isFullChecker = $printer->getIsFullChecker();
    $isCategoryChecker = $printer->getIsCategoryChecker();

    $q = $this->_db->prepare("UPDATE printer SET ip='{$ip}', partnerId='{$partnerId}', name='{$name}', isReceipt='{$isReceipt}',  isFullChecker='{$isFullChecker}', isCategoryChecker='{$isCategoryChecker}' WHERE id='{$id}'");
    $a = $q->execute();
    return $a;
  }

  public function delete(printer $printer)
  {
    $id = $printer->getId();

    $q = $this->_db->prepare("DELETE FROM printer WHERE id='{$id}'");
    $a = $q->execute();
    return $a;
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `printer` WHERE id='{$id}'");
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $printer = new Printer($donnees);
    return $printer;
  }

  public function getByPartnerId($id)
  {
    $q = $this->_db->query("SELECT * FROM `printer` WHERE partnerId='{$id}'");
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $printer = new Printer($donnee);
      array_push($res, $printer);
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
