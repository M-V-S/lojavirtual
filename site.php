<?php

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;


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
        'products'=>$cart->getProduct(),
        'error'=>Cart::getMsgError()
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

//Calcular frete
$app->post('/cart/freight', function(){    
    $cart = Cart::getFromSession();

    $cart->setFreight($_POST['zipcode']);
   
    header("Location: /cart");
    exit;

});

//checar
$app->get('/checkout', function(){

    User::verifyLogin(false);

    $cart = Cart::getFromSession();

    $address = new Address(); 

    $page = new Page();
    $page->setTpl("checkout", [
        "cart" =>$cart->getValues(),
        "address"=>$address->getValues()
    ]);

     exit;

});

//carregar login
$app->get('/login', function(){
    $page = new Page();
    $page->setTpl("login",[
        "error"=>User::getError(),
        "errorRegister"=>User::getErrorRegister(),
        "registerValues"=> (isset($_SESSION['registerValues']))?$_SESSION['registerValues']:[
            "name"=>'',
            "email"=>'',
            "phone"=>''
        ]
    ]);
    exit;

});

//receber senha e password
$app->post('/login', function(){

    try{

        $user = User::login($_POST['login'], $_POST['password']);

    }catch (Exception $e){
        User::setError($e->getMessage());
    }

    header("Location: /checkout");
    exit;

});

$app->get('/logout', function (){
    User::logout();
    header("Location: /login");
    exit();

});


/*--------------------------*/
$app->post('/register', function(){
   $_SESSION['registerValues'] = $_POST;
    
   if (!isset($_POST["name"]) || $_POST["name"] == '') {
       User::setErrorRegister("Preencha seu nome.");
       header("Location: /login");
       exit;
   }

    if (!isset($_POST["email"]) || $_POST["email"] == '') {
       User::setErrorRegister("Preencha seu email.");
       header("Location: /login");
       exit;
    }

    if (!isset($_POST["password"]) || $_POST["password"] == '') {
       User::setErrorRegister("Preencha a senha.");
       header("Location: /login");
       exit;
    }

    if (User::checkLoginExist($_POST['email'])) {
       User::setErrorRegister("Este endereço de e-mail já esta sendo usado por outra usuário.");
       header("Location: /login");
       exit;
    }

   $user = new User();

   $user->setData([
       'inadmin'=>0,
       'deslogin'=>$_POST["email"],
       'desperson'=>$_POST["name"],
       'desemail'=>$_POST["email"],
       'despassword'=>$_POST["password"],
       'nrphone'=>$_POST["phone"]
   ]);

   $user->save();

   User::login($_POST["email"], $_POST["password"]);



   header("Location: /checkout");
   exit();
});

/*------------------ESQUECEU SUA SENHA-----------------------*/
//pedir pagina recuperação da senha
$app->get('/forgot', function () {

    $page = new Page();

    $page->setTpl("forgot");

});

//receber email do forgot.html
$app->post('/forgot', function () {

    User::getForgot($_POST["email"], false);

    header("Location: /forgot/sent");
    exit();
});

//pagina para mostrar que o email foi enviado com sucesso
$app->get('/forgot/sent', function () {

    $page = new Page();

    $page->setTpl("forgot-sent");
    exit();
});

//apos o usuário clicar no link no email dele, ele é redirecionado para a rota asseguir 
$app->get('/forgot/reset', function () {


    $user = User::validForgotDecrypt($_GET["code"]);
    
    $page = new Page();
    //carregar pagina para colocar nova senha
    $page->setTpl("forgot-reset", array(
        "name" => $user['desperson'],
        "code" => $_GET["code"]
    ));
    exit();
});


$app->post('/forgot/reset', function () {


    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUser($forgot["idrecovery"]);

    $user = new User();

    $user->get((int)$forgot["iduser"]);

    $password = User::getPasswordHash($_POST["password"]);

    $user->setPassword($password);

    $page = new Page();

    $page->setTpl("forgot-reset-success");
    exit();
});
