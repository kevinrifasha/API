<?php

require_once("specialMember.php");

class SpecialMemberManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function addPartner(user $usr)
  {
    $q = $this->_db->prepare('INSERT INTO partner SET email = :email, password = :password, organization= :organization');
    $q->bindValue(':email', $usr->email());
    $q->bindValue(':password', $usr->password());
    $q->bindValue(':organization', 'Natta');
    $q->execute();
  }

  public function getSpecialMember($phone, $id_master)
  {
    $q = $this->_db->query("SELECT * FROM special_member WHERE phone='{$phone}' AND id_master='{$id_master}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $newPartner = new SpecialMember($donnees);
    return $newPartner;
  }

  public function getPartnerDetails($id)
  {
    $q = $this->_db->query("SELECT * FROM partner WHERE id='{$id}'");

    $result = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($result) || $result == false)
      return false;
    else
      $newPartner = new Partner($result);
    return $newPartner->getDetails();
  }

  public function getAllPartner()
  {

    $q = $this->_db->query("SELECT * FROM partner WHERE organization='Natta'");


    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      return new Partner($donnees);
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
