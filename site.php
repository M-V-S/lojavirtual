<?php

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


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


$app->get("/products/:desurl", function ($desurl) {
    $product = new product();

    $product->getFromURL($desurl);


    $page = new Page();

    $page->setTpl("product-detail", [
        "product" => $product->getValues(),
        "categories" => $product->getCategories()
    ]);
    exit;
});

//carregar pagina do carrinho e listar todos os produtos do carrinho
$app->get("/cart", function () {
    $cart = Cart::getFromSession();


    $page = new Page();

    $page->setTpl("cart", [
        'cart' => $cart->getValues(),
        'products' => $cart->getProduct(),
        'error' => Cart::getMsgError()
    ]);
    exit;
});


//adicionar um produto ao carrinho de compra
$app->get('/cart/:idproduct/add', function ($idproduct) {


    $product = new product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

    for ($i = 0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit();


});

//remover somente um produto especifico do carrinho
$app->get('/cart/:idproduct/minus', function ($idproduct) {
    $product = new product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);

    header("Location: /cart");
    exit;

});

//remover todos os produtos do carrinho
$app->get('/cart/:idproduct/remove', function ($idproduct) {
    $product = new product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;

});

//Calcular frete
$app->post('/cart/freight', function () {
    $cart = Cart::getFromSession();

    $cart->setFreight($_POST['zipcode']);

    header("Location: /cart");
    exit;

});

//checar dados da compra e endereço
$app->get('/checkout', function () {

    User::verifyLogin(false);

    $address = new Address();

    $cart = Cart::getFromSession();

    if (isset($_GET['zipcode'])) {
        $_GET['zipcode'] = $cart->getdeszipcode();
    }

    if (isset($_GET['zipcode'])) {
        $address->loadFromCep($_GET['zipcode']);

        $cart->setdeszipcode($_GET['zipcode']);

        $cart->save();

        $cart->getCalculateTotal();
    }

    if (!$address->getdesaddress()) $address->setdesaddress('');
    if (!$address->getdescomplement()) $address->setdescomplement('');
    if (!$address->getdescity()) $address->setdescity('');
    if (!$address->getdesstate()) $address->setdesstate('');
    if (!$address->getdescountry()) $address->setdescountry('');
    if (!$address->getdeszipcode()) $address->setdeszipcode('');
    if (!$address->getdesdistrict()) $address->setdesdistrict('');


    $page = new Page();
    $page->setTpl("checkout", [
        "cart" => $cart->getValues(),
        "address" => $address->getValues(),
        "products" => $cart->getProducts(),
        "error" => Address::getMsgError()
    ]);

    exit;

});

$app->post('/checkout', function () {
    User::verifyLogin(false);

    if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
        Address::setMsgError("Informe o Cep.");
        header("Location: /checkout");
        exit();
    }
    if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
        Address::setMsgError("Informe o endereço.");
        header("Location: /checkout");
        exit();
    }
    if (!isset($_POST['descity']) || $_POST['descity'] === '') {
        Address::setMsgError("Informe o cidade.");
        header("Location: /checkout");
        exit();
    }
    if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
        Address::setMsgError("Informe o estado.");
        header("Location: /checkout");
        exit();
    }
    if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
        Address::setMsgError("Informe o pais.");
        header("Location: /checkout");
        exit();
    }


    $user = User::getFromSession();

    $address = new Address();

    $_POST["deszipcode"] = $_POST["zipcode"];
    $_POST["idperson"] = $user->getidperson();

    $address->setData($_POST);

    $address->save();
    //Pegar todos os valores relacionado ao carrinho que tem na sessão
    $cart = Cart::getFromSession();

    $totals = $cart->getCalculateTotal();

    $order = new Order();

    $order->setData(array(
        'idcart'=>$cart->getidcart(),
        'idaddress'=>$address->getidaddress(),
        'iduser'=>$user->getiduser(),
        'idstatus'=>OrderStatus::EM_ABERTO,
        'vltotal'=>$cart->getvltotal()
    ));

    $order->save();

    header("Location: /order/" . $order->getidorder());
    exit();
});

//carregar login
$app->get('/login', function () {
    $page = new Page();
    $page->setTpl("login", [
        "error" => User::getError(),
        "errorRegister" => User::getErrorRegister(),
        "registerValues" => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : [
            "name" => '',
            "email" => '',
            "phone" => ''
        ]
    ]);
    exit;

});

//receber senha e password
$app->post('/login', function () {

    try {

        $user = User::login($_POST['login'], $_POST['password']);

    } catch (Exception $e) {
        User::setError($e->getMessage());
    }

    header("Location: /checkout");
    exit;

});

$app->get('/logout', function () {
    User::logout();
    header("Location: /login");
    exit();

});


/*--------------------------*/
$app->post('/register', function () {
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
        'inadmin' => 0,
        'deslogin' => $_POST["email"],
        'desperson' => $_POST["name"],
        'desemail' => $_POST["email"],
        'despassword' => $_POST["password"],
        'nrphone' => $_POST["phone"]
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

/*------minha conta--------------*/

$app->get('/profile', function () {
    User::verifyLogin(false);

    $user = User::getFromSession();

    $page = new Page();

    $page->setTpl("profile", [
        "user" => $user->getValues(),
        "profileMsg" => User::getSuccess(),
        "profileError" => User::getError()
    ]);
});

$app->post('/profile', function () {
    User::verifyLogin(false);
    //verificar se a vari.. existe e se esta vazia
    if (!isset($_POST['desperson']) || $_POST['desperson'] == '') {
        User::setError("Preecha seu nome.");
        header("Location: /profile");
        exit;
    }

    if (!isset($_POST['desemail']) || $_POST['desemail'] == '') {
        User::setError("Preecha seu e-mail.");
        header("Location: /profile");
        exit;
    }

    $user = User::getFromSession();
    //verificar se o email já existi 
    if ($_POST['desemail'] !== $user->getdesemail()) {
        if (User::checkLoginExist($_POST['desemail']) === true) {
            User::setError("Este endereço de e-mail já esta cadastrado.");
            header("Location: /profile");
            exit;
        }
    }
    //substituir, para evitar invassão
    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];

    $user->setData($_POST);

    $user->update();
    User::setSuccess("Preecha seu nome.");

    User::setSuccess("Dados alterado com sucesso.");

    header("Location: /profile");
    exit();

});

/*-------------------order/pagamento-----------------------------------*/
$app->get('/order/:idorder', function ($idorder) {

    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);


    $page = new Page();

    $page->setTpl("payment", [
        'order' => $order->getValues()
    ]);
});

$app->get('/boleto/:idorder', function ($idorder){
    User::verifyLogin(false);
    $order = new Order();
    $order->get((int)$idorder);
    // DADOS DO BOLETO PARA O SEU CLIENTE
    $dias_de_prazo_para_pagamento = 10;
    $taxa_boleto = 5.00;
    $data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006";
    $valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
    $valor_cobrado = str_replace(".", "", $valor_cobrado);
    $valor_cobrado = str_replace(",", ".",$valor_cobrado);
    $valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
    $dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
    $dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
    $dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
    $dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
    $dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
    $dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula
    // DADOS DO SEU CLIENTE
    $dadosboleto["sacado"] = $order->getdesperson();
    $dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
    $dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();
    // INFORMACOES PARA O CLIENTE
    $dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
    $dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
    $dadosboleto["demonstrativo3"] = "";
    $dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
    $dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
    $dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
    $dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";
    // DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
    $dadosboleto["quantidade"] = "";
    $dadosboleto["valor_unitario"] = "";
    $dadosboleto["aceite"] = "";
    $dadosboleto["especie"] = "R$";
    $dadosboleto["especie_doc"] = "";
    // ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //
    // DADOS DA SUA CONTA - ITAÚ
    $dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
    $dadosboleto["conta"] = "48781";	// Num da conta, sem digito
    $dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta
    // DADOS PERSONALIZADOS - ITAÚ
    $dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157
    // SEUS DADOS
    $dadosboleto["identificacao"] = "Hcode Treinamentos";
    $dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
    $dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
    $dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
    $dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";
    // NÃO ALTERAR!
    $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
    require_once($path . "funcoes_itau.php");
    require_once($path . "layout_itau.php");

});

