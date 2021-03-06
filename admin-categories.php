<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
// =====================================================================
$app->get("/admin/categories", function(){ // template de categorias 

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : ""; // paginação// possível criar classe e chamar os métodos 
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {

		$pagination = Category::getPageSearch($search, $page);

	} else {

		$pagination = Category::getPage($page);

	}

	$pages = [];

	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, [
			'href'=>'/admin/categories?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);

	}

	$page = new PageAdmin();

	$page->setTpl("categories", [
		"categories"=>$pagination['data'], /// página junto com os arrays 
		"search"=>$search,
		"pages"=>$pages
	]);	


});
// =====================================================================
$app->get("/admin/categories/create", function(){ // rota pra criação 

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");	// para a pagina // Template

});
// =====================================================================
$app->post("/admin/categories/create", function(){ // salvando a criação 

	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST); // no Model pegando

	$category->save(); // no model salvando

	header('Location: /admin/categories');
	exit;

});
// =====================================================================
$app->get("/admin/categories/:idcategory/delete", function($idcategory){

	User::verifyLogin(); // verificação de login 

	$category = new Category();

	$category->get((int)$idcategory); // carregando id

	$category->delete(); // no model

	header('Location: /admin/categories');
	exit;

});
// =====================================================================
$app->get("/admin/categories/:idcategory", function($idcategory){ // rota que mostar o template

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-update", [ // Template // 
		'category'=>$category->getValues() // category sendo passado como um array
	]);	

});
// =====================================================================
$app->post("/admin/categories/:idcategory", function($idcategory){ // Salvando categoria // criar çógica para não salvar a mesma categoria 

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$category->setData($_POST);

	$category->save();	

	header('Location: /admin/categories');
	exit;

});
// =====================================================================
$app->get("/admin/categories/:idcategory/products", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-products", [ // template
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(), // relacionandos com a categoria // padrão 
		'productsNotRelated'=>$category->getProducts(false) // não relacionados com a categoria 
	]);

});
// =====================================================================
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){ // adicionando à categoria com base no :idcategory

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});
// =====================================================================
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){ // removendo da categoria com base no :idcategory

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

 ?>