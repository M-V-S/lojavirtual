<?php 

require_once("vendor/autoload.php");

$app = new \Slim\Slim();

use \Hcode\Page;
use \Hcode\PageAdmin;



$app->config('debug', true);

//solicitar pagina principal do site
$app->get('/', function() {

    $page = new Page();

    $page->setTpl("index");
});

//solicitar pagina  inicial do administrador
$app->get('/admin', function(){
	$pageAdmin = new PageAdmin();
	$pageAdmin->setTpl("index");

});




$app->run();

 ?>