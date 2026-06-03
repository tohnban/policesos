<?php
namespace App;

use App\controller\Controller404;
use Src\classes\ClassRoutes;
use Src\classes\ClassRateLimiter;
use Src\classes\ClassCommissionGuard;
use Src\classes\ClassLimitedAccountGuard;
use Src\classes\ClassTrustBadgeGuard;
use Src\classes\ResolvedRoute;
use Src\classes\RouteMiddlewareRunner;
use Src\classes\RouteRegistry;

class Dispatch extends ClassRoutes {
	private $Method;
	private $Param = [];
	private $Obj;

	protected function getMethod() { return $this->Method; }
	public function setMethod($Method) { $this->Method = $Method; }

	protected function getParam() { return $this->Param; }
	public function setParam($Param) { $this->Param = $Param; }

	public function __construct() {
		$url = $this->parseUrl();
		$httpMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

		$resolved = RouteRegistry::match($httpMethod, $url);
		if ($resolved !== null) {
			$this->dispatchResolved($resolved, $url);
			return;
		}

		$this->dispatchLegacy($url);
	}

	private function dispatchResolved(ResolvedRoute $resolved, array $url): void {
		$this->enforceGuards($resolved->controllerShortName, $url);

		$routeKey = $resolved->routeKey;
		if ($routeKey !== '') {
			ClassRateLimiter::enforceScopeAllMethods(
				$routeKey,
				'rate_limit_route_max',
				'rate_limit_route_window_seconds',
				200,
				60
			);
		}

		RouteMiddlewareRunner::run($resolved->middleware, $resolved);

		if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
			ClassRateLimiter::enforceGlobalPost();
		}

		$controller = new $resolved->controllerClass();
		$action = $resolved->action;

		if ($action === '' || !method_exists($controller, $action)) {
			$this->renderNotFound();
			return;
		}

		call_user_func_array([$controller, $action], $resolved->orderedParams());
	}

	private function dispatchLegacy(array $url): void {
		$RotaController = $this->getRota();
		$NameS = "App\\controller\\{$RotaController}";
		$this->Obj = new $NameS;

		$this->enforceGuards($RotaController, $url);

		$routeKey = (string) ($url[0] ?? '');
		if ($routeKey !== '') {
			ClassRateLimiter::enforceScopeAllMethods(
				$routeKey,
				'rate_limit_route_max',
				'rate_limit_route_window_seconds',
				200,
				60
			);
		}

		if (isset($url[1])) {
			$this->addMethod($url);
			return;
		}

		ClassRateLimiter::enforceGlobalPost();
		$defaultMethod = (string) ($url[0] ?? '');
		if ($defaultMethod === 'robots.txt' && method_exists($this->Obj, 'robots')) {
			$this->Obj->robots();
			return;
		}
		if ($defaultMethod !== '' && method_exists($this->Obj, $defaultMethod)) {
			$this->Obj->{$defaultMethod}();
			return;
		}

		if ($RotaController === 'Controller404') {
			return;
		}

		if ($defaultMethod !== '') {
			$this->renderNotFound();
		}
	}

	private function addMethod(array $url): void {
		$methodName = (string) ($url[1] ?? '');
		$resolvedMethod = $methodName;

		if (!method_exists($this->Obj, $resolvedMethod) && strpos($methodName, '_') !== false) {
			$resolvedMethod = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $methodName))));
		}

		if (!method_exists($this->Obj, $resolvedMethod) && is_numeric($methodName) && method_exists($this->Obj, 'show')) {
			call_user_func_array([$this->Obj, 'show'], [$methodName]);
			return;
		}

		if (method_exists($this->Obj, $resolvedMethod)) {
			ClassRateLimiter::enforceGlobalPost();
			$this->setMethod($resolvedMethod);
			$this->addParam($url);
			call_user_func_array([$this->Obj, $this->getMethod()], array_values($this->getParam()));
			return;
		}

		$this->renderNotFound();
	}

	private function addParam(array $url): void {
		$contArray = count($url);
		if ($contArray > 2) {
			$params = [];
			foreach ($url as $key => $value) {
				if ($key > 1) {
					$params[] = $value;
				}
			}
			$this->setParam($params);
		}
	}

	private function enforceGuards(string $controller, array $url): void {
		ClassCommissionGuard::enforce($controller, $url);
		ClassLimitedAccountGuard::enforce($controller, $url);
		ClassTrustBadgeGuard::enforce($controller, $url);
	}

	private function renderNotFound(): void {
		new Controller404();
	}
}
