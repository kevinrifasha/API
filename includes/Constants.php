<?php
/**
 * Created by PhpStorm.
 * User: Belal
 * Date: 04/02/17
 * Time: 7:50 PM
 */
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__. '/..');
    $dotenv->load();

    define('DB_USERNAME', $_ENV['DB_USERNAME']);
    define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
    define('DB_HOST', $_ENV['DB_HOST']);
    define('DB_NAME', $_ENV['DB_NAME']);

    define('USER_CREATED', 0);
    define('USER_ALREADY_EXIST', 1);
    define('USER_NOT_CREATED', 2);
    define('USER_UPDATED', 3);
    define('USER_NOT_UPDATED', 4);
    define('TRANSAKSI_CREATED', 5);
    define('FAILED_TO_CREATE_TRANSAKSI', 6);
    define('REMOVE_MENU', 7);
    define('FAILED_TO_REMOVE_MENU', 8);

?>
