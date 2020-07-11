<?php 

namespace Hcode;

use Rain\Tpl;

class Page {

	private $tpl;
	private $options = [];
	private $defaults = [ // padrão
		"header"=>true,
		"footer"=>true,
		"data"=>[]
	];
	// =============================================================================================================
	public function __construct($opts = array(), $tpl_dir = "/views/"){ // primeiro a ser executado
		
		$this->options = array_merge($this->defaults, $opts); // mesclando os 2 arrays // opt tem prioridade

		$config = array( // exemplos simples do template rain
			"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir, // diretório do root
			"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
			"debug"         => false
	    );

		Tpl::configure( $config );

		$this->tpl = new Tpl;

		$this->setData($this->options["data"]);

		if ($this->options["header"] === true) $this->tpl->draw("header");

	}
	// =============================================================================================================
	private function setData($data = array()) // função para otimizar o código por causa da repetição
	{

		foreach ($data as $key => $value) {
			$this->tpl->assign($key, $value);
		}

	}
	// =============================================================================================================
	public function setTpl($name, $data = array(), $returnHTML = false) // conteúdo da página 
	{

		$this->setData($data);

		return $this->tpl->draw($name, $returnHTML);

	}
	// =============================================================================================================
	public function __destruct(){ // último a ser executado

		if ($this->options["footer"] === true) $this->tpl->draw("footer");

	}

}

 ?>