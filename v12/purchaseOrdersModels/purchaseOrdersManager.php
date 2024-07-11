<?php

require_once("purchaseOrders.php");

class PurchaseOrdersManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(purchaseOrders $purchaseOrders)
  {
    $supplier_id = $purchaseOrders->getSupplier_id();
    $partner_id = $purchaseOrders->getPartner_id();
    $master_id = $purchaseOrders->getMaster_id();
    $total = $purchaseOrders->getTotal();

    $q = $this->_db->prepare("INSERT INTO `purchase_orders` SET `supplier_id`='{$supplier_id}',`master_id`='{$master_id}',`partner_id`='{$partner_id}',`master_id`='{$master_id}' ");
    return $q->execute();
  }

  public function delete(purchaseOrders $purchaseOrders)
  {
    $id = $purchaseOrders->getId();

    $q = $this->_db->prepare("UPDATE `purchase_orders` SET deleted_at=NOW() WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function update(purchaseOrders $purchaseOrders)
  {
    $supplier_id = $purchaseOrders->getSupplier_id();
    $partner_id = $purchaseOrders->getPartner_id();
    $master_id = $purchaseOrders->getMaster_id();
    $total = $purchaseOrders->getTotal();

    $q = $this->_db->prepare("UPDATE `purchase_orders` SET `supplier_id`='{$supplier_id}',`master_id`='{$master_id}',`partner_id`='{$partner_id}',`master_id`='{$master_id}' WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $purchaseOrders = new PurchaseOrders($donnees);
    return $purchaseOrders;
  }

  public function getByMasterId($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders` WHERE master_id='{$id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $purchaseOrders = new PurchaseOrders($donnee);
      array_push($categories, $purchaseOrders);
    }
    return $categories;
  }

  public function getByPartnerId($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders` WHERE partner_id='{$id}' AND deleted_at IS NULL ORDER BY id DESC");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $purchaseOrders = new PurchaseOrders($donnee);
      array_push($categories, $purchaseOrders);
    }
    return $categories;
  }

  public function setDb(PDO $db)
  {
    $this->_db = $db;
  }
}
