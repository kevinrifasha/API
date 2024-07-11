<?php

require_once("menu.php");

class MenuManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function delete($id)
  {
    $q = $this->_db->prepare("UPDATE `menu` SET deleted_at=NOW() WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function update(menu $menu)
  {
    $id = $menu->getId();
    $sku = $menu->getSKU();
    $id_partner = $menu->getId_partner();
    $nama = $menu->getNama();
    $harga = $menu->getHarga();
    $Deskripsi = $menu->getDeskripsi();
    $id_category = $menu->getId_category();
    $img_data = $menu->getImg_data();
    $enabled = $menu->getEnabled();
    $stock = $menu->getStock();
    $hpp = $menu->getHpp();
    $harga_diskon = $menu->getHarga_diskon();
    $is_variant = $menu->getIs_variant();
    $is_recommended = $menu->getIs_recommended();
    $is_recipe = $menu->getIs_recipe();
    $thumbnail = $menu->getThumbnail();
    $is_auto_cogs = $menu->getIs_auto_cogs();
    $show_in_sf = $menu->getShow_in_sf();
    $show_in_waiter = $menu->getShow_in_waiter();

    $q = $this->_db->prepare("UPDATE `menu` SET `sku`='{$sku}', `nama`='{$nama}',`harga`='{$harga}',`Deskripsi`='{$Deskripsi}',`id_category`='{$id_category}',`img_data`='{$img_data}',`enabled`='{$enabled}',`stock`='{$stock}',`hpp`='{$hpp}',`harga_diskon`='{$harga_diskon}',`is_variant`='{$is_variant}',`is_recommended`='{$is_recommended}',`is_recipe`='$is_recipe', `thumbnail`='$thumbnail', `is_auto_cogs`='$is_auto_cogs', `show_in_sf`=$show_in_sf, `show_in_waiter`=$show_in_waiter, updated_at=NOW() WHERE `id`='{$id}' AND deleted_at IS NULL");
    return $q->execute();
  }

  public function add(menu $menu)
  {
    $sku = $menu->getSKU();
    $id_partner = $menu->getId_partner();
    $nama = $menu->getNama();
    $harga = $menu->getHarga();
    $Deskripsi = $menu->getDeskripsi();
    $category = $menu->getCategory();
    $id_category = $menu->getId_category();
    $img_data = $menu->getImg_data();
    $enabled = $menu->getEnabled();
    $stock = $menu->getStock();
    $hpp = $menu->getHpp();
    $harga_diskon = $menu->getHarga_diskon();
    $is_variant = $menu->getIs_variant();
    $is_recommended = $menu->getIs_recommended();
    $is_recipe = $menu->getIs_recipe();
    $thumbnail = $menu->getThumbnail();
    $is_auto_cogs = $menu->getIs_auto_cogs();
    $show_in_sf = $menu->getShow_in_sf();
    $show_in_waiter = $menu->getShow_in_waiter();

    $q = $this->_db->prepare("INSERT INTO `menu` SET `id_partner`='{$id_partner}',`sku`='{$sku}',`nama`='{$nama}',`harga`='{$harga}',`Deskripsi`='{$Deskripsi}',`category`='{$category}',`id_category`='{$id_category}',`img_data`='{$img_data}',`enabled`='{$enabled}',`stock`='{$stock}',`hpp`='{$hpp}',`harga_diskon`='{$harga_diskon}',`is_variant`='{$is_variant}',`is_recommended`='{$is_recommended}',`is_recipe`='$is_recipe', `thumbnail`='$thumbnail', `is_auto_cogs`='$is_auto_cogs',`show_in_sf`=$show_in_sf,`show_in_waiter`=$show_in_waiter ");

    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `menu` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menu = new Menu($donnees);
    return $menu;
  }

  public function getByPartnerId($id)
  {
    $q = $this->_db->query("SELECT * FROM `menu` WHERE id_partner='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }

  public function getByMasterId($idMaster)
  {
    $q = $this->_db->query("SELECT m.* FROM menu m JOIN partner p ON p.id = m.id_partner WHERE p.id_master='$idMaster' AND m.deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }

  public function getByParentId($parentId)
  {
    $q = $this->_db->query("SELECT menu.* FROM `menu` JOIN partner ON partner.id=menu.id_partner WHERE partner.fc_parent_id='{$parentId}' AND menu.deleted_at IS NULL AND partner.deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }

  public function getByPartnerIdSuggest($id)
  {
    $q = $this->_db->query("SELECT * FROM `menu` WHERE id_partner='{$id}' AND deleted_at IS NULL AND is_suggestions!=0 ORDER BY is_suggestions");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }
  public function getByPartnerIdNotSuggest($id)
  {
    $q = $this->_db->query("SELECT * FROM `menu` WHERE id_partner='{$id}' AND deleted_at IS NULL AND is_suggestions=0");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }

  public function getByPartnerIdNoVar($id)
  {
    $q = $this->_db->query("SELECT menu.*  FROM `menu` LEFT JOIN menus_variantgroups ON menus_variantgroups.menu_id=menu.id WHERE menu.id_partner='{$id}' AND menu.deleted_at IS NULL AND menu.deleted_at IS NULL AND menus_variantgroups.id IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }
  public function getByCategoryId($id, $idP)
  {
    $q = $this->_db->query("SELECT * FROM `menu` WHERE id_category='{$id}' AND id_partner='{$idP}' AND deleted_at IS NULL ORDER BY sequence, id DESC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
  }

  public function getByPartnerIdNR($id)
  {
    $q = $this->_db->query("SELECT * FROM `menu` WHERE id_partner='{$id}' AND is_recipe='0'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $menus = array();
    foreach ($donnees as $donnee) {
      $menu = new Menu($donnee);
      array_push($menus, $menu);
    }
    return $menus;
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
