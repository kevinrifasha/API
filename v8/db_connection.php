<?php
// require  __DIR__ . '/../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__. '/..');
// $dotenv->load();
// date_default_timezone_set('Asia/Jakarta');
// $db_conn = mysqli_connect($_ENV['DB_HOST'],$_ENV['DB_USERNAME'],$_ENV['DB_PASSWORD'],$_ENV['DB_NAME']);
$db_conn = mysqli_connect('localhost:3306','urhub_admin','MonkeyChampagne1998','urhub_dev')
?>