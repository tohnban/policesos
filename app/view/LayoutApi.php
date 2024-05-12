<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="author" content="Antonio Nzage Banduenga">
    <meta name="description" content="<?php echo $this->getDescription();?>">
    <meta name="keywords" content="<?php echo $this->getKeywords();?>">
    <title> <?php echo $this->getTitle();?> </title>

    <link rel="stylesheet" href="<?php echo DIRCSS.'style0.css'?>">
    <link rel="stylesheet" href="<?php echo DIRCSS.'style.css'?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>  
<body>
   
<main>
  <form action='<?php echo DIRPAGE.'api/autenticar'?>' method="post">
  <label for="nome">Nome:</label><br>
  <input type="text" name="usuario" value="930890700"><br>

  <label for="lname">Senha:</label><br>
  <input type="text" name="senha" value="Senha"><br><br>
  <input type="submit" value="Submit">
</form> 
</main>  


 <footer>    
<div class="container-footer"> 
    <div class="footer">
        <div class="copyright">
            © 2021 Todos os Direitos Reservados |<a href="<?php echo DIRPAGE.'#'?>"><span>API</span>SOS-Banduenga</a>
        </div>

        <div class="information">
        <a href="<?php echo DIRPAGE.'#'?>">Configuracoes</a> |
        <a href="">Politica de Privacidade</a> | <a href="">Termos e Condicoes</a> | <a href=""><span class="logout">Terminar Sessao</span></a> 
        </div>
    </div>
</div>
</footer>
</body>
</html>             