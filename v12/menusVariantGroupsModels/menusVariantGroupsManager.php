<?php

require_once("menusVariantGroups.php");

class MenusVariantGroupsManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `variant_group` WHERE id='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menu = new MenusVariantGroups($donnees);
    return $menu;
  }

  public function getByMenuId($id)
  {
    $q = $this->_db->query("SELECT * FROM `menus_variantgroups` WHERE menu_id='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $value) {
      $menu = new MenusVariantGroups($value);
      array_push($res, $menu);
    }
    return $res;
  }

  public function add(menusvariantgroups $mvg)
  {
    $menu_id = $mvg->getMenu_id();
    $variant_group_id = $mvg->getVariant_group_id();
    $created_at = $mvg->getCreated_at();
    $updated_at = $mvg->getUpdated_at();
    $deleted_at = $mvg->getDeleted_at();

    $q = $this->_db->prepare("INSERT INTO `menus_variantgroups` SET `menu_id`='{$menu_id}',`variant_group_id`='{$variant_group_id}',`created_at`='{$created_at}',`updated_at`='{$updated_at}'");
    return $q->execute();
  }

  public function update(menusvariantgroups $mvg)
  {
    $id = $mvg->getId();
    $menu_id = $mvg->getMenu_id();
    $variant_group_id = $mvg->getVariant_group_id();
    $created_at = $mvg->getCreated_at();
    $updated_at = $mvg->getUpdated_at();
    $deleted_at = $mvg->getDeleted_at();

    $q = $this->_db->prepare("UPDATE `menus_variantgroups` SET `variant_group_id`='{$variant_group_id}',`created_at`='{$created_at}',`updated_at`='{$updated_at}', `deleted_at`='{$deleted_at}' WHERE `id`='{$id}'");
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
