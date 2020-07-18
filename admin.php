<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;
// ================================================================================================================
$app->get('/admin', function() {
    
	User::verifyLogin(); // na classe User

	$page = new PageAdmin();

	$page->setTpl("index");

});
// ================================================================================================================
$app->get('/admin/login', function() { // login ADM // carregamento pag

	$page = new PageAdmin([
		"header"=>false, // desabilitando o padrão
		"footer"=>false  // desabilitando o padrão
	]);

	$page->setTpl("login");

});
// ================================================================================================================
$app->post('/admin/login', function() { // rota do formulário de login envio 

	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;

});
// ================================================================================================================
$app->get('/admin/logout', function() { // rota no admin/ header

	User::logout();

	header("Location: /admin/login");
	exit;

});
// ================================================================================================================
$app->get("/admin/forgot", function() { // caminho para alteração de senha // template

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");	

});
// ================================================================================================================
$app->post("/admin/forgot", function(){ // enviando email de recuperação!

	$user = User::getForgot($_POST["email"]); /// no model User

	header("Location: /admin/forgot/sent");
	exit;

});
// ================================================================================================================
$app->get("/admin/forgot/sent", function(){ // confirmando envio 
 // finalizando envio
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");	

});
// ================================================================================================================
$app->get("/admin/forgot/reset", function(){ // tela de recuperação de senha 

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset", array( // recebendo os dados par aalteração!
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});
// ================================================================================================================
$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);	 // verificando o código 

	User::setFogotUsed($forgot["idrecovery"]); // salvando o idrecovery com o tempo estipulado

	$user = new User();

	$user->get((int)$forgot["iduser"]); /// pegando o id

	$password = User::getPasswordHash($_POST["password"]); // criptografando!

	$user->setPassword($password);  // recebendo a senha nova 

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success"); // template de confirmação 

});

 ?>