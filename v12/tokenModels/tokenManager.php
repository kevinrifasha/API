<?php

require_once("token.php");

date_default_timezone_set('Asia/Jakarta');
class TokenManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function stringEncryption($action, $string)
  {
    $output = false;

    $encrypt_method = 'AES-256-CBC';                // Default
    $secret_key = 'Sopians#2019!';               // Change the key!
    $secret_iv = 'VarioKENCENG!Sopian';  // Change the init vector!

    // hash
    $key = hash('sha256', $secret_key);

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'encrypt') {
      $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
      $output = base64_encode($output);
    } else if ($action == 'decrypt') {
      $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
  }

  public function validate($tkn)
  {
    $tknDecrypt = self::stringEncryption('decrypt', $tkn);
    $tknDecrypt = json_decode($tknDecrypt);

    if (
      isset($tknDecrypt->id) && !empty($tknDecrypt->id)
      && isset($tknDecrypt->role) && !empty($tknDecrypt->role)
    ) {
      $expires_in = $tknDecrypt->expired;
      $created_at = $tknDecrypt->created_at;
      $expire_datetime = strtotime($created_at);
      $expire_datetime = $expire_datetime + $expires_in;
      $timeNow = strtotime("now");
      if ($timeNow <= $expire_datetime) {
        return $tkn;
      } else {
        $res = array();
        $res['status'] = 401;
        $res['success'] = 0;
        $res['msg'] = "Expired Token";
        return $res;
      }
    } else {
      $res = array();
      $res['status'] = 403;
      $res['success'] = 0;
      $res['msg'] = "Wrong Token";
      return $res;
    }
    return $tkn;
  }

  public function validateCreate($tkn)
  {
    $tknDecrypt = self::stringEncryption('decrypt', $tkn);
    $tknDecrypt = json_decode($tknDecrypt);

    if (
      isset($tknDecrypt->id) && !empty($tknDecrypt->id)
      && isset($tknDecrypt->role) && !empty($tknDecrypt->role)
    ) {
      $expires_in = $tknDecrypt->expired;
      $created_at = $tknDecrypt->created_at;
      $expire_datetime = strtotime($created_at);
      $expire_datetime = $expire_datetime + $expires_in;
      $timeNow = strtotime("now");
      if ($timeNow <= $expire_datetime) {
        $res = array();
        $res['status'] = 200;
        $res['success'] = 1;
        $res['msg'] = "success";
        return $res;
      } else {
        $res = array();
        $res['status'] = 401;
        $res['success'] = 0;
        $res['msg'] = "Expired Token";
        return $res;
      }
    } else {
      $res = array();
      $res['status'] = 403;
      $res['success'] = 0;
      $res['msg'] = "Wrong Token";
      return $res;
    }

    $res = array();
    $res['status'] = 200;
    $res['success'] = 1;
    $res['msg'] = "success";
    return $res;
  }

  public function reCreate($tkn)
  {
    $today = date("Y-m-d H:i:s");
    $tknDecrypt = self::stringEncryption('decrypt', $tkn);
    $tknDecrypt = json_decode($tknDecrypt);

    $tkn = json_encode(['id' => $tknDecrypt->id, 'role' => $tknDecrypt->role, 'created_at' => $today, 'expired' => 500, 'masterID' => $tknDecrypt->masterID, 'partnerID' => $tknDecrypt->partnerID]);
    $tkn = self::stringEncryption('encrypt', $tkn);

    return $tkn;
  }

  public function addToken(token $tkn)
  {
    $q = $this->_db->prepare('INSERT INTO token SET token = :token, created_at = :created_at, type = :type, expires_in = :expires_in');
    $q->bindValue(':token', $tkn->getToken());
    $q->bindValue(':created_at', $tkn->getCreated_at());
    $q->bindValue(':type', $tkn->getType());
    $q->bindValue(':expires_in', $tkn->getExpires_in());
    return $q->execute();
  }

  public function getTokens($token)
  {
    $q = $this->_db->query("SELECT * FROM token WHERE token='{$token}'");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false) {
      $res = array();
      $res['success'] = 0;
      $res['msg'] = "Wrong Token";
      return $res;
    } else {
      $newToken = new Token($donnees);
      $tokenDetail = $newToken->getDetails();

      $expires_in = $tokenDetail['expires_in'];
      $created_at = $tokenDetail['created_at'];
      $expire_datetime = strtotime($created_at);
      $expire_datetime = $expire_datetime + $expires_in;
      $timeNow = strtotime("now");
      if ($timeNow <= $expire_datetime) {
        return $tokenDetail;
      } else {
        $res = array();
        $res['success'] = 0;
        $res['msg'] = "Expired Token";
        return $res;
      }
    }
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
