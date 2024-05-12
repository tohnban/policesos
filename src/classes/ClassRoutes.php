<?php
namespace Src\classes;

use Src\traits\TraitUrlParser;
class ClassRoutes{

use TraitUrlParser;

	private $Rotas;

	public function getRota()
	{
		$url=$this->parseUrl();
		$I=$url[0];

		$this->Rotas= array(
			""=>"ControllerApi",
			"api"=>"ControllerApi"
		);

		if (array_key_exists($I, $this->Rotas)) {
			if(file_exists(DIRREQ."app/controller/{$this->Rotas[$I]}.php"))
			{
				return $this->Rotas[$I];
			}
			else{return "ControllerApi";}
		}
		else
		{
			return "Controller404";
		}
	} 
}
?>