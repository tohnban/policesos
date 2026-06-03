<?php
namespace Src\traits;

trait TraitUrlParser{
	#Dividir url em Array
	public function parseUrl(){
		$url = isset($_GET['url']) ? filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL) : '';
		return explode("/", $url);
	}
}