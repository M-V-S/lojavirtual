<?php


use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get('/admin', function () {

    User::verifyLogin();
    $page = new PageAdmin();
    $page->setTpl("index");
});

//solicitar pagina login
$app->get('/admin/login', function () {
    $pageAdmin = new PageAdmin($opts = array(
        "header" => false,
        "folter" => false
    ));

    $pageAdmin->setTpl("login");

});
//Receber method post login/ Fazer validação
$app->post('/admin/login', function () {
    User::login($_POST['login'], $_POST['password']);

    header("Location: /admin");
    exit;
});
//Deslogar sair, acabar com a sessão
$app->get('/admin/logout', function () {
    User::logout();
    header("Location: /admin/login");
    exit();
});

/*------------------ESQUECEU SUA SENHA-----------------------*/
//pedir pagina recuperação da senha
$app->get('/admin/forgot', function () {

    $pageAdmin = new PageAdmin($opts = array(
        "header" => false,
        "folter" => false
    ));

    $pageAdmin->setTpl("forgot");

});

//receber email do forgot.html
$app->post('/admin/forgot', function () {

    User::getForgot($_POST["email"]);

    header("Location: /admin/forgot/sent");
    exit();
});

//pagina para mostrar que o email foi enviado com sucesso
$app->get('/admin/forgot/sent', function () {

    $pageAdmin = new PageAdmin($opts = array(
        "header" => false,
        "folter" => false
    ));

    $pageAdmin->setTpl("forgot-sent");
    exit();
});

//apos o usuário clicar no link no email dele, ele é redirecionado para a rota asseguir 
$app->get('/admin/forgot/reset', function () {


    $user = User::validForgotDecrypt($_GET["code"]);

    $pageAdmin = new PageAdmin($opts = array(
        "header" => false,
        "folter" => false
    ));
    //carregar pagina para colocar nova senha
    $pageAdmin->setTpl("forgot-reset", array(
        "name" => $user['desperson'],
        "code" => $_GET["code"]
    ));
    exit();
});


$app->post('/admin/forgot/reset', function () {


    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUser($forgot["idrecovery"]);

    $user = new User();

    $user->get((int)$forgot["iduser"]);

    $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
        "const" => 12]);

    $user->setPassword($password);

    $pageAdmin = new PageAdmin($opts = array(
        "header" => false,
        "folter" => false
    ));

    $pageAdmin->setTpl("forgot-reset-success");
    exit();
});

?>