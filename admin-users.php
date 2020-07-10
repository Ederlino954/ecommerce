<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;
// =======================================================================================
$app->get("/admin/users/:iduser/password", function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-password", [
		"user"=>$user->getValues(),
		"msgError"=>User::getError(),
		"msgSuccess"=>User::getSuccess()
	]);

});
// =======================================================================================
$app->post("/admin/users/:iduser/password", function($iduser){

	User::verifyLogin();

	if (!isset($_POST['despassword']) || $_POST['despassword']==='') {

		User::setError("Preencha a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm']==='') {

		User::setError("Preencha a confirmação da nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	if ($_POST['despassword'] !== $_POST['despassword-confirm']) {

		User::setError("Confirme corretamente as senhas.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	$user = new User();

	$user->get((int)$iduser);

	$user->setPassword(User::getPasswordHash($_POST['despassword']));

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /admin/users/$iduser/password");
	exit;

});
// =======================================================================================
$app->get("/admin/users", function() { //list

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : ""; // evita erro de variável vazia
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {

		$pagination = User::getPageSearch($search, $page);

	} else {

		$pagination = User::getPage($page); // traz as páginas sem busca 

	}

	$pages = []; // elementos adicionados dentro dele 

	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, [
			'href'=>'/admin/users?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);

	}

	$page = new PageAdmin();

	$page->setTpl("users", array(
		"users"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	));

});
// =======================================================================================
$app->get("/admin/users/create", function() { // create

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");

});
// =======================================================================================
$app->get("/admin/users/:iduser/delete", function($iduser) { // Delete // ficar em cima por causa da ordem de /:iduser/delete"

	User::verifyLogin();	

	$user = new User();

	$user->get((int)$iduser); // convertendo para numérico por certificação 

	$user->delete();

	header("Location: /admin/users");
	exit;

});
// =======================================================================================
$app->get("/admin/users/:iduser", function($iduser) { // atualizar / editar // ficar abaixo por causa da ordem de /:iduser"

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser); // convertendo para numérico por certificação 

	$page = new PageAdmin();

	$page->setTpl("users-update", array(  // users-update = view
		"user"=>$user->getValues()
	));

});
// =======================================================================================
$app->post("/admin/users/create", function() { // create

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0; /// verificação de Admin 1 = admin / 0= não

	$_POST['despassword'] = User::getPasswordHash($_POST['despassword']);

	$user->setData($_POST);

	$user->save(); // no model

	header("Location: /admin/users");
	exit;

});
// =======================================================================================
$app->post("/admin/users/:iduser", function($iduser) {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0; /// verificação de Admin 1 = admin / 0= não

	$user->get((int)$iduser); // convertendo para numérico por certificação 

	$user->setData($_POST);

	$user->update(); // no model

	header("Location: /admin/users");
	exit;

});

 ?>