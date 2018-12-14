<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;


/*------------------CRUD CATEGORIAS-----------------------*/
$app->get('/admin/categories', function () {
    User::verifyLogin();

    $search = (isset($_GET['search']))? $_GET['search'] : '';
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    
    if ($search != '') {
         $pagination = Category::getPageSearch($search, $page);
    }else{

        $pagination = Category::getPage($page);
    }

    $pages = [];

    for ($i=0; $i < $pagination['pages']; $i++) { 
        array_push($pages, [
            'href'=>'/admin/categories?'.http_build_query([
                'page'=>$i+1,
                'search'=>$search
            ]),
            'text'=>$i+1
        ]);
    }

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("categories", array(
       "categories" => $pagination['data'],
       "search" => $search,
       'pages' => $pages
    ));

    exit();
});

$app->get('/admin/categories/create', function () {
    User::verifyLogin();
    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("categories-create");

    exit();
});


//receber post
$app->post('/admin/categories/create', function () {
    User::verifyLogin();

    $category = new Category();

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");
    exit();

});

$app->get('/admin/categories/:idcategory/delete', function ($idcategory) {
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->delete();

    header("Location: /admin/categories");
    exit();
});

$app->get('/admin/categories/:idcategory', function ($idcategory) {
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("categories-update", array(
        "category" => $category->getValues()
    ));

    exit();
});

$app->post('/admin/categories/:idcategory', function ($idcategory) {
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");

    exit();
});


$app->get('/admin/categories/:idcategory/products', function ($idcategory) {

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);


    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("categories-products", array(
        "category" => $category->getValues(),
        "productsRelated" => $category->getProducts(),
        "productsNotRelated" => $category->getProducts(false)
    ));


    exit();
});

//relacionar produto com catecoria
$app->get('/admin/categories/:idcategory/products/:idproduct/add', function ($idcategory, $idproduct) {

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);


    $product = new Product();

    $product->get((int)$idproduct);

    $category->addProduct($product);

    header("Location: /admin/categories/" . $idcategory . "/products");


    exit();
});


//remover produto da categoria
$app->get('/admin/categories/:idcategory/products/:idproduct/remove', function ($idcategory, $idproduct) {

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);


    $product = new Product();

    $product->get((int)$idproduct);

    $category->removeProduct($product);

    header("Location: /admin/categories/" . $idcategory . "/products");

    exit();
});


?>