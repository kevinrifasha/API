<?php


class NotificationManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function pushPaymentNotification($dev_token, $title, $message, $no_meja, $channel_id, $methodPay, $status, $queue, $id_trans, $id_partner, $action)
  {
    if ($dev_token != "TEMPORARY_TOKEN") {
      $fcm_token = $dev_token;

      $url = "https://fcm.googleapis.com/fcm/send";
      $header = [
        'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
        'content-type: application/json'
      ];

      $notification = [
        'title' => $title,
        'body' => $message,
        'android_channel_id' => $channel_id
      ];
      $extraNotificationData = ["status" => $status, "event" => "payment", "queue" => $queue, "message" => $message, "title" => $title, "action" => $action, "id_transaction" => $id_trans, "partnerID" => $id_partner, "methodPay" => $methodPay];

      $fcmNotification = [
        'to'        => $fcm_token,
        'notification' => $notification,
        'data' => $extraNotificationData
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
  }

  public function getByVariantId($id)
  {
    $q = $this->_db->query("SELECT * FROM `recipe` WHERE id_variant='{$id}'");

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
