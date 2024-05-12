<?php
namespace App\model;

class ManipularBanco extends ClassConexao{

private $Crud;
private $Contador; 

 private function preparedStatements($Query, $Parametro)
 {
 	$this->Contador=count($Parametro);
  
 	$this->Crud=$this->ConexaoDB()->prepare($Query);
 	if($this->Contador>0){
 		for ($i=1; $i <= $this->Contador; $i++) { 
 			$this->Crud->bindValue($i,$Parametro[$i-1]);
 		}
 	}
 	$this->Crud->execute();
 }
 public function Salvar($Tabela, $Condicao, $Parametro)
 {
 	$this->preparedStatements("INSERT INTO {$Tabela} VALUES({$Condicao})",$Parametro);
   return $this->Crud;
 }
 public function Buscar($Campos,$Tabela,$Condicao,$Parametro)
 {
 	$this->preparedStatements("SELECT {$Campos} FROM {$Tabela} {$Condicao}",$Parametro);
 	return $this->Crud;
 }
 public function Seleciona($Campos,$Tabela,$Condicao,$Parametro)
 {
 	$this->preparedStatements("SELECT {$Campos} FROM {$Tabela} 	WHERE {$Condicao}",$Parametro);
 	return $this->Crud;
 }
  public function ManipularStored($uspName,$Parametro)
 {
   $this->preparedStatements($uspName,$Parametro);
   return $this->Crud;
 }
 public function Actualizar($Tabela,$Set,$Condicao,$Parametro)
 {
 	$this->preparedStatements("UPDATE {$Tabela} SET {$Set} WHERE {$Condicao}",$Parametro);
 	return $this->Crud;
 }
 public function Excluir($Tabela,$Condicao,$Parametro)
 {
 	$this->preparedStatements("DELETE FROM {$Tabela} WHERE {$Condicao}",$Parametro);
 	return $this->Crud;
 }
}
