<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Category extends Model {
	//=======================================================================================================
	public static function listAll() // static para ser chado só internamente
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory"); // listando todas as categorias ordenando pelo nome

	}
	//=======================================================================================================
	public function save() 
	{

		$sql = new Sql(); // usando PROCEDURE

		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
			":idcategory"=>$this->getidcategory(),
			":descategory"=>$this->getdescategory()
		));

		$this->setData($results[0]); // método seters e geters 

		Category::updateFile(); // atualizando o arquivo categories-menu.html // lista dinâmica --------------------------------------------------->>>>>

	}
	//=======================================================================================================
	public function get($idcategory) // pegando as categorias 
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory'=>$idcategory
		]);

		$this->setData($results[0]);

	}
	//=======================================================================================================
	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory'=>$this->getidcategory() // sem a variação por isso usar $this
		]);

		Category::updateFile(); // atualizando o arquivo categories-menu.html // lista dinâmica --------------------------------------------------->>>>>

	}
	//=======================================================================================================
	public static function updateFile() // atualizando o arquivo categories-menu.html // lista dinâmica --------------------------------------------------->>>>>
	{

		$categories = Category::listAll();

		$html = [];
		// criando as listas de categorias no footer 
		foreach ($categories as $row) {
			array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
		}
		// salvando o arquivo!
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));// implode transformando o array em string

	}
	//=======================================================================================================
	public function getProducts($related = true) // trazendo todos os produtos // padrão os que estão relacionados com a categoria 
	{

		$sql = new Sql();

		if ($related === true) { // os que estão relacionados IN()----------------------------------------------------------------------------------

			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct IN( 
					SELECT a.idproduct
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				':idcategory'=>$this->getidcategory() // id que está no próprio objeto instanciado!
			]);

		} else { // os que não estão relacionandos NOT IN()------------------------------------------------------------------------------

			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct NOT IN(
					SELECT a.idproduct
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				':idcategory'=>$this->getidcategory() // id que está no próprio objeto instanciado!
			]);

		}

	}
	//=======================================================================================================
	public function getProductsPage($page = 1, $itemsPerPage = 8) // paginação!
	{

		$start = ($page - 1) * $itemsPerPage; /// para iniciar no índice 0

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itemsPerPage;
		", [
			':idcategory'=>$this->getidcategory()
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>Product::checkList($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) /// ceil arredonda para cima
		];

	}
	//=======================================================================================================
	public function addProduct(Product $product) // Product incial maiúscula por ser uma classe 
	{

		$sql = new Sql();

		$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct)", [
			':idcategory'=>$this->getidcategory(),
			':idproduct'=>$product->getidproduct()
		]);

	}
	//=======================================================================================================
	public function removeProduct(Product $product) // Product incial maiúscula por ser uma classe 
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
			':idcategory'=>$this->getidcategory(),
			':idproduct'=>$product->getidproduct()
		]);

	}
	//=======================================================================================================		
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}
	//=======================================================================================================
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			WHERE descategory LIKE :search
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%'
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}

}

 ?>