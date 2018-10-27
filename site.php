<?php

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;


//solicitar pagina principal do site
$app->get('/', function () {
    $Product = Product::listAll();

    $page = new Page();

    $page->setTpl("index", [
        "products" => Product::checkList($Product)
    ]);
     exit;
});

//CATEGORIA STORE
$app->get('/categories/:idcategory', function ($idcategory) {
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $category = new Category();

    $category->get((int)$idcategory);

    $panination = $category->getProductsPage($page);

    $pages = [];

    for ($i = 1; $i <= $panination['pages']; $i++) {
        array_push($pages, [
            'link' => '/categories/' . $category->getidcategory() . '?page=' . $i,
            'page' => $i
        ]);
    }

    $page = new Page();
    $page->setTpl("category", [
        "category" => $category->getValues(),
        "products" => $panination["data"],
        'pages' => $pages
    ]);
     exit;
});


$app->get("/products/:desurl", function($desurl){
    $product = new product();

    $product->getFromURL($desurl);

 

    $page = new Page();

    $page->setTpl("product-detail",[
        "product"=>$product->getValues(),
        "categories"=>$product->getCategories()
    ]);
     exit;
});

//carregar pagina do carrinho e listar todos os produtos do carrinho
$app->get("/cart", function () {
    $cart =  Cart::getFromSession();

     
    $page = new Page();

    $page->setTpl("cart", [
        'cart'=>$cart->getValues(),
        'products'=>$cart->getProduct()
    ]);
     exit;
});


//adicionar um produto ao carrinho de compra
$app->get('/cart/:idproduct/add', function($idproduct){


    $product = new product();
 
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $qtd = (isset($_GET['qtd']))? (int)$_GET['qtd']:1;

    for ($i=0; $i < $qtd; $i++) { 
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit();

    

});

//remover somente um produto especifico do carrinho
$app->get('/cart/:idproduct/minus', function($idproduct){
    $product = new product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);

    header("Location: /cart");
    exit;

});

//remover todos os produtos do carrinho
$app->get('/cart/:idproduct/remove', function($idproduct){
    $product = new product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;

});








?>