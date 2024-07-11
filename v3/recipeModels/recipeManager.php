<?php

require_once("recipe.php");

class RecipeManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(recipe $rcp)
  {
    $id_menu = $rcp->getId_menu();
    $id_raw = $rcp->getId_raw();
    $qty = $rcp->getQty();
    $id_metric = $rcp->getId_metric();
    $id_variant = $rcp->getId_variant();
    $id_partner = $rcp->getId_partner();

    $q = $this->_db->prepare("INSERT INTO `recipe` SET `id_menu`='{$id_menu}',`id_raw`='{$id_raw}',`qty`='{$qty}',`id_metric`='{$id_metric}', `id_variant`='{$id_variant}',`partner_id`='{$id_partner}'");
    return $q->execute();
  }

  public function update(recipe $rcp)
  {
    $id = $rcp->getId();
    $id_menu = $rcp->getId_menu();
    $id_raw = $rcp->getId_raw();
    $qty = $rcp->getQty();
    $id_metric = $rcp->getId_metric();
    $id_variant = $rcp->getId_variant();
    $id_partner = $rcp->getId_partner();

    $q = $this->_db->prepare("UPDATE `recipe` SET `id_menu`='{$id_menu}',`id_raw`='{$id_raw}',`qty`='{$qty}',`id_metric`='{$id_metric}', `id_variant`='{$id_variant}',`partner_id`='{$id_partner}' WHERE id='{$id}'");
    return $q->execute();
  }

  public function delete($id)
  {
    $q = $this->_db->prepare("UPDATE `recipe` SET deleted_at=NOW() WHERE id='{$id}'");
    return $q->execute();
  }

  public function deleteByVariant($id)
  {
    $q = $this->_db->prepare("UPDATE `recipe` SET deleted_at=NOW() WHERE id_variant='{$id}'");
    return $q->execute();
  }

  public function deleteByMenuId($id)
  {
    $q = $this->_db->prepare("UPDATE `recipe` SET deleted_at=NOW() WHERE id_menu='{$id}'");
    return $q->execute();
  }

  public function getByRawID($id)
  {
    $q = $this->_db->query("SELECT * FROM `recipe` WHERE id_raw ='{$id}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    foreach ($donnees as $value) {
      $res[$i] = new Recipe($value);
      $i += 1;
    }
    return $res;
  }
  public function getByMenuId($id)
  {
    $q = $this->_db->query("SELECT * FROM `recipe` WHERE id_menu ='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    foreach ($donnees as $value) {
      $res[$i] = new Recipe($value);
      $i += 1;
    }
    return $res;
  }

  public function getByVariantID($id)
  {
    $q = $this->_db->query("SELECT * FROM `recipe` WHERE id_variant='{$id}' AND deleted_at IS NULL");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    foreach ($donnees as $value) {
      $res[$i] = new Recipe($value);
      $i += 1;
    }
    return $res;
  }

  public function getByMasterID($id)
  {
    $q = $this->_db->query("SELECT r.*, mtrc.name AS metric_name, m.nama AS menu_name, m.id AS menu_id, rm.name AS raw_material_name FROM `recipe` r JOIN menu m ON r.id_menu=m.id JOIN partner p ON m.id_partner=p.id JOIN raw_material rm ON rm.id_partner=p.id JOIN metric mtrc ON mtrc.id=r.id_metric WHERE p.id_master='{$id}' AND r.deleted_at IS NULL GROUP BY r.id ORDER BY r.id_menu");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    $j = 0;
    $firstLoop = true;
    foreach ($donnees as $value) {
      if ($firstLoop == true) {
        $res[$i]['menuName'] = $value['menu_name'];
        $res[$i]['menuID'] = $value['menu_id'];
        $res[$i]['recipe'][$j]['ID'] = $value['id'];
        $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
        $res[$i]['recipe'][$j]['raw_material']['label'] = $value['raw_material_name'];
        $firstLoop = false;
      } else {
        if ($res[$i]['menuID'] == $value['menu_id']) {
          $res[$i]['recipe'][$j]['ID'] = $value['id'];
          $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
          $res[$i]['recipe'][$j]['raw_material']['label'] = $value['raw_material_name'];
          $j += 1;
        } else {
          $j = 0;
          $i += 1;
          $res[$i]['menuName'] = $value['menu_name'];
          $res[$i]['menuID'] = $value['menu_id'];
          $res[$i]['recipe'][$j]['ID'] = $value['id'];
          $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
          $res[$i]['recipe'][$j]['raw_material']['label'] = $value['raw_material_name'];
        }
      }
    }
    return $res;
  }

  public function getByPartnerID($id)
  {
    $q = $this->_db->query("SELECT r.*, m.nama AS menuName, m.img_data AS menuImage,rw.name AS rawName, me.name AS metricName FROM recipe r JOIN menu m ON m.id=r.id_menu JOIN raw_material rw ON rw.id=r.id_raw JOIN metric me ON me.id=r.id_metric WHERE m.id_partner='{$id}' AND m.is_recipe='1' ORDER BY r.id_menu");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    $j = 0;
    $firstLoop = true;
    foreach ($donnees as $value) {
      if ($firstLoop == true) {
        $res[$i]['menuName'] = $value['menuName'];
        $res[$i]['menuImage'] = $value['menuImage'];
        $res[$i]['menuID'] = $value['id_menu'];
        $res[$i]['recipe'][$j]['ID'] = $value['id'];
        $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
        $res[$i]['recipe'][$j]['raw_material']['label'] = $value['rawName'];
        $res[$i]['recipe'][$j]['raw_material']['id_metric'] = $value['id_metric'];
        $res[$i]['recipe'][$j]['raw_material']['metricName'] = $value['metricName'];
        $res[$i]['recipe'][$j]['raw_material']['qty'] = $value['qty'];
        $res[$i]['recipe'][$j]['raw_material']['id_raw'] = $value['id_raw'];
        $firstLoop = false;
      } else {
        if ($res[$i]['menuID'] == $value['id_menu']) {
          $j += 1;
          $res[$i]['recipe'][$j]['ID'] = $value['id'];
          $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
          $res[$i]['recipe'][$j]['raw_material']['label'] = $value['rawName'];
          $res[$i]['recipe'][$j]['raw_material']['id_metric'] = $value['id_metric'];
          $res[$i]['recipe'][$j]['raw_material']['metricName'] = $value['metricName'];
          $res[$i]['recipe'][$j]['raw_material']['qty'] = $value['qty'];
          $res[$i]['recipe'][$j]['raw_material']['id_raw'] = $value['id_raw'];
        } else {
          $j = 0;
          $i += 1;
          $res[$i]['menuName'] = $value['menuName'];
          $res[$i]['menuImage'] = $value['menuImage'];
          $res[$i]['menuID'] = $value['id_menu'];
          $res[$i]['recipe'][$j]['ID'] = $value['id'];
          $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
          $res[$i]['recipe'][$j]['raw_material']['label'] = $value['rawName'];
          $res[$i]['recipe'][$j]['raw_material']['id_metric'] = $value['id_metric'];
          $res[$i]['recipe'][$j]['raw_material']['metricName'] = $value['metricName'];
          $res[$i]['recipe'][$j]['raw_material']['qty'] = $value['qty'];
          $res[$i]['recipe'][$j]['raw_material']['id_raw'] = $value['id_raw'];
        }
      }
    }
    return $res;
  }

  public function getByPartnerIDALL($id)
  {
    $q = $this->_db->query("SELECT r.*, m.nama AS menuName, m.img_data AS menuImage,rw.name AS rawName, me.name AS metricName FROM recipe r JOIN menu m ON m.id=r.id_menu JOIN raw_material rw ON rw.id=r.id_raw JOIN metric me ON me.id=r.id_metric WHERE m.id_partner='{$id}' AND r.id_raw!=0 ORDER BY r.id_menu");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    $i = 0;
    $j = 0;
    $firstLoop = true;
    foreach ($donnees as $value) {
      if ($firstLoop == true) {
        $res[$i]['menuName'] = $value['menuName'];
        $res[$i]['menuImage'] = $value['menuImage'];
        $res[$i]['menuID'] = $value['id_menu'];
        $res[$i]['recipe'][$j]['ID'] = $value['id'];
        $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
        $res[$i]['recipe'][$j]['raw_material']['label'] = $value['rawName'];
        $res[$i]['recipe'][$j]['raw_material']['id_metric'] = $value['id_metric'];
        $res[$i]['recipe'][$j]['raw_material']['metricName'] = $value['metricName'];
        $res[$i]['recipe'][$j]['raw_material']['qty'] = $value['qty'];
        $res[$i]['recipe'][$j]['raw_material']['id_raw'] = $value['id_raw'];
        $firstLoop = false;
      } else {
        if ($res[$i]['menuID'] == $value['id_menu']) {
          $j += 1;
          $res[$i]['recipe'][$j]['ID'] = $value['id'];
          $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
          $res[$i]['recipe'][$j]['raw_material']['label'] = $value['rawName'];
          $res[$i]['recipe'][$j]['raw_material']['id_metric'] = $value['id_metric'];
          $res[$i]['recipe'][$j]['raw_material']['metricName'] = $value['metricName'];
          $res[$i]['recipe'][$j]['raw_material']['qty'] = $value['qty'];
          $res[$i]['recipe'][$j]['raw_material']['id_raw'] = $value['id_raw'];
        } else {
          $j = 0;
          $i += 1;
          $res[$i]['menuName'] = $value['menuName'];
          $res[$i]['menuImage'] = $value['menuImage'];
          $res[$i]['menuID'] = $value['id_menu'];
          $res[$i]['recipe'][$j]['ID'] = $value['id'];
          $res[$i]['recipe'][$j]['raw_material']['value'] = $value['id_raw'];
          $res[$i]['recipe'][$j]['raw_material']['label'] = $value['rawName'];
          $res[$i]['recipe'][$j]['raw_material']['id_metric'] = $value['id_metric'];
          $res[$i]['recipe'][$j]['raw_material']['metricName'] = $value['metricName'];
          $res[$i]['recipe'][$j]['raw_material']['qty'] = $value['qty'];
          $res[$i]['recipe'][$j]['raw_material']['id_raw'] = $value['id_raw'];
        }
      }
    }
    return $res;
  }
  public function setDb(PDO $db)
  {
    $this->_db = $db;
  }
}
