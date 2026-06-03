<?php
namespace App;

use Src\classes\ClassRoutes;
use Src\classes\ClassRateLimiter;
use Src\classes\ClassCommissionGuard;
use Src\classes\ClassLimitedAccountGuard;
use Src\classes\ClassTrustBadgeGuard;
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

		ClassCommissionGuard::enforce($RotaController, $this->parseUrl());
		ClassLimitedAccountGuard::enforce($RotaController, $this->parseUrl());
		ClassTrustBadgeGuard::enforce($RotaController, $this->parseUrl());

		// Enforce a per-route rate limit for all methods (GET/POST/etc.).
		$routeKey = (string) ($this->parseUrl()[0] ?? '');
		if ($routeKey !== '') {
			ClassRateLimiter::enforceScopeAllMethods($routeKey, 'rate_limit_route_max', 'rate_limit_route_window_seconds', 200, 60);
		}

		if (isset($this->parseUrl()[1])) {
			self::addMethod();
		} else {
			ClassRateLimiter::enforceGlobalPost();
			// Se não houver método especificado, tenta chamar o método com o mesmo nome da rota
			$defaultMethod = $this->parseUrl()[0];
			if (!empty($defaultMethod) && method_exists($this->Obj, $defaultMethod)) {
				$this->Obj->{$defaultMethod}();
			}
		}
	}

	#Metodo de Adicao do Metodo do controller
	private function addMethod(){
		$methodName = $this->parseUrl()[1];
		$resolvedMethod = $methodName;

		// Accept snake_case URLs for camelCase controller methods, e.g. moderate_users -> moderateUsers.
		if (!method_exists($this->Obj, $resolvedMethod) && strpos($methodName, '_') !== false) {
			$resolvedMethod = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $methodName))));
		}
		
		// Se o método não existe e o segundo parâmetro é numérico, tenta chamar show()
		if (!method_exists($this->Obj, $resolvedMethod) && is_numeric($methodName) && method_exists($this->Obj, 'show')) {
			call_user_func_array([$this->Obj, 'show'], [$methodName]);
			return;
		}
		
		if(method_exists($this->Obj, $resolvedMethod))
		{
			ClassRateLimiter::enforceGlobalPost();
			$this->setMethod("{$resolvedMethod}");
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