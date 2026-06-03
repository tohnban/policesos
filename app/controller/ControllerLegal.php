<?php

namespace App\controller;

use Src\classes\ClassRender;

class ControllerLegal
{
    public function cookies()
    {
        $render = new ClassRender();
        $render->setTitle('Politica de Cookies');
        $render->setDescription('Informacoes sobre cookies, consentimento, privacidade e melhoria de experiencia no sistema.');
        $render->setKeywords('cookies, privacidade, consentimento');
        $render->setDir('legal/cookies');
        $render->renderLayout();
    }
}
