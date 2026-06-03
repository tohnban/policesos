<?php
namespace App\model;

class ManipularBanco extends ClassConexao{

private $Crud;
private $Contador; 

 public function prepare($Query) {
 	return $this->ConexaoDB()->prepare($Query);
 }

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
 public function Salvar($Tabela, $Condicao = null, $Parametro = null)
 {
 	if (is_array($Tabela) && is_string($Condicao) && $Parametro === null) {
 		$data = $Tabela;
 		$table = $Condicao;
 		$columns = array_keys($data);
 		$placeholders = implode(',', array_fill(0, count($columns), '?'));
 		$sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";
		$conn = $this->ConexaoDB();
		$stmt = $conn->prepare($sql);
		$ok = $stmt->execute(array_values($data));
		if (!$ok) {
			return false;
		}
		$insertId = (int) $conn->lastInsertId();
		return $insertId > 0 ? $insertId : true;
 	}

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
