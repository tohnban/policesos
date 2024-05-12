<?php
namespace App\controller;
use Src\classes\ClassRender;
use Src\interfaces\InterfaceView;
use App\model\ModelApi;
use App\model;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class ControllerApi extends ModelApi {
use \Src\traits\TraitUrlParser;
	public function __construct() 
	{
		$Render=new ClassRender();
		if (count($this->parseUrl())==1) {

			$Render->setTitle("Himalaias Página Inicial");
			$Render->setDescription("Compar, Vender e Trabalhar em casa");
			$Render->setKeywords("Himalaias Express,Trabalhar na internet","Comprar produtos online");
			//$Render->setDir("home");
			$Render->renderLayoutApi(); 
		}	
	}
	public function validarEmail($email) {
    
    	$emailDecodificado = base64_decode($email);

	    $prod=new ModelApi();
	    $resultado=$prod->ativarconta($emailDecodificado);
	    //$result=json_encode($resultado);
	    //var_dump($result); 
	    echo($resultado);
	}
	public function enviarEmail($destinatario, $assunto, $corpo) {

        // Inicializar o PHPMailer
        $mailer = new PHPMailer(true);

        try {
            // Configurações do servidor SMTP
            $mailer->isSMTP();
            $mailer->Host = 'smtp.gmail.com'; 
            $mailer->SMTPAuth = true;
            $mailer->Username = 'antoniobanduenga@gmail.com'; // Seu endereço de e-mail
            $mailer->Password = 'vqewaurvjvufxcat'; // Sua senha de e-mail
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mailer->Port = 465; // Porta SMTP 587; 
            
            // Configurações adicionais (opcional)
            $mailer->setFrom('antoniobanduenga@gmail.com', 'SEP');
            $mailer->addAddress($destinatario);
            $mailer->Subject = $assunto;
            $mailer->isHTML(true);
            $mailer->Body = $corpo;

            $mailer->send();

            return true;
        } catch (Exception $e) {
            return false; 
        }
    }
    public function _enviaremail($destinatario, $assunto, $corpo) {
    // Seu código para processar o pedido...

    // Exemplo de uso da função enviarEmail
    //$destinatario = 'tianatote02@gmail.com';
    //$assunto = 'Ativacao da conta';
    //$corpo = 'Conteúdo do E-mail em HTML ou texto';
    
	    if ($this->enviarEmail($destinatario, $assunto, $corpo)) {
	        //echo 'E-mail enviado com sucesso!';
	        return true;
	    } else {
	        //echo 'Falha ao enviar o e-mail.';
	        return false;
	    }
	}
	public function reporsenha(){
		if(isset($_POST['email']))
			{
				$email=$_POST['email'];
				$email=filter_var($email, FILTER_VALIDATE_EMAIL);
			}
			else return;
		$prod=new ModelApi();
		$resultado=$prod->encontrar_email($email);
		if ($resultado==null) {
			$mensagem=json_encode(["mensagem" =>"erro"]);
			echo($mensagem);
			return;
		}
		$nome=$resultado['usuario'];
		$emailCodificado = base64_encode($email);
		$corpo = '<h2>Olá, Senhor(a) '.$nome.'</h2><p>Você solicitou a redefinição da senha no Software de Emergências Policiais-SEP.</p><p>Para criar uma nova senha, clique no link abaixo:</p><p><a href="'.DIRPAGE.'api/redefinirSenha/'.$emailCodificado.'">Redefinir Senha</a></p><p>Se você não solicitou a redefinição de senha, ignore este email.</p><p>Atenciosamente,<br>Equipe do Seu Site</p>';

		if($this->_enviaremail($email,'Repor senha',$corpo)==true && $resultado['email']==$email){
			$mensagem=json_encode(["mensagem" =>"sucesso"]);
			echo($mensagem);
		}
		else{
			$mensagem=json_encode(["mensagem" =>"erro"]);
			echo($mensagem);
		}
	} 

	public function redefinirSenha($email){
		echo '
			<div class="container">
			    <h2>Redefinir Senha</h2>
			    <form action="'.DIRPAGE.'api/alterarsenha/'.$email.'" method="POST">
			        <div class="form-group">
			            <label for="novaSenha">Nova Senha:</label><br>
			            <input type="password" id="novaSenha" name="novaSenha" required>
			        </div>
			        <div class="form-group">
			            <label for="confirmarSenha">Confirmar Senha:</label><br>
			            <input type="password" id="confirmarSenha" name="confirmarSenha" required>
			        </div><P>
			        <div class="form-group">
			            <input type="submit" value="Redefinir Senha">
			        </div>
			    </form>
			</div>';


	}
	public function alterarsenha($email){
		$emailDecodificado = base64_decode($email);
		if(isset($_POST['novaSenha']))
			{$novaSenha=$_POST['novaSenha'];}
			else return;
		if(isset($_POST['confirmarSenha']))
			{$confirmarSenha=$_POST['confirmarSenha'];}
			else return;
		if ($novaSenha!=$confirmarSenha) {
			
			$resultado="<center><font color='red' size='18' face='Arial'>As senhas não coincidem, por favor tente novamente!</font></center>";
			echo($resultado);
			return; 
		}

		$novaSenha = password_hash($novaSenha, PASSWORD_DEFAULT);

		$prod=new ModelApi();
	    $resultado=$prod->senhausuario($novaSenha,$emailDecodificado);

		echo($resultado);
	}
	public function numeropedidos($idusuario)
	{
		$prod=new ModelApi();
		$resultado=$prod->npedidos($idusuario);
		$result=json_encode($resultado);
		echo($result);
	}
	public function numeropedentes($idposto)
	{
		$prod=new ModelApi();
		$resultado=$prod->pendentepedidos($idposto);
		$result=json_encode($resultado);
		echo($result);
	}
	public function numeroresolvidos($idposto)
	{
		$prod=new ModelApi();
		$resultado=$prod->resolvidopedidos($idposto);
		$result=json_encode($resultado);
		echo($result);
	}
	public function graficopedidos($idposto)
	{
		$prod=new ModelApi();
		$resultado=$prod->pedidospormes($idposto);
		$result=json_encode($resultado);
		echo($result);
	}
	public function enviarpedido()
	{
		
		//$latitudeUsuario = -8.8494; // Exemplo: latitude do usuário
		//$longitudeUsuario = 13.894; // Exemplo: longitude do usuário		
		if(isset($_POST['longitude']))
			{$longitudeUsuario=$_POST['longitude'];}
			else return;
		if(isset($_POST['latitude']))
			{$latitudeUsuario=$_POST['latitude'];}
			else return;
		if(isset($_POST['idusuario']))
			{$idusuario=$_POST['idusuario'];}
			else return;
		if(isset($_POST['datahora']))
			{$datahora=$_POST['datahora'];}
			else return;
		// Localização do usuário (latitude e longitude)
		$prod=new ModelApi();
		$idposto=$prod->localizarPosto($latitudeUsuario, $longitudeUsuario);
		//Guardar no Banco de Dados
		$resultado=$prod->salvarPedido($idusuario,$idposto,$longitudeUsuario,$latitudeUsuario,$datahora);
		$resultado=json_encode($resultado);

		echo "$resultado";

	}
	public function getpedidos($IdPosto)
	{	
		$prod=new ModelApi();
		$result=$prod->verPedidos($IdPosto); 
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";
	
	}
	public function _getpedidos()
	{	
		$prod=new ModelApi();
		$result=$prod->_verPedidos(); 
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";	
	}
	public function ver(){
		echo("Funciona");

	}
	public function mudarsituacao($idpedido)
	{	
		$prod=new ModelApi();
		$result=$prod->marcarPedido($idpedido); 
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";	
	}
	public function criarconta()
	{	
		//$nome,$senha,$email,$telefone
		if(isset($_POST['nome']))
			{$nome=$_POST['nome'];}
			else return;
		if(isset($_POST['senha']))
			{
				$senha=$_POST['senha'];
				$senhanova = password_hash($senha, PASSWORD_DEFAULT);
			}
			else return;
		if(isset($_POST['email']))
			{
				$email=$_POST['email'];
				$emailnovo=filter_var($email, FILTER_VALIDATE_EMAIL);
			}
			else return;
			if(isset($_POST['telefone']))
			{$telefone=$_POST['telefone'];}
			else return;
		$prod=new ModelApi();

		if ($email=="") {
			$result=$prod->salvarUsuario($nome,$senhanova, $email,$telefone);
			$resultado=json_encode($result);
		}
		else{
			if ($emailnovo==false) {

			 $resultado=json_encode(['email_invalido' => 'invalido']);
		}
		else
		{
			$result=$prod->salvarUsuario($nome,$senhanova, $emailnovo,$telefone);

			if ($result['senha_correta']==true) {
				// Criptografar o email antes de enviar
				$emailCodificado = base64_encode($email);

				$corpo='<h2>Olá, Senhor(a) '.$nome.'</h2><p>Obrigado por se cadastrar no Software de Emergências Policiais-SEP!</p><p>Para continuar, por favor, clique no link abaixo para confirmar seu email:</p><p><a href='.DIRPAGE.'api/validarEmail/'.$emailCodificado.'> Confirmar Email</a></p><p>Se você não solicitou este cadastro, ignore este email.</p><p>Atenciosamente,<br>Equipe do SEP</p>';

				//$this->_enviaremail($email,'Validacao da conta',$corpo);
				if ($this->_enviaremail($email,'Validacao da conta',$corpo)==true) {
					$resultado=json_encode($result);			
				}			
			}
		}
		}
		echo "$resultado";
		
	}
	public function cadastrarpostos($nome, $endereço, $latitude, $longitude)
	{  
		$prod=new ModelApi();
		$result=$prod->salvarPosto($nome, $endereço, $latitude, $longitude);	
		$resultado=json_encode($result);
		echo "$resultado";
	}
	public function deletarposto($IdPosto)
	{	
		$prod=new ModelApi();
		$result=$prod->excluirPosto($IdPosto);	
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";
	
	}
	public function autenticar()
{   
    if(isset($_POST['senha']) && isset($_POST['usuario'])) {
        $senha = $_POST['senha'];
        $usuario = $_POST['usuario'];

        $prod = new ModelApi();
        $result = $prod->verPerfil($senha, $usuario);

        // Define o tipo de conteúdo da resposta como JSON
        header('Content-Type: application/json');

        // Retorna o resultado codificado em JSON
        echo json_encode($result);
    } else {
        // Caso os parâmetros não sejam enviados
        http_response_code(400); // Bad Request
        echo json_encode(array('error' => 'Parâmetros faltando na requisição'));
    }
}

	public function verificar(){
    // Senha original
    $senha = 'Senha';

    // Criptografa a senha original
    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

    // Senha nova
    $senhanova = 'Senha';

    // Criptografa a senha nova
    $senhanovaCriptografada = password_hash($senhanova, PASSWORD_DEFAULT);

    // Verifica se as senhas criptografadas coincidem
    if (password_verify($senha, $senhaCriptografada) && password_verify($senhanova, $senhanovaCriptografada)) {
        echo "Senhas coincidem";
    } else {
        echo "Senhas diferentes";
    }
}

	public function editarconta()
	{	
		if(isset($_POST['email']))
			{$email=$_POST['email'];}
			else return;
			if(isset($_POST['telefone']))
			{$telefone=$_POST['telefone'];}
			else return;
			if(isset($_POST['idusuario']))
			{$idusuario=$_POST['idusuario'];}
			else return;

		$prod=new ModelApi();
		$prod->editarusuario($email, $telefone, $idusuario);
		$resultado=json_encode(['senha' => true]);
		echo($resultado); 
	}
	public function excluirconta()
	{	
		if(isset($_POST['email']))
			{$email=$_POST['email'];}
			else return;
		if(isset($_POST['telefone']))
			{$telefone=$_POST['telefone'];}
			else return;
		if(isset($_POST['senha']))
			{$senha=$_POST['senha'];}
			else return;
		if(isset($_POST['idusuario']))
			{$idusuario=$_POST['idusuario'];}
			else return;
		//if($email==null){$email=$telefone;}

		$prod=new ModelApi();
		$result=$prod->verPerfil($senha, $telefone);

		if ($result['senha_correta']==true) {
		
			$prod->excluirusuario($idusuario);
			
			$resultado=json_encode(['senha_correta' => true]);
			echo($resultado);
		}else{
			$resultado=json_encode(['senha_correta' => false]);
			echo($resultado);
		}

	}
	public function autenticaradmin($senha,$usuario)
	{	
		$prod=new ModelApi();
		$result=$prod->adminPerfil($senha, $usuario);
		$resultado=json_encode($result);
		echo($resultado); 
	}
	public function cadastraradministrador($nome, $email, $senha, $nivelAcesso, $idPosto)
	{
		$senha = password_hash($senha, PASSWORD_DEFAULT);
		$prod=new ModelApi();
		$result=$prod->salvarAdmin($nome, $email, $senha, $nivelAcesso, $idPosto);
		$resultado=json_encode($result);
		echo($resultado); 
	}
	public function atualizaradministrador($nome, $email, $senha, $nivelAcesso, $idPosto,$idAdministrador)
	{
		$senha = password_hash($senha, PASSWORD_DEFAULT);	
		$prod=new ModelApi();
		$result=$prod->actualizarAdmin($nome, $email, $senha, $nivelAcesso, $idPosto,$idAdministrador);
		$resultado=json_encode($result);
		echo($resultado); 
	}
	public function excluiradministrador($idAdministrador)
	{	
		$prod=new ModelApi();
		$result=$prod->excluirAdmin($idAdministrador);
		$resultado=json_encode($result);
		echo($resultado); 
	}
	public function getadministradores()
	{	
		$prod=new ModelApi();
		$result=$prod->verAdmin(); 
		$resultado=json_encode($result);
		echo ($resultado);
	
	}
	public function verhistorico($idusuario)
	{	
		$prod=new ModelApi();
		$result=$prod->statusPedidos($idusuario);	
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";
	
	}
	public function postoproximo($latitudeUsuario,$longitudeUsuario)
	{	
		// Localização do usuário (latitude e longitude)
		/*if(isset($_POST['longitude']))
			{$longitudeUsuario=$_POST['longitude'];}
			else return;
		if(isset($_POST['latitude']))
			{$latitudeUsuario=$_POST['latitude'];}
			else return;*/

		//$latitudeUsuario = -8.8494; 
		//$longitudeUsuario = 13.894;

		$prod=new ModelApi();
		$result=$prod->topPostos($latitudeUsuario, $longitudeUsuario);	
		$resultado=json_encode($result);
		echo "$resultado";
	
	}
	public function getpostos()
	{	
		$prod=new ModelApi();
		$result=$prod->verPostos(); 
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";
	
	}
	public function updatepostos($idposto, $nome, $endereco, $latitude, $longitude,$telefone){

		$prod=new ModelApi();
		$result=$prod->actualizarPostos($idposto, $nome, $endereco, $latitude, $longitude,$telefone);
		$resultado=json_encode($result);
		//var_dump($result);
		echo "$resultado";
	} 



	private function criptografarEmail($email, $chave) {
	    return openssl_encrypt($email, 'AES-256-CBC', $chave, 0, '1234567890123456');
	}

	// Função para descriptografar o email
	private function descriptografarEmail($emailCriptografado, $chave) {
	    return openssl_decrypt($emailCriptografado, 'AES-256-CBC', $chave, 0, '1234567890123456');
	}
	private function cod_usuario($user_num){
		$user_num=(2*$user_num-1);
		return urlencode(base64_encode($user_num));
		
	}
	private function dec_Usuario($user_codi){
		$user_codi=base64_decode(urldecode($user_codi));
		return(($user_codi+1)/2);
	}
	
	public function var_cart(){
		$obj=new ObjProduto();
		$obj->setIdPessoa(1);
			$data=date_create('now')->format("Y-m-d H:i:s");
		$obj->setDataRegisto($data);
		if (isset($_POST['adiciona'])){$obj->setIdProduto(filter_input(INPUT_POST,'adiciona',FILTER_SANITIZE_SPECIAL_CHARS));}
		
		return $obj; 
	}	
}

