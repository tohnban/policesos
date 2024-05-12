<?php
namespace Src\classes;

class ClassBreadcrumb{

	use \Src\traits\TraitUrlParser;

	#Criando os Breadcrumb do site
	public function  addBreadcrumb()
	{
		$Contador=count($this->parseUrl());
		$ArrayLink[0]='';
echo "<a href=".DIRPAGE.">home</a> >";
	 for ($i=0; $i < $Contador; $i++) { 
	 	$ArrayLink[0].=$this->parseUrl()[$i].'/';
		echo "<a href=".DIRPAGE.$ArrayLink[0].">".$this->parseUrl()[$i]."</a>";

		if ($i<$Contador-1) {
			echo ">";
		}
	 }
	}
}