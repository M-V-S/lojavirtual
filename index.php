<?php 
session_start();
require_once("vendor/autoload.php");

$app = new \Slim\Slim();

use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;



$app->config('debug', true);

//solicitar pagina principal do site
$app->get('/', function() {

    $page = new Page();

    $page->setTpl("index");
});

//solicitar pagina  inicial do administrador
$app->get('/admin', function(){
	User::verifyLogin();
	$pageAdmin = new PageAdmin();
	$pageAdmin->setTpl("index");

});
//solicitar pagina login
$app->get('/admin/login', function(){
	$pageAdmin = new PageAdmin($opts = array(
		"header" => false,
		"folter" => false
	));

	$pageAdmin->setTpl("login");

});
//Receber method post login/ Fazer validação
$app->post('/admin/login', function(){
	User::login($_POST['login'], $_POST['password']);

	header("Location: /admin");
	exit;
});

$app->get('/admin/logout', function(){
	User::logout();
	header("Location: /admin/login");
	exit();
});






$app->run();

 ?>