<?php


use \Hcode\PageAdmin;
use \Hcode\Model\User;

//carregar todos os usuários
$app->get('/admin/users', function () {
    User::verifyLogin();

    $search = (isset($_GET['search']))? $_GET['search'] : '';
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    
    if ($search != '') {
         $pagination = User::getPageSearch($search, $page);
    }else{

        $pagination = User::getPage($page);
    }

    

    $pages = [];

    for ($i=0; $i < $pagination['pages']; $i++) { 
        array_push($pages, [
            'href'=>'/admin/users?'.http_build_query([
                'page'=>$i+1,
                'search'=>$search
            ]),
            'text'=>$i+1
        ]);
    }

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("users", array(
        "users" => $pagination['data'],
        "search" => $search,
        'pages' => $pages
    ));
    exit();
});

//Carrega formulario para criar user
$app->get('/admin/users/create', function () {
    User::verifyLogin();

    $pageAdmin = new PageAdmin();

    $pageAdmin->setTpl("users-create");
    exit();
});

//excluir usuário
$app->get('/admin/users/:iduser/delete', function ($iduser) {
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->delete();

    header("Location: /admin/users");
    exit();

});


//Carrega formulario para editar
$app->get('/admin/users/:iduser', function ($iduser) {
    User::verifyLogin();

    $pageAdmin = new PageAdmin();

    $user = new User();

    $user->get((int)$iduser);

    $pageAdmin->setTpl("users-update", array(
        "user" => $user->getValues()
    ));
    exit();
});

//Criar usuário
$app->post('/admin/users/create', function () {
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
        "cost" => 12
    ]);

    $user->setData($_POST);

    $user->save();

    header("Location: /admin/users");

    exit();
});

//Editar usuário
$app->post('/admin/users/:iduser', function ($iduser) {
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit();

});
?>