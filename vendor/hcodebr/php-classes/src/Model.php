<?php 

namespace Hcode;

class Model { // criando os geters e seters 

	private $values = []; // contem todos os valores dos campos dos objetos 
	// ===============================================================================================
	public function __call($name, $args)
	{

		$method = substr($name, 0, 3); // marcando as 3 posições inicais para trazer 
		$fieldName = substr($name, 3, strlen($name)); // marcando a partir da 3 pos até o final 

		switch ($method)
		{

			case "get":
				return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL; // verificação por causa da PROCEDURE que gera o id, evitar erro
			break;

			case "set":
				$this->values[$fieldName] = $args[0];
			break;

		}

	}
	// ===============================================================================================
	public function setData($data = array())
	{
		// separando os dados do BD
		foreach ($data as $key => $value) {
			
			$this->{"set".$key}($value); // entre chave sendo dinâmico

		}

	}
    // ===============================================================================================
	public function getValues()
	{

		return $this->values;
		
	}

}

 ?>