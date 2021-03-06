<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;  //  \Hcode chama na raiz
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model { // classe model e necessita de geters e seters 

	const SESSION = "User"; // nome da sessão
	const SECRET = "HcodePhp7_Secret";
	const SECRET_IV = "HcodePhp7_Secret_IV";
	const ERROR = "UserError";
	const ERROR_REGISTER = "UserErrorRegister";
	const SUCCESS = "UserSucesss";
	// =================================================================================================================
	public static function getFromSession() // carrinho de compras 
	{

		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {

			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;

	}
	// =================================================================================================================
	public static function checkLogin($inadmin = true) // valor padrão 
	{

		if (
			!isset($_SESSION[User::SESSION])  // se não foi definida
			||
			!$_SESSION[User::SESSION] // foi definida mas vazia
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;

		} else {

			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) { // Verificação se é administrador---------------------------------

				return true; // Logado com sessãode ADM ----------------------------------//
                                                                                          //
			} else if ($inadmin === false) {                                              //
                                                                                          // 
				return true; // Com sessão mas não é ADM----usuário logado----------------//
                                                                                          // 
			} else {                                                                      //
                                                                                          //
				return false; // Não logou sessão!---usuario não logado ------------------//

			}

		}

	}
	// =================================================================================================================
	public static function login($login, $password)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		)); 

		if (count($results) === 0)
		{
			throw new \Exception("Usuário inexistente ou senha inválida."); // \ para achar exception principal/ pois não criamos Exception
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true)
		{

			$user = new User(); // gera instancia da própria classe

			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data); /// setData pegando o array inteiro para o model onde está o seters e geters 

			$_SESSION[User::SESSION] = $user->getValues(); // pegando os valores 

			return $user;

		} else {
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}

	}
	// =================================================================================================================
	public static function verifyLogin($inadmin = true)
	{

		if (!User::checkLogin($inadmin)) {

			if ($inadmin) {
				header("Location: /admin/login"); // Caminho para o Administrador
			} else {
				header("Location: /login");
			}
			exit;

		}

	}
	// =================================================================================================================
	public static function logout() // saindo da sessão
	{

		$_SESSION[User::SESSION] = NULL;

	}
	// =================================================================================================================
	public static function listAll() // ler todos os dados da tabela
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

	}
	// =================================================================================================================
	public function save() // cadastrando usuários 
	{

		$sql = new Sql(); //ultilizando procedure CALL, por se mais rapido e necessita uma requisição.

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>utf8_decode($this->getdesperson()), // acentuação 
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()), // encryptando 
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin() // geters gerados pelo setData()
		));

		$this->setData($results[0]);

	}
	// =================================================================================================================
	public function get($iduser) // pegando dados para atualizar
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser"=>$iduser
		));

		$data = $results[0];

		$data['desperson'] = utf8_encode($data['desperson']);


		$this->setData($data);

	}
	// =================================================================================================================
	public function update()
	{

		$sql = new Sql(); //ultilizando procedure CALL, por se mais rapido e necessita uma requisição.

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>utf8_decode($this->getdesperson()), // decode para resolver acentuação
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()), // encryptando
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);		

	}
	// =================================================================================================================
	public function delete()
	{

		$sql = new Sql(); //ultilizando procedure CALL, por se mais rapido e necessita uma requisição.

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));

	}
	// =================================================================================================================
	public static function getForgot($email, $inadmin = true) // envio de email para alteração
	{

		$sql = new Sql();
		// verificando se o email está no  banco de dados 
		$results = $sql->select(" 
			SELECT *
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(
			":email"=>$email
		));

		if (count($results) === 0) // se não retornar email
		{

			throw new \Exception("Não foi possível recuperar a senha.");

		}
		else
		{

			$data = $results[0]; // usando a PROCEDURE

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data['iduser'],
				":desip"=>$_SERVER['REMOTE_ADDR'] // pega o ip do usuário 
			));

			if (count($results2) === 0)
			{

				throw new \Exception("Não foi possível recuperar a senha.");

			}
			else
			{

				$dataRecovery = $results2[0];

				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV)); // encryptando

				$code = base64_encode($code);

				if ($inadmin === true) { // preservando a rota do ADM

					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code"; // link de envio para o email

				} else {

					$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code"; // link de envio para o email
					
				}				

				$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(// "forgot" dentro de views/email/
					"name"=>$data['desperson'], // variavel dentro de forgot no email
					"link"=>$link // variavel dentro de forgot no email
				));				

				$mailer->send();

				return $link;

			}

		}

	}
	// =================================================================================================================
	public static function validForgotDecrypt($code) // na classe ssmtp do phpmailaer desabilitei um if na linha 368 para teste
	{

		$code = base64_decode($code); /// decodificando

		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

		$sql = new Sql(); // recuperando a senha com uma hora de duração 

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{

			return $results[0]; 

		}

	}
	// =================================================================================================================
	public static function setFogotUsed($idrecovery) // Atualizando o idrecovery com o tempo estipulado // recuperação de senha
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));

	}
	// =================================================================================================================
	public function setPassword($password) // recebendo a senha nova 
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));

	}
	// =================================================================================================================
	public static function setError($msg)
	{

		$_SESSION[User::ERROR] = $msg;

	}
	// =================================================================================================================
	public static function getError()
	{

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError(); // limpando erro 

		return $msg;

	}
	// =================================================================================================================
	public static function clearError()
	{

		$_SESSION[User::ERROR] = NULL;

	}
	// =================================================================================================================
	public static function setSuccess($msg)
	{

		$_SESSION[User::SUCCESS] = $msg;

	}
	// =================================================================================================================
	public static function getSuccess()
	{

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		User::clearSuccess();

		return $msg;

	}
	// =================================================================================================================
	public static function clearSuccess()
	{

		$_SESSION[User::SUCCESS] = NULL;

	}
	// =================================================================================================================
	public static function setErrorRegister($msg)
	{

		$_SESSION[User::ERROR_REGISTER] = $msg;

	}
	// =================================================================================================================
	public static function getErrorRegister()
	{

		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;

	}
	// =================================================================================================================
	public static function clearErrorRegister()
	{

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}
	// =================================================================================================================
	public static function checkLoginExist($login)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin'=>$login
		]);

		return (count($results) > 0);

	}
	// =================================================================================================================
	public static function getPasswordHash($password) // encryptando 
	{

		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);

	}
	// =================================================================================================================
	public function getOrders()
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.iduser = :iduser
		", [
			':iduser'=>$this->getiduser()
		]);

		return $results;

	}
	// =================================================================================================================
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			ORDER BY b.desperson
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}
	// =================================================================================================================
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson)
			WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
			ORDER BY b.desperson
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%' /// parâmentro de busca
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