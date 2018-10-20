<?php
use \Hcode\Page;
use \Hcode\Model\Category;


//solicitar pagina principal do site
$app->get('/', function() {

    $page = new Page();

    $page->setTpl("index");
});


//CATEGORIA STORE
$app->get('/categories/:idcategory', function($idcategory){
	$category = new Category();

	$category->get((int)$idcategory);

	$page = new Page();

	 $page->setTpl("category", [
	 	"category"=>$category->getValues()
	 ]);
	
	 $category = new Category();

	 $category->get((int)$idcategory);

	$page = new Page();
    $page->setTpl("category", [
    	"category"=> $category->getValues(),
    	//"products"=>$products[]
    ]);
});





?>