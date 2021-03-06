<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model {
	//====================================================================================================================================================
	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");

	}
	//====================================================================================================================================================
	public static function checkList($list) // checando a lista de produtos 
	{

		foreach ($list as &$row) { // & manipular a mesma variável na memória 
			
			$p = new Product();
			$p->setData($row);
			$row = $p->getValues();

		}

		return $list; // os produtos já formatado 

	}
	//====================================================================================================================================================
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
			":idproduct"=>$this->getidproduct(),
			":desproduct"=>$this->getdesproduct(),
			":vlprice"=>$this->getvlprice(),
			":vlwidth"=>$this->getvlwidth(),
			":vlheight"=>$this->getvlheight(),
			":vllength"=>$this->getvllength(),
			":vlweight"=>$this->getvlweight(),
			":desurl"=>$this->getdesurl()
		));

		$this->setData($results[0]);

	}
	//====================================================================================================================================================
	public function get($idproduct)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct'=>$idproduct
		]);

		$this->setData($results[0]);

	}
	//====================================================================================================================================================
	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct'=>$this->getidproduct()
		]);

	}
	//====================================================================================================================================================
	public function checkPhoto() // verificando se existe ou não foto 
	{

		if (file_exists( // Caminho das imagens // sistema operacioanal
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg"
			)) {

			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg"; 

		} else {

			$url = "/res/site/img/product.jpg"; // foto padrão retornada // imagem base padrão!

		}

		return $this->setdesphoto($url); // purl existindo evitando erro 

	}
	//====================================================================================================================================================
	public function getValues() // mesma função da classe pai
	{

		$this->checkPhoto(); // verificando se tem ou não foto o produto!

		$values = parent::getValues(); // no model, classa pai 

		return $values;

	}
	//====================================================================================================================================================
	public function setPhoto($file) // upload do arquivo
	{

		$extension = explode('.', $file['name']); // explodindo arquivo com base no ponto 
		$extension = end($extension); // pegando a ultima porte do arquivo, extensão

		switch ($extension) {

			case "jpg":
			case "jpeg":
			$image = imagecreatefromjpeg($file["tmp_name"]);
			break;

			case "gif":
			$image = imagecreatefromgif($file["tmp_name"]);
			break;

			case "png":
			$image = imagecreatefrompng($file["tmp_name"]);
			break;

		}

		$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg";

		imagejpeg($image, $dist); /// image, caminho

		imagedestroy($image);

		$this->checkPhoto();

	}
	//====================================================================================================================================================
	public function getFromURL($desurl)  // url do produto
	{

		$sql = new Sql();

		$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [ /// LIMIT ! para retornar somente uma linha 
			':desurl'=>$desurl
		]);

		$this->setData($rows[0]);

	}
	//====================================================================================================================================================
	public function getCategories() // Categorias em detalhes 
	{

		$sql = new Sql();

		return $sql->select("
			SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct
		", [

			':idproduct'=>$this->getidproduct()
		]);

	}
	//====================================================================================================================================================
	public static function getPage($page = 1, $itemsPerPage = 10)  // paginação
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products 
			ORDER BY desproduct
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}
	//====================================================================================================================================================
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10) // Busca
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products 
			WHERE desproduct LIKE :search
			ORDER BY desproduct
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