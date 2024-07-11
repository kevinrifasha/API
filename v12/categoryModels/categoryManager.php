<?php

require_once("category.php");

class CategoryManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(category $category)
  {
    $id_master = $category->getId_master();
    $name = $category->getName();
    $sequence = $category->getSequence();
    $department_id = $category->getDepartment_id();
    if (isset($department_id) && !empty($department_id)) {
      $q = $this->_db->prepare("INSERT `categories` SET `id_master`='{$id_master}',`name`='{$name}',`sequence`='{$sequence}', `department_id`='{$department_id}'");
    } else {
      $q = $this->_db->prepare("INSERT `categories` SET `id_master`='{$id_master}',`name`='{$name}',`sequence`='{$sequence}'");
    }
    return $q->execute();
  }

  public function delete(category $category)
  {
    $id = $category->getId();

    $q = $this->_db->prepare("UPDATE  `categories` SET deleted_at = NOW() WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function update(category $category)
  {
    $id = $category->getId();
    $id_master = $category->getId_master();
    $name = $category->getName();
    $sequence = $category->getSequence();
    $department_id = $category->getDepartment_id();

    if (isset($department_id) && !empty($department_id)) {
      $q = $this->_db->prepare("UPDATE `categories` SET `id_master`='{$id_master}',`name`='{$name}',`sequence`='{$sequence}', `department_id`={$department_id} WHERE `id`='{$id}' AND `name`!='Promo'");
    } else {
      $q = $this->_db->prepare("UPDATE `categories` SET `id_master`='{$id_master}',`name`='{$name}',`sequence`='{$sequence}' WHERE `id`='{$id}' AND `name`!='Promo'");
    }
    return $q->execute();
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `categories` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $category = new Category($donnees);
    return $category;
  }

  public function getByMasterId($id)
  {
    // $q = $this->_db->query("SELECT * FROM `categories` WHERE id_master='{$id}' AND `name`!='Promo' AND deleted_at IS NULL ORDER BY sequence ASC");
    // karena tidak ada partnerID di tabel categories jadi di ambil dengan join dari tabel department
    $q = $this->_db->query("SELECT c.*, d.partner_id AS id_partner FROM categories c JOIN departments d ON d.id = c.department_id WHERE c.id_master='{$id}' AND c.name != 'Promo' AND c.deleted_at IS NULL ORDER BY c.sequence ASC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $category = new Category($donnee);
      array_push($categories, $category);
    }
    return $categories;
  }

  public function getByPartnerId($id)
  {
    // $q = $this->_db->query("SELECT * FROM `categories` WHERE id_master='{$id}' AND `name`!='Promo' AND deleted_at IS NULL ORDER BY sequence ASC");
    // karena tidak ada partnerID di tabel categories jadi di ambil dengan join dari tabel department
    $q = $this->_db->query("SELECT c.*, d.partner_id AS id_partner FROM categories c JOIN departments d ON d.id = c.department_id WHERE d.partner_id='{$id}' AND c.name != 'Promo' AND c.deleted_at IS NULL ORDER BY c.sequence ASC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $category = new Category($donnee);
      array_push($categories, $category);
    }
    return $categories;
  }

  public function getByMasterIdDD($id)
  {
    $q = $this->_db->query("SELECT * FROM `categories` WHERE id_master='{$id}' ORDER BY sequence ASC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $category = new Category($donnee);
      array_push($categories, $category);
    }
    return $categories;
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
