<?php 

namespace Hcode; // namesapce principal 

class Model { // criando os geters e seters -----------------------------------------------------------------------------------------------------------------------------

	private $values = []; // contem todos os valores dos campos dos objetos 
	// ===============================================================================================
	public function __call($name, $args) // nome do método e os argumentos 
	{

		$method = substr($name, 0, 3); // marcando as 3 posições inicais para trazer // set ou get
		$fieldName = substr($name, 3, strlen($name)); // marcando a partir da 3 pos até o final // nome restante

		switch ($method)
		{

			case "get": // retorna informação
				return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL; // verificação por causa da PROCEDURE que gera o id, evitar erro
			break;

			case "set": // retorna a informação 
				$this->values[$fieldName] = $args[0];
			break;

		}

	}
	// ===============================================================================================
	public function setData($data = array()) // gerando o array base dos envios // Fazendo os sets dos valores 
	{
		// separando os dados do BD
		foreach ($data as $key => $value) {
			
			$this->{"set".$key}($value); // entre chave sendo dinâmico

		}

	}
    // ===============================================================================================
	public function getValues() // rRtorna o atributo values // não pega o atributo diretamente por não ser uma boa prática 
	{

		return $this->values;
		
	}

}

 ?>