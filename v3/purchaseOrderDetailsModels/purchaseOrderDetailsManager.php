<?php

require_once("purchaseOrderDetails.php");

class PurchaseOrderDetailsManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(purchaseOrderDetails $purchaseOrderDetails)
  {
    $supplier_id = $purchaseOrderDetails->getSupplier_id();
    $raw_id = $purchaseOrderDetails->getRaw_id();
    $menu_id = $purchaseOrderDetails->getMenu_id();
    $qty = $purchaseOrderDetails->getQty();
    $metric_id = $purchaseOrderDetails->getMetric_id();
    $price = $purchaseOrderDetails->getPrice();
    $partner_id = $purchaseOrderDetails->getPartner_id();
    $master_id = $purchaseOrderDetails->getMaster_id();

    $q = $this->_db->prepare("INSERT `purchase_orders_details` SET `supplier_id`='{$supplier_id}',`raw_id`='{$raw_id}',`menu_id`='{$menu_id}',`qty`='{$qty}',`metric_id`='{$metric_id}',`price`='{$price}',`partner_id`='{$partner_id}',`master_id`='{$master_id}' ");
    return $q->execute();
  }

  public function delete(purchaseOrderDetails $purchaseOrderDetails)
  {
    $id = $purchaseOrderDetails->getId();

    $q = $this->_db->prepare("UPDATE `purchase_orders_details` SET deleted_at=NOW() WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function update(purchaseOrderDetails $purchaseOrderDetails)
  {
    $id = $purchaseOrderDetails->getId();
    $supplier_id = $purchaseOrderDetails->getSupplier_id();
    $raw_id = $purchaseOrderDetails->getRaw_id();
    $menu_id = $purchaseOrderDetails->getMenu_id();
    $qty = $purchaseOrderDetails->getQty();
    $metric_id = $purchaseOrderDetails->getMetric_id();
    $price = $purchaseOrderDetails->getPrice();
    $partner_id = $purchaseOrderDetails->getPartner_id();
    $master_id = $purchaseOrderDetails->getMaster_id();

    $q = $this->_db->prepare("UPDATE `purchase_orders_details` SET `supplier_id`='{$supplier_id}',`raw_id`='{$raw_id}',`menu_id`='{$menu_id}',`qty`='{$qty}',`metric_id`='{$metric_id}',`price`='{$price}',`partner_id`='{$partner_id}',`master_id`='{$master_id}' WHERE `id`='{$id}'");
    return $q->execute();
  }

  public function getById($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders_details` WHERE id='{$id}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $purchaseOrderDetails = new PurchaseOrderDetails($donnees);
    return $purchaseOrderDetails;
  }

  public function getByPOId($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders_details` WHERE purchase_order_id='{$id}'");
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);

    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $donnee['qty'] = (float) $donnee['qty'];
      $purchaseOrderDetails = new PurchaseOrderDetails($donnee);
      array_push($categories, $purchaseOrderDetails);
    }
    return $categories;
  }

  public function getByMasterId($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders_details` WHERE master_id='{$id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $purchaseOrderDetails = new PurchaseOrderDetails($donnee);
      array_push($categories, $purchaseOrderDetails);
    }
    return $categories;
  }

  public function getByPartnerId($id)
  {
    $q = $this->_db->query("SELECT * FROM `purchase_orders_details` WHERE partner_id='{$id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $categories = array();
    foreach ($donnees as $donnee) {
      $purchaseOrderDetails = new PurchaseOrderDetails($donnee);
      array_push($categories, $purchaseOrderDetails);
    }
    return $categories;
  }

  public function setDb(PDO $db)
  {
    $this->_db = $db;
  }
}
