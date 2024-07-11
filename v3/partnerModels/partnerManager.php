<?php

require_once("partner.php");
require '../../db_connection.php';

class PartnerManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function add(partner $partner)
  {
    $id = $partner->getId();
    $id_master = $partner->getId_master();
    // $email= $partner->getEmail();
    // $password= $partner->getPassword();
    $name = $partner->getName();
    $phone = $partner->getPhone();
    $charge_ur_shipper = $partner->getCharge_ur_shipper();
    $charge_ur = $partner->getCharge_ur();
    $trial_until = $partner->getTrial_until();
    $parent_id = $partner->getParent_id();
    $parent_id = $partner->getParent_id();
    $subscription_status = $partner->getSubscription_status();
    $primary_subscription_id = $partner->getPrimary_subscription_id();
    $subscription_until = $partner->getSubscription_until();
    $appendQuery = "";
    if($parent_id == null){
        $appendQuery = "parent_id=NULL";
    }else{
        $appendQuery = "parent_id='{$parent_id}',subscription_status='{$subscription_status}', primary_subscription_id='{$primary_subscription_id}', subscription_until='{$subscription_until}'";
    }
    $q = $this->_db->prepare("INSERT INTO partner SET id='{$id}', name = '{$name}', phone = '{$phone}', id_master='{$id_master}', status=1, charge_ur='{$charge_ur}', charge_ur_shipper='{$charge_ur_shipper}', trial_until='{$trial_until}'," . $appendQuery);

    $a = $q->execute();
    if ($a != false) {
      return $this->_db->lastInsertId();
    }
    return $a;
  }

  public function update(partner $prtnr)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $name = mysqli_real_escape_string($db_conn, $prtnr->getName());
    $address = mysqli_real_escape_string($db_conn, $prtnr->getAddress());
    $desc = mysqli_real_escape_string($db_conn, $prtnr->getDesc_map());
    $wifi = mysqli_real_escape_string($db_conn, $prtnr->getWifi_ssid());
    $wifi_pass = mysqli_real_escape_string($db_conn, $prtnr->getWifi_password());

    $q = $this->_db->prepare("UPDATE `partner` SET `name`='{$name}',`address`='{$address}',`phone`='{$prtnr->getPhone()}',`status`='{$prtnr->getStatus()}',`tax`='{$prtnr->getTax()}',`service`='{$prtnr->getService()}', `restaurant_number`='{$prtnr->getRestaurant_number()}',`id_master`='{$prtnr->getId_master()}',`longitude`='{$prtnr->getLongitude()}',`latitude`='{$prtnr->getLatitude()}',`img_map`='{$prtnr->getImg_map()}',`logo`='{$prtnr->getLogo()}',`desc_map`='{$desc}',`is_delivery`='{$prtnr->getIs_delivery()}',`is_takeaway`='{$prtnr->getIs_takeaway()}',`is_open`='{$prtnr->getIs_open()}',`jam_buka`='{$prtnr->getJam_buka()}',`jam_tutup`='{$prtnr->getJam_tutup()}',`wifi_ssid`='{$wifi}',`wifi_password`='{$wifi_pass}',`is_booked`='{$prtnr->getIs_booked()}',`booked_before`='{$prtnr->getBooked_before()}',`created_at`='{$prtnr->getCreated_at()}',`hide_charge`='{$prtnr->getHide_charge()}',`ovo_active`='{$prtnr->getOvo_active()}',`dana_active`='{$prtnr->getDana_active()}',`linkaja_active`='{$prtnr->getLinkaja_active()}',`cc_active`='{$prtnr->getCc_active()}',`debit_active`='{$prtnr->getDebit_active()}',`qris_active`='{$prtnr->getQris_active()}',`partner_type`='{$prtnr->getPartner_type()}', `shipper_location`='{$prtnr->getShipper_location()}', `is_average_cogs`='{$prtnr->getIs_average_cogs()}', `shopeepay_active`='{$prtnr->getShopeepay_active()}', `thumbnail`='{$prtnr->getThumbnail()}', `is_temporary_close`='{$prtnr->getIs_temporary_close()}', `is_temporary_qr`='{$prtnr->getIs_temporary_qr()}', `is_foodcourt`='{$prtnr->getIs_foodcourt()}', `is_centralized`='{$prtnr->getIsCentralized()}', `is_rounding`='{$prtnr->getIs_rounding()}', `rounding_digits`='{$prtnr->getRounding_digits()}', `rounding_down_below`='{$prtnr->getRounding_down_below()}' WHERE `id`='{$prtnr->getId()}'");
    return $q->execute();
  }

  public function login($email, $password)
  {
    // dont_take_it allow us to check if email is already exist or not we dont care about password
    if ($password == 'dont_take_it') {
    } else {
      $q = $this->_db->query("SELECT * FROM partner WHERE email='{$email}' AND password='{$password}'");
    }
    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $newPartner = new Partner($donnees);
    return $newPartner;
  }

  public function getPartnerDetails($id)
  {
    $q = $this->_db->query("SELECT * FROM partner WHERE id='$id'");

    $result = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($result) || $result == false)
      return false;
    else
      $newPartner = new Partner($result);
    return $newPartner;
  }

  public function getByEmail($email)
  {
    $q = $this->_db->query("SELECT * FROM partner WHERE email='{$email}'");

    $result = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($result) || $result == false)
      return false;
    else
      $newPartner = new Partner($result);
    return $newPartner;
  }

  public function getLastIdResto()
  {
    $q = $this->_db->query("SELECT id FROM partner WHERE id NOT LIKE 'q%' ORDER BY id DESC LIMIT 1");

    $result = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($result) || $result == false)
      return false;
    else
      $id = $result;
    return (int)$id['id'];
  }

  public function getAllPartner()
  {

    $q = $this->_db->query("SELECT * FROM partner ");


    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      return new Partner($donnees);
  }

  public function getAllByMasterId($id)
  {

    $q = $this->_db->query("SELECT * FROM partner WHERE id_master='{$id}'");
    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      return false;
    } else
      $res = array();
    foreach ($donnees as $value) {
      array_push($res, $value);
    }
    return $res;
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
