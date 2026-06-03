<?php
namespace App\controller;

use Src\classes\ClassAuth;
use Src\classes\ClassHeaders;
use Src\classes\ClassRender;
use Src\classes\ClassSEO;

class Controller404 {
    public function __construct() {
        http_response_code(404);
        ClassHeaders::setCacheNone();

        $requestedPath = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
        if ($requestedPath === 'public/index.php') {
            $requestedPath = '';
        }

        $render = new ClassRender();
        $render->setTitle('Página não encontrada — Imobil Fácil');
        $render->setDescription('A página que procurou não existe ou foi movida. Volte ao início ou explore imóveis disponíveis na Imobil Fácil.');
        $render->setKeywords('404, página não encontrada, imóveis');
        $render->setCanonical(rtrim(DIRPAGE, '/'));
        $render->setData([
            'requestedPath' => $requestedPath,
            'isAuthenticated' => ClassAuth::check(),
        ]);
        $render->setDir('errors/404');
        $render->renderLayout();
    }
}
