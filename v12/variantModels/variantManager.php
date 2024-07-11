<?php

require_once("variant.php");

class VariantManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `variant` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menu = new Variant($donnees);
    return $menu;
  }

  public function getByVariantGroupId($id)
  {
    $q = $this->_db->query("SELECT * FROM `variant` WHERE id_variant_group='{$id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $value) {
      $variant = new Variant($value);
      array_push($res, $variant);
    }
    return $res;
  }

  public function delete($id)
  {
    $q = $this->_db->prepare("DELETE FROM `variant` WHERE id='{$id}'");
    $a = $q->execute();
    return $a;
  }

  public function deleteByVGID($id)
  {
    $q = $this->_db->prepare("DELETE FROM `variant` WHERE id_variant_group='{$id}'");
    $a = $q->execute();
    return $a;
  }

  public function add(variant $variant)
  {
    $id_variant_group = $variant->getId_variant_group();
    $name = $variant->getName();
    $price = $variant->getPrice();
    $stock = $variant->getStock();
    $is_recipe = $variant->getIs_recipe();

    $q = $this->_db->prepare("INSERT INTO `variant` SET `id_variant_group`='{$id_variant_group}',`name`='{$name}',`price`='{$price}',`stock`='{$stock}',`is_recipe`='{$is_recipe}' ");

    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(variant $variant)
  {
    $id = $variant->getId();
    $id_variant_group = $variant->getId_variant_group();
    $name = $variant->getName();
    $price = $variant->getPrice();
    $stock = $variant->getStock();
    $is_recipe = $variant->getIs_recipe();
    $cogs = $variant->getCogs();

    $q = $this->_db->prepare("UPDATE `variant` SET `id_variant_group`='{$id_variant_group}',`name`='{$name}',`price`='{$price}',`stock`='{$stock}',`is_recipe`='{$is_recipe}',`cogs`='{$cogs}' WHERE id='{$id}'");
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
