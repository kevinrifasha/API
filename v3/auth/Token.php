<?php

date_default_timezone_set('Asia/Jakarta');

class Token
{

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
      && isset($tknDecrypt->id_master) && !empty($tknDecrypt->id_master)
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
        $res['msg'] = "Success";
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
    return $tkn;
  }

  public function employeeValidate($tkn)
  {
    $tknDecrypt = self::stringEncryption('decrypt', $tkn);
    $tknDecrypt = json_decode($tknDecrypt);
    if (
      isset($tknDecrypt->id) && !empty($tknDecrypt->id)
      && isset($tknDecrypt->role_id)
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
        $res['msg'] = "Expired Token";
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
    return $tkn;
  }

  public function reCreate($tkn)
  {
    $today = date("Y-m-d H:i:s");
    $tknDecrypt = self::stringEncryption('decrypt', $tkn);
    $tknDecrypt = json_decode($tknDecrypt);
    $checker = self::employeeValidate($tkn);
    if ($checker["status"] == 403) {
      // $tkn = json_encode(['id'=>$tknDecrypt->id, 'name'=>$tknDecrypt->name, 'phone'=>$tknDecrypt->phone, 'email'=>$tknDecrypt->email, 'created_at'=>$today, 'expired'=>3600]);
    } else {
      $tkn = json_encode(['id' => $tknDecrypt->id, 'role_id' => $tknDecrypt->role_id, 'id_master' => $tknDecrypt->id_master, 'id_partner' => $tknDecrypt->id_partner, 'created_at' => $today, 'expired' => 200]);
    }
    $tkn = self::stringEncryption('encrypt', $tkn);
    $tknDecrypt = self::stringEncryption('decrypt', $tkn);
    return $tkn;
  }
}
