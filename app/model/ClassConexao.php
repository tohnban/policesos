<?php
namespace App\model;

class ClassConexao{
	public function ConexaoDB(){
		try {
			$Con=new \PDO("mysql:host=".HOST.";dbname=".DB."","".USER."","".PASS."");
			return $Con;
		} 
		catch (\PDOException $Erro) {
			return $Erro->getMessage(); 
		}
	}
}