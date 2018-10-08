<?php 

require_once("vendor/autoload.php");

$app = new \Slim\Slim();

use \Hcode\Page;

$app->config('debug', true);

$app->get('/', function() {

	

    $page = new Page();

    $page->setTpl("index");


});

$app->run();

 ?>