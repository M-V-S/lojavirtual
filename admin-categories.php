<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;


/*------------------CRUD CATEGORIAS-----------------------*/
$app->get('/admin/categories', function () {
    User::verifyLogin();


    $categories = Category::listAll();

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("categories", array(
        "categories" => $categories
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