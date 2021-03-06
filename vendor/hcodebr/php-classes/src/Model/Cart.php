<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model {

	const SESSION = "Cart";
	const SESSION_ERROR = "CartError";
	// ==================================================================================================================
	public static function getFromSession() 
	{

		$cart = new Cart();

		if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) { // se id carrinho está na sessão e ele é maior que 0

			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']); // carrinho inserido no banco e está na sesssão

		} else {

			$cart->getFromSessionID();

			if (!(int)$cart->getidcart() > 0) { // não conseguiu criar carrinho

				$data = [
					'dessessionid'=>session_id() // criando carrinho novo
				];

				if (User::checkLogin(false)) { // se verdadeiro está logado!

					$user = User::getFromSession();
					
					$data['iduser'] = $user->getiduser();// Passando o id do usuário! 

				}

				$cart->setData($data);

				$cart->save(); // salvando na BD

				$cart->setToSession(); // colocando  na sessão 

			}

		}

		return $cart;

	}
	// ==================================================================================================================
	public function setToSession() // Não estatico pois está usando $this
	{

		$_SESSION[Cart::SESSION] = $this->getValues(); // colocou o carrinho na sessão! 

	}
	// ==================================================================================================================
	public function getFromSessionID()
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			':dessessionid'=>session_id()
		]);

		if (count($results) > 0) { // para evitar erro se vier vazio

			$this->setData($results[0]);

		}

	}	
	// ==================================================================================================================
	public function get(int $idcart)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
			':idcart'=>$idcart
		]);

		if (count($results) > 0) {

			$this->setData($results[0]);

		}

	}
	// ==================================================================================================================
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
			':idcart'=>$this->getidcart(),
			':dessessionid'=>$this->getdessessionid(),
			':iduser'=>$this->getiduser(),
			':deszipcode'=>$this->getdeszipcode(),
			':vlfreight'=>$this->getvlfreight(),
			':nrdays'=>$this->getnrdays()
		]);

		$this->setData($results[0]);

	}
	// ==================================================================================================================
	public function addProduct(Product $product) // adicioanndo um produto ao carrinho
	{

		$sql = new Sql();

		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
			':idcart'=>$this->getidcart(),
			':idproduct'=>$product->getidproduct()
		]);

		$this->getCalculateTotal();

	}
	// ==================================================================================================================
	public function removeProduct(Product $product, $all = false) // removendo produto do carrinho // remoção de 1 ou de todos 
	{

		$sql = new Sql();

		if ($all) { // all verdadeiro remove tudo! 

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);

		} else { // remove a unidade 

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [ // nulo para não setar o que ja foi setado
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);

		}

		$this->getCalculateTotal();

	}
	// ==================================================================================================================
	public function getProducts() // pegando todos os produtos adicionados ao carrinho 
	{

		$sql = new Sql();

		$rows = $sql->select("
			SELECT b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
			ORDER BY b.desproduct
		", [
			':idcart'=>$this->getidcart()
		]);

		return Product::checkList($rows);

	}
	// ==================================================================================================================
	public function getProductsTotals() /// atualiza frete e os preços
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products a
			INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
			WHERE b.idcart = :idcart AND dtremoved IS NULL;
		", [
			':idcart'=>$this->getidcart()
		]);

		if (count($results) > 0) {
			return $results[0];
		} else {
			return [];
		}

	}
	// ==================================================================================================================
	public function setFreight($nrzipcode) // Rota cálculo do frete
	{

		$nrzipcode = str_replace('-', '', $nrzipcode); // trocando o hífem pelo vazio sem espaço

		$totals = $this->getProductsTotals(); // atualiza frete e os preços

		if ($totals['nrqtd'] > 0) {

			if ($totals['vlheight'] < 2) $totals['vlheight'] = 2; // limites de dimensão dos produtos 
			if ($totals['vllength'] < 16) $totals['vllength'] = 16; // limites de dimensão dos produtos 

			$qs = http_build_query([ // array
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'09853120',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);

			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);// função que lê XML

			$result = $xml->Servicos->cServico;

			if ($result->MsgErro != '') { // Mensagem de erro!

				Cart::setMsgError($result->MsgErro);

			} else {

				Cart::clearMsgError();

			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save(); // Salva no banco

			return $result;

		} else {



		}

	}
	// ==================================================================================================================
	public static function formatValueToDecimal($value):float // formatando valor 
	{

		$value = str_replace('.', '', $value);
		return str_replace(',', '.', $value);

	}
	// ==================================================================================================================
	public static function setMsgError($msg)
	{

		$_SESSION[Cart::SESSION_ERROR] = $msg;

	}
	// ==================================================================================================================
	public static function getMsgError()
	{

		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : ""; // definido retorna elel mesmo se não vazio

		Cart::clearMsgError();

		return $msg;

	}
	// ==================================================================================================================
	public static function clearMsgError() // limpando 
	{

		$_SESSION[Cart::SESSION_ERROR] = NULL;

	}
	// ==================================================================================================================
	public function updateFreight() // Atualizando frete 
	{

		if ($this->getdeszipcode() != '') {

			$this->setFreight($this->getdeszipcode());

		}

	}
	// ==================================================================================================================
	public function getValues()
	{

		$this->getCalculateTotal();

		return parent::getValues();

	}
	// ==================================================================================================================
	public function getCalculateTotal() // atualiza frete e os preços
	{

		$this->updateFreight(); // atualizando o frete 

		$totals = $this->getProductsTotals(); // valores totais do carrinho 

		$this->setvlsubtotal($totals['vlprice']); // soma de todos os produtos dentro do carrinho 
		$this->setvltotal($totals['vlprice'] + (float)$this->getvlfreight()); // soma de todos os produtos dentro do carrinho + valor frete 

	}

}

 ?>