<?php

namespace App\controller;

use Src\classes\ClassRender;
use Src\classes\ClassSEO;

class ControllerLegal
{
    public function cookies()
    {
        $render = new ClassRender();
        $render->setTitle('Política de Cookies — Imobil Fácil');
        $render->setDescription('Saiba como a Imobil Fácil utiliza cookies, como gerir o seu consentimento e quais dados são recolhidos para melhorar a experiência.');
        $render->setKeywords('cookies, privacidade, consentimento, imobil facil angola');
        $render->setCanonical(rtrim(DIRPAGE, '/') . '/cookies');
        $render->setOgTitle('Política de Cookies — Imobil Fácil');
        $render->setOgDescription($render->getDescription());
        $render->setOgImage(ClassSEO::defaultOgImage());
        $render->setDir('legal/cookies');
        $render->renderLayout();
    }
}
