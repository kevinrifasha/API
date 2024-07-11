<?php

require_once("metricConvert.php");

class MetricConvertManager
{
  private $_db; // Instance de PDO

  public function __construct($db)
  {
    $this->setDb($db);
  }

  public function getByMetricsId($idm1, $idm2)
  {
    $q = $this->_db->query("SELECT * FROM `metric_convert` WHERE id_metric1='{$idm1}' AND `id_metric2`='{$idm2}' ");

    $donnees = $q->fetch(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $metricConvert = new MetricConvert($donnees);
    return $metricConvert;
  }

  public function getByMetricsConvert($metricId)
  {
    $q = $this->_db->query("SELECT * FROM `metric_convert` WHERE id_metric1='{$metricId}' OR id_metric2='{$metricId}'");

    $donnees = $q->fetchAll(PDO::FETCH_ASSOC);
    if (is_null($donnees) || $donnees == false)
      return false;
    else
      $res = array();
    foreach ($donnees as $donnee) {
      $metricConvert = new MetricConvert($donnee);
      array_push($res, $metricConvert);
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
