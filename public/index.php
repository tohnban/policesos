<?php
header("content-type:text/html; charset=utf-8");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; script-src 'self'; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; media-src 'self' https: data:;");

require_once("../config/config.php");

require_once("../src/vendor/autoload.php");

if (APP_ENV === 'production' && Src\classes\ClassSession::isHttpsRequest()) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Start session
Src\classes\ClassSession::start();

$Dispatch=new App\Dispatch();
 
