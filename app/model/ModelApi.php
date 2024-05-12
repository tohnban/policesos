<?php
namespace App\model;
use App\model;
use App\model\ManipularBanco;
class ModelApi extends ManipularBanco {
	
	public function adminPerfil($email, $senha){
		$consulta=$this->Buscar("IdAdministrador, Nome, Senha, IdPosto, NivelAcesso","tbadministradores","WHERE Email=? AND Estado<>'Desativada';",array($email));
		if ($consulta->rowCount() >= 1) {

        $Result = $consulta->fetch(\PDO::FETCH_ASSOC);

        $senhaCorreta = password_verify($senha, $Result['Senha']);
        $Array = [
            'IdAdministrador' => $Result['IdAdministrador'],
            'Nome' => $Result['Nome'],
            'Senha' => $senhaCorreta,
            'IdPosto' => $Result['IdPosto'],
            'NivelAcesso' => $Result['NivelAcesso']
        ];
        return $Array;
    }
    else {
        // Perfil não encontrado
        return null;
    }
	}
	public function salvarAdmin($nome, $email, $senha, $nivelAcesso, $idPosto){
		return $this->Salvar("tbadministradores","?,?,?,?,?,?,?",array(0,$nome,$email, $senha,$nivelAcesso,$idPosto,"Desativada"));
	}
	public function actualizarAdmin($nome, $email, $senha, $nivelAcesso, $idPosto,$idAdministrador)
	{
    	return $this->Actualizar("tbadministradores", "Nome =?, Email =?, Senha =?, NivelAcesso =?, IdPosto =?", "IdAdministrador =?", array($nome, $email, $senha, $nivelAcesso, $idPosto,$idAdministrador));
	}
	public function excluirAdmin($idAdministrador){
		return $this->Excluir("tbadministradores","IdAdministrador=?",array($idAdministrador));
	}
	public function verAdmin(){
		$consulta=$this->Buscar("IdAdministrador,Nome,Email,NivelAcesso,IdPosto,Estado","tbadministradores","",array());
		$I=0;
		$Array=[];
			if($consulta->rowCount()>=1){
			while ($Result=$consulta->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'idAdministrador'=>$Result['IdAdministrador'],
				'nome'=>$Result['Nome'],
				'email'=>$Result['Email'],
				'nivelAcesso'=>$Result['NivelAcesso'],
				'idPosto'=>$Result['IdPosto'],
				'estado'=>$Result['Estado']
			];
			$I++;
			}
			return	$Array;
		}
	}
	public function ativarconta($email)
	{
		$consulta = $this->Buscar("*", "tbusuario", "WHERE Email = ?", array($email));
		$result = $consulta->fetch(\PDO::FETCH_ASSOC);

		if ($result['Estado']=="Ativada") {
			$resultado="<center><font color='orange' size='24' face='Arial'>O email já foi verificado anteriormente!</font></center>";
		}
		else{
			return $this->Actualizar("tbusuario", "Estado='Ativada'","Email=?", array($email));
			$resultado="<center><font color='green' size='24' face='Arial'>O email foi verificado com sucesso!</font></center>";
		} 
    	return $resultado;
	}
	public function editarusuario($email, $telefone, $idusuario)
	{
    	$this->Actualizar("tbusuario", "Email=?, Telefone=?","IdUsuario=?", array($email, $telefone,$idusuario));
	}
	public function excluirusuario($idusuario){
		$this->Actualizar("tbusuario", "Estado='Excluida'","IdUsuario=?", array($idusuario)); 
	}
	//Alterar senha via confirmação Email
	public function senhausuario($senha, $email)
	{
    	$this->Actualizar("tbusuario", "Senha=?","Email=?", array($senha, $email));
    	$resultado="<center><font color='green' size='24' face='Arial'>Senha alterada com sucesso!</font></center>";
    	echo($resultado);
	}
	public function npedidos($idusuario){  
		$comando=$this->Buscar("COUNT(*) AS pendentes","tbpedidossos","WHERE Estado = 'Pendente' AND IdUsuario=?;",array($idusuario));
		$Result=$comando->fetch(\PDO::FETCH_ASSOC);

		return $Result; 
	}
	public function pendentepedidos($idposto){ 
		if ($idposto==1) {
		 	$comando=$this->Buscar("COUNT(*) AS qtd","tbpedidossos","WHERE Estado = 'Pendente';",array());
			$Result=$comando->fetch(\PDO::FETCH_ASSOC);

			return $Result;
		 }
		 else{
		 	$comando=$this->Buscar("COUNT(*) AS qtd","tbpedidossos","WHERE Estado = 'Pendente' AND IdPosto=?;",array($idposto));
			$Result=$comando->fetch(\PDO::FETCH_ASSOC);

			return $Result; 
		 }
		
	}
	public function pedidospormes($idposto){  

		if ($idposto==1) {
			$comando=$this->Buscar("MONTH(HoraRequisicao) AS Mes, COUNT(*) AS Qtd","tbpedidossos"," GROUP BY MONTH(HoraRequisicao) ORDER BY Mes ASC;",array());
		$I=0;
		$Array=[]; 
		if($comando->rowCount()>=1){
			while ($Result=$comando->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'mes'=>$Result['Mes'],
				'qtd'=>$Result['Qtd']
			];
			$I++;
			}
			return	$Array;
		}
		}
		else{
			$comando=$this->Buscar("MONTH(HoraRequisicao) AS Mes, COUNT(*) AS Qtd","tbpedidossos","WHERE IdPosto=? GROUP BY MONTH(HoraRequisicao) ORDER BY Mes ASC;",array($idposto));
		$I=0;
		$Array=[]; 
		if($comando->rowCount()>=1){
			while ($Result=$comando->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'mes'=>$Result['Mes'],
				'qtd'=>$Result['Qtd']
			];
			$I++;
			}
			return	$Array;
		}
		}
		
	}
	public function resolvidopedidos($idposto){  
		
		if ($idposto==1) {
			$comando=$this->Buscar("COUNT(*) AS qtd","tbpedidossos","WHERE Estado = 'Resolvido' AND HoraRequisicao >= DATE_SUB(NOW(), INTERVAL 30 DAY);",array());
			$Result=$comando->fetch(\PDO::FETCH_ASSOC);

			return $Result; 
		}
		else{
			$comando=$this->Buscar("COUNT(*) AS qtd","tbpedidossos","WHERE Estado = 'Resolvido' AND IdPosto=? AND HoraRequisicao >= DATE_SUB(NOW(), INTERVAL 30 DAY);",array($idposto));
			$Result=$comando->fetch(\PDO::FETCH_ASSOC);

			return $Result; 
		}
		
	}


	public function salvarPosto($nome, $endereço, $latitude, $longitude,$telefone){
		return $this->Salvar("tbpostospolicia","?,?,?,?,?,?",array(0,$nome, $endereço, $latitude, $longitude,$telefone));
	}
	public function salvarPedido($idusuario,$idposto,$longitude,$latitude,$datahora){
		return $this->Salvar("tbpedidossos","?,?,?,?,?,?,?",array(0,$idusuario,$idposto,$datahora,"Pendente",$longitude,$latitude));
	}
	public function marcarPedido($idpedido){
		return $this->Actualizar("tbpedidossos", "Estado=?", "IdPedido=?",array("Resolvido",$idpedido));
	}
	public function encontrar_email($email){
		$consulta = $this->Buscar("*", "tbusuario", "WHERE Email = ?", array($email));

        if ($consulta->rowCount() >= 1) {
            $result = $consulta->fetch(\PDO::FETCH_ASSOC);
            $arrayUsuario = [ 
                'idusuario' => $result['IdUsuario'],
                'usuario' => $result['Usuarionome'],
                'email' => $result['Email'],
                'telefone' => $result['Telefone']
            ];

            return $arrayUsuario;
        } else {
            // Usuário não encontrado
            return null;
        }
	}
	public function salvarUsuario($nome, $senha, $email, $telefone) {
    try {
        //$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        $parametros = array(0,$nome, $senha, $email, $telefone, "Desativada");
        $result = $this->Salvar("tbusuario", "?,?,?,?,?,?", $parametros);

        // Consulta para obter os dados do usuário inserido
        $consulta = $this->Buscar("*", "tbusuario", "WHERE Telefone = ?", array($telefone));

        if ($consulta->rowCount() >= 1) {
            $result = $consulta->fetch(\PDO::FETCH_ASSOC);
            $senhaCorreta = password_verify($senha, $result['Senha']);
  
            $arrayUsuario = [
                'idusuario' => $result['IdUsuario'],
                'usuario' => $result['Usuarionome'],
                'email' => $result['Email'],
                'telefone' => $result['Telefone'],
                'senha_correta' => $senhaCorreta 
            ];

            return $arrayUsuario;
        } else {
            // Usuário não encontrado
            return null;
        }
    } catch (\PDOException $e) {
        /*echo "Erro ao salvar usuário: " . $e->getMessage();
        return null;*/
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
        	echo "Duplicado";
	    } else {
	        throw $e;
	    }
    }
	}
	public function actualizarPostos($idposto, $nome, $endereco, $latitude, $longitude,$telefone)
	{
    	return $this->Actualizar("tbpostospolicia", "Nome=?, Endereco=?, Latitude=?, Longitude=?,Telefone=?", "IdPosto=?", array($nome, $endereco, $latitude, $longitude,$telefone, $idposto));
	}
	public function excluirPosto($idposto){
		return $this->Excluir("tbpostospolicia","IdPosto=?",array($idposto));
	}
// Fórmula de Haversine
	function calcularDistancia($latitudeOrigem, $longitudeOrigem, $latitudeDestino, $longitudeDestino) {
		$raioTerra = 6371; // Raio médio da Terra em quilômetros
		$dLat = deg2rad($latitudeDestino - $latitudeOrigem);
		$dLon = deg2rad($longitudeDestino - $longitudeOrigem);
		$a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($latitudeOrigem)) * cos(deg2rad($latitudeDestino)) * sin($dLon / 2) * sin($dLon / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		$distancia = $raioTerra * $c;
		return $distancia;
	}
	public function localizarPosto($latitudeUsuario,$longitudeUsuario)
	{

		// Inicializar a variável $postosOrdenados antes de usá-la
		$consulta=$this->Buscar("*","tbpostospolicia","",array());
		$postosPoliciais = $consulta->fetchAll(\PDO::FETCH_ASSOC);

		// Inicializar a variável $postosOrdenados antes de usá-la
		$postosOrdenados = [];
		foreach ($postosPoliciais as $posto) {
			$distancia = $this->calcularDistancia($latitudeUsuario, $longitudeUsuario, $posto['Latitude'], $posto['Longitude']);
			$posto['Distancia'] = $distancia;
			$postosOrdenados[] = $posto;
		}
	
		// Ordenar os postos por distância (do mais próximo para o mais distante)
		usort($postosOrdenados, function($a, $b) {
			return $a['Distancia'] <=> $b['Distancia'];
		});
	
		// Selecionar o posto mais próximo (primeiro na lista ordenada)
		$postoMaisProximo = $postosOrdenados[0];
	
		// Exibir o posto mais próximo
		//echo "O posto policial mais próximo é: " . $postoMaisProximo['Nome'] . " - " . $postoMaisProximo['Endereco'] . " (Distância: " . round($postoMaisProximo['Distancia'], 2) . " km)";
		//echo("".$postoMaisProximo['IdPosto']."<br>");
		
		return $postoMaisProximo['IdPosto'];
	}


	public function topPostos($latitudeUsuario,$longitudeUsuario)
	{

		// Inicializar a variável $postosOrdenados antes de usá-la
		$consulta=$this->Buscar("*","tbpostospolicia","",array());
		$postosPoliciais = $consulta->fetchAll(\PDO::FETCH_ASSOC);

		// Inicializar a variável $postosOrdenados antes de usá-la
		$postosOrdenados = [];
		foreach ($postosPoliciais as $posto) {
			$distancia = $this->calcularDistancia($latitudeUsuario, $longitudeUsuario, $posto['Latitude'], $posto['Longitude']);
			$posto['Distancia'] = $distancia;
			$postosOrdenados[] = $posto;
		}
	
		// Ordenar os postos por distância (do mais próximo para o mais distante)
		usort($postosOrdenados, function($a, $b) {
			return $a['Distancia'] <=> $b['Distancia'];
		});
	
		// Selecionar os 5 postos mais próximos (primeiros 5 na lista ordenada)
		$postosMaisProximos = array_slice($postosOrdenados, 0, 10);
		
		return $postosMaisProximos;
		// Exibir os 5 postos mais próximos
		/*echo "Os 5 postos policiais mais próximos são:\n";
		foreach ($postosMaisProximos as $posto) {
			echo $posto['Nome'] . " - " . $posto['Endereco'] . " (Distância: " . round($posto['Distancia'], 2) . " km)\n";
			//$postos=json_encode($posto);
			//var_dump($postos);
		}*/
	}
	public function statusPedidos($idusuario){
		$consulta=$this->Buscar("ps.IdPedido, pp.Nome, pp.Endereco, ps.HoraRequisicao, ps.Estado","tbpedidossos ps INNER JOIN tbpostospolicia pp"," ON ps.IdPosto = pp.IdPosto AND ps.IdUsuario=? ORDER BY ps.IdPedido DESC",array($idusuario));
		$I=0;
		$Array=[];
		if($consulta->rowCount()>=1){
			while ($Result=$consulta->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'idpedido'=>$Result['IdPedido'],
				'nome'=>$Result['Nome'],
				'endereco'=>$Result['Endereco'],
				'data'=>$Result['HoraRequisicao'],
				'estado'=>$Result['Estado']
			];
			$I++;
			}
			return	$Array;
		}
	 
	}
	public function verPerfil($senha, $usuario){
    // Consulta SQL para obter os dados do perfil do usuário
    $consulta = $this->Buscar("*", "tbusuario", "WHERE Email=? OR Telefone=?", array($usuario, $usuario));
    if ($consulta->rowCount() >= 1) {
        // Obtém o resultado da consulta
        $Result = $consulta->fetch(\PDO::FETCH_ASSOC);

        $senhaCorreta = password_verify($senha, $Result['Senha']);
        $Array = [
            'idusuario' => $Result['IdUsuario'],
            'usuario' => $Result['Usuarionome'],
            'email' => $Result['Email'],
            'telefone' => $Result['Telefone'],
            'estado' => $Result['Estado'],
            'senha_correta' => $senhaCorreta 
        ];
        return $Array;
    } else {
        // Perfil não encontrado
        return null;
    }
}
	public function verPostos(){
		$consulta=$this->Buscar("*","tbpostospolicia","WHERE IdPosto<>1",array());
		$I=0;
		$Array=[]; 
		if($consulta->rowCount()>=1){
			while ($Result=$consulta->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'idposto'=>$Result['IdPosto'],
				'descricao'=>$Result['Nome'],
				'endereco'=>$Result['Endereco'],
				'latitude'=>$Result['Latitude'],
				'longitude'=>$Result['Longitude'],
				'telefone'=>$Result['Telefone']
			];
			$I++;
			}
			return	$Array;
		} 
	}
	public function verPedidos($idPosto){
	$consulta = $this->Buscar("ps.IdPedido, pp.Nome, pp.Endereco, ps.HoraRequisicao, ps.Estado, usu.Telefone, ps.Longitude, ps.Latitude","tbpedidossos AS ps INNER JOIN tbpostospolicia AS pp ON ps.IdPosto = pp.IdPosto INNER JOIN tbusuario AS usu ON ps.IdUsuario = usu.IdUsuario", "WHERE ps.IdPosto=? ORDER BY ps.IdPedido DESC;",array($idPosto));

		$I=0;
		$Array=[];
		if($consulta->rowCount()>=1){
			while ($Result=$consulta->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'idpedido'=>$Result['IdPedido'],
				'posto'=>$Result['Nome'],
				'endereco'=>$Result['Endereco'],
				'data'=>$Result['HoraRequisicao'],
				'estado'=>$Result['Estado'],
				'telefone'=>$Result['Telefone'],
				'longitude'=>$Result['Longitude'],
				'latitude'=>$Result['Latitude']
			];
			$I++;
			}
			return	$Array;
		}
	 
	}
	public function _verPedidos(){
		$consulta=$this->Buscar("ps.IdPedido, pp.Nome, pp.Endereco, ps.HoraRequisicao, ps.Estado,usu.Telefone,ps.Longitude,ps.Latitude","tbpedidossos AS ps INNER JOIN tbpostospolicia AS pp ON ps.IdPosto = pp.IdPosto INNER JOIN tbusuario AS usu"," ON ps.IdUsuario = usu.IdUsuario ORDER BY ps.IdPedido DESC;",array());
		$I=0;
		$Array=[];
		if($consulta->rowCount()>=1){
			while ($Result=$consulta->fetch(\PDO::FETCH_ASSOC)) {
			$Array[$I]=[
				'idpedido'=>$Result['IdPedido'],
				'posto'=>$Result['Nome'],
				'endereco'=>$Result['Endereco'],
				'data'=>$Result['HoraRequisicao'],
				'estado'=>$Result['Estado'],
				'telefone'=>$Result['Telefone'],
				'longitude'=>$Result['Longitude'],
				'latitude'=>$Result['Latitude']
			];
			$I++;
			}
			return	$Array;
		}
	 
	}

}
 
?>