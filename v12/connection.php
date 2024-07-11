<?php

    function escape_error($val)
    {
        $magic_quotes =get_magic_quotes_gpc();

        if($magic_quotes)
        {
            $val = addslashes($val);
        }	
        return $val;
    }
    
    function connectBase(){
        require  __DIR__ . '/../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__. '/..');
        $dotenv->load();
        date_default_timezone_set('Asia/Jakarta');

        $host = $_ENV['DB_HOST'];
        $database = $_ENV['DB_NAME'];
        $username = $_ENV['DB_USERNAME'];
        $password =  $_ENV['DB_PASSWORD'];

        try {
            $newPDO = new PDO ('mysql:host='.$host.';dbname='.$database, $username, $password);
            return  $newPDO;
        }
        catch (PDOException $e) {
            print "Erreur !: " . $e->getMessage() . "<br/>";
            die();
        }
    }
?> 