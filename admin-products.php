<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\product;


$app->get('/admin/products', function () {
    User::verifyLogin();

    $products = product::listAll();

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("products", array(
        "products" => $products
    ));

});

$app->get('/admin/products/create', function () {
    User::verifyLogin();

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("products-create");
});

$app->post('/admin/products/create', function () {
    User::verifyLogin();

    $product = new product();

    $product->setData($_POST);

    $product->save();

    header("Location: /admin/products");
    exit();
});

$app->get('/admin/products/:idproduct', function ($idproduct) {
    User::verifyLogin();

    $product = new product();

    $product->get((int)$idproduct);

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("products-update", array(
        "product" => $product->getValues()
    ));

    exit();
});

$app->post('/admin/products/:idproduct', function ($idproduct) {
    User::verifyLogin();

    $product = new product();

    $product->get((int)$idproduct);

    $product->setData($_POST);

    $product->save();

    if ((int)$_FILES["file"]["size"] > 0) {
        $product->setPhoto($_FILES["file"]);
    }


    header('Location: /admin/products');
    exit();
});

$app->get('/admin/products/:idproduct/delete', function ($idproduct) {
    User::verifyLogin();

    $product = new product();

    $product->get((int)$idproduct);

    $product->delete();

    header("Location: /admin/products");
    exit();
});


?>