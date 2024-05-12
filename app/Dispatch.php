<?php
namespace App;

use Src\classes\ClassRoutes;
	class Dispatch extends ClassRoutes{
		private $Method;
		private $Param=[];
		private $Obj;

	protected function getMethod(){return $this->Method;}
	public function setMethod($Method){$this->Method=$Method;}

	protected function getParam(){return $this->Param;}
	public function setParam($Param){$this->Param=$Param;}

	public function __construct(){
		self::addController();
	}

	#Metodo de Adicao do Controller
	private function addController()
	{
		$RotaController=$this->getRota();
		$NameS="App\\controller\\{$RotaController}";
		$this->Obj=new $NameS;

		if (isset($this->parseUrl()[1])) {
			self::addMethod();
		}
	}

	#Metodo de Adicao do Metodo do controller
	private function addMethod(){
		if(method_exists($this->Obj, $this->parseUrl()[1]))
		{
			$this->setMethod("{$this->parseUrl()[1]}");
			self::addParam();
			call_user_func_array([$this->Obj, $this->getMethod()],$this->getParam());
		}
	}
	#Metodo de Adicao do Parametro do controller
	private function addParam(){
	$ContArray=count($this->parseUrl());
	if($ContArray>2){
		foreach ($this->parseUrl() as $key => $value) {
			if($key>1){$this->setParam($this->Param+=[$key=>$value]);}
		}
	}
	}
}