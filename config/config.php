<?php
#Arquivos directorios raizes
$PastaInterna="";
define('DIRPAGE',"http://{$_SERVER['HTTP_HOST']}/{$PastaInterna}");

if(substr($_SERVER['DOCUMENT_ROOT'], -1)=='/')
	{define('DIRREQ',"{$_SERVER['DOCUMENT_ROOT']}{$PastaInterna}");}
else{define('DIRREQ',"{$_SERVER['DOCUMENT_ROOT']}/{$PastaInterna}");}

define('DIRIMG',DIRPAGE."public/img/");
define('DIRCSS',DIRPAGE."public/css/");
define('DIRJS',DIRPAGE."public/js/");
define('DIRADMIN',DIRPAGE."public/admin/");
define('DIRAUDIO',DIRPAGE."public/audio/");
define('DIRDESIGN',DIRPAGE."public/design/");
define('DIRFONTES',DIRPAGE."public/fontes/");
define('DIRVIDEO',DIRPAGE."public/video/"); 

#Acesso ao Banco de dados
define('HOST',"localhost");
define('DB',"sosdb");
define('USER',"root");
define('PASS',"");



