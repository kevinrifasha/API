<?php

require_once("ipCategory.php");

class IpCategoryManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(ipcategory $IpCategory)
  {
    $ip = $IpCategory->getIp();
    $category_id = $IpCategory->getId_category();

    $q = $this->_db->prepare("INSERT INTO ip_categories SET ip = '{$ip}', category_id = '{$category_id}'");

    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function getByCategoryId($category_id)
  {
    $q = $this->_db->query("SELECT * FROM `ip_categories` WHERE id_category='{$category_id}'");
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $IpCategory = new IpCategory($donnees);
    return $IpCategory;
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
