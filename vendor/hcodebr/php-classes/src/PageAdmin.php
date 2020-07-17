<?php 

namespace Hcode;

class PageAdmin extends Page { // Herdando // publico e protegido dá para acessar 

	public function __construct($opts = array(), $tpl_dir = "/views/admin/") // alteração no caminho da pasta 
	{

		parent::__construct($opts, $tpl_dir); // da classe pai 

	}

}

 ?>