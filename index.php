<?php 
session_start();
require_once("vendor/autoload.php");

$app = new \Slim\Slim();

use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;



$app->config('debug', true);

//solicitar pagina principal do site
$app->get('/', function() {

    $page = new Page();

    $page->setTpl("index");
});

//solicitar pagina  inicial do administrador
$app->get('/admin', function(){
	User::verifyLogin();
	$pageAdmin = new PageAdmin();
	$pageAdmin->setTpl("index");

});
/*------------------LOGIN-----------------------*/
//solicitar pagina login
$app->get('/admin/login', function(){
	$pageAdmin = new PageAdmin($opts = array(
		"header" => false,
		"folter" => false
	));

	$pageAdmin->setTpl("login");

});
//Receber method post login/ Fazer validação
$app->post('/admin/login', function(){
	User::login($_POST['login'], $_POST['password']);

	header("Location: /admin");
	exit;
});
//Deslogar sair, acabar com a sessão
$app->get('/admin/logout', function(){
	User::logout();
	header("Location: /admin/login");
	exit();
});

/*------------------CRUD USER-----------------------*/
//carregar todos os usuários
$app->get('/admin/users', function(){
	User::verifyLogin();

	$users = User::listAll();

	$pageAdmin = new PageAdmin();

	$pageAdmin->setTpl("users",array(
		"users"=>$users
	));
	exit();
});

//Carrega formulario para criar user
$app->get('/admin/users/create', function(){
	User::verifyLogin();

	$pageAdmin = new PageAdmin();

	$pageAdmin->setTpl("users-create");
	exit();
});

//excluir usuário
$app->get('/admin/users/:iduser/delete', function($iduser){
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");
	exit();

});


//Carrega formulario para editar
$app->get('/admin/users/:iduser', function($iduser){
	User::verifyLogin();

	$pageAdmin = new PageAdmin();

	$user = new User();

	$user->get((int)$iduser);

	$pageAdmin->setTpl("users-update", array(
		"user"=>$user->getValues()
	));
	exit();
});

//Criar usuário
$app->post('/admin/users/create', function(){
	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
 		"cost"=>12
 	]);

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");

	exit();
});

//Editar usuário
$app->post('/admin/users/:iduser', function($iduser){
	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");
	exit();

});
/*------------------FIM CRUD-----------------------*/

/*------------------ESQUECEU SUA SENHA-----------------------*/
//pedir pagina recuperação da senha
$app->get('/admin/forgot', function(){
	
	$pageAdmin = new PageAdmin($opts = array(
		"header" => false,
		"folter" => false
	));

	$pageAdmin->setTpl("forgot");

});

//receber email do forgot.html
$app->post('/admin/forgot', function(){
	User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit();
});

//pagina para mostrar que o email foi enviado com sucesso
$app->get('/admin/forgot/sent', function(){
	$pageAdmin = new PageAdmin($opts = array(
		"header" => false,
		"folter" => false
	));

	$pageAdmin->setTpl("forgot-sent");
	exit();
});

//apos o usuário clicar no link no email dele, ele é redirecionado para a rota asseguir 
$app->get('/admin/forgot/reset', function(){


	$user = User::validForgotDecrypt($_GET["code"]);

	$pageAdmin = new PageAdmin($opts = array(
		"header" => false,
		"folter" => false
	));
	//carregar pagina para colocar nova senha
	$pageAdmin->setTpl("forgot-reset", array(
		"name"=>$user['desperson'],
		"code"=>$_GET["code"]
	));
	exit();
});


$app->post('/admin/forgot/reset', function(){


	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setForgotUser($forgot["idrecovery"]);	

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT,[
		"const"=>12]);

	$user->setPassword($password);

	$pageAdmin = new PageAdmin($opts = array(
		"header" => false,
		"folter" => false
	));

	$pageAdmin->setTpl("forgot-reset-success");
	exit();	
	});
	/*------------------END CRUD USER-----------------------*/

	/*------------------CRUD CATEGORIAS-----------------------*/
	$app->get("/admin/categories", function(){
		User::verifyLogin();
		$pageAdmin = new PageAdmin();
		
		$categories = Category::listAll();

		$pageAdmin->setTpl("categories", array(
			"categories"=>$categories
		));
	});


$app->run();

 ?>