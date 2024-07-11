<?php

require_once("deviceToken.php");

class DeviceTokenManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function insertPartnerTokens(deviceToken $deviceToken)
  {
    $q = $this->_db->prepare('INSERT INTO device_tokens SET id_partner = :id_partner, tokens = :tokens, created_at = :created_at');
    $q->bindValue(':id_partner', $deviceToken->getId_partner());
    $q->bindValue(':tokens', $deviceToken->getTokens());
    $q->bindValue(':created_at', $deviceToken->getCreated_at());
    return $q->execute();
  }

  public function updatePartnerTokens(deviceToken $deviceToken)
  {
    $id_partner = $deviceToken->getId_partner();
    $tokens = $deviceToken->getTokens();
    $updated_at = $deviceToken->getUpdated_at();
    $id = $deviceToken->getId();
    $q = $this->_db->prepare("UPDATE device_tokens SET id_partner = '{$id_partner}', tokens= '{$tokens}', updated_at= '{$updated_at}' WHERE id= '{$id}'");
    return $q->execute();
  }


  public function getByToken($tokens)
  {
    $q = $this->_db->query("SELECT * FROM device_tokens WHERE tokens='{$tokens}' AND deleted=0");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $deviceToken = new DeviceToken($donnees);
    return $deviceToken->getDetails();
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
