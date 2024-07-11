<?php 
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://partner.ur-hub.com" || $http_origin == "https://master.ur-hub.com" || $http_origin == "https://admin.ur-hub.com")
{  
    header("Access-Control-Allow-Origin: $http_origin");
}
header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');  
session_start();
require_once("../includes/fonctions.php");
require_once("../modele/userManager.php"); 

$json = file_get_contents('php://input'); 


 
	$_SESSION = array();

	// session stored through a cookies; 
	if(isset($_COOKIE[session_name()]))
	{
	    setcookie(session_name(),'',time()-3500,'/');
	}


	// remove all session variables
	session_unset();

	// destroy the session
	session_destroy();   


	 
    $SignoutMsg = "Signout succes";
    $SignoutMsg = json_encode($SignoutMsg);
    echo($SignoutMsg);

?>