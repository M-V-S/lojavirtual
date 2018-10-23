<?php
use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;


//solicitar pagina principal do site
$app->get('/', function() {
	$Product = Product::listAll();

    $page = new Page();

    $page->setTpl("index", [
    	"products"=>Product::checkList($Product)
    ]);
});

//CATEGORIA STORE
$app->get('/categories/:idcategory', function($idcategory){
	
    $category = new Category();

	$category->get((int)$idcategory);

    $page = new Page();
    $page->setTpl("category", [
    	"category"=> $category->getValues(),
    	"products"=>Product::checkList($category->getProducts())
    ]);
});








?>