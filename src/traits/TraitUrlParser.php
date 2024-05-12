<?php
namespace Src\traits;

trait TraitUrlParser{
	#Dividir url em Array
	public function parseUrl(){
		return explode("/",rtrim($_GET['url']), FILTER_SANITIZE_URL);
	}
}