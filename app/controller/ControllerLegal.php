<?php

namespace App\controller;

use Src\classes\ClassRender;
use Src\classes\ClassSEO;

class ControllerLegal
{
    public function cookies(): void
    {
        $this->renderPage(
            'cookies',
            'Política de Cookies — Imobil Fácil',
            'Saiba como a Imobil Fácil utiliza cookies, como gerir o seu consentimento e quais dados são recolhidos para melhorar a experiência.',
            'cookies',
            'cookies, privacidade, consentimento, imobil facil angola'
        );
    }

    public function privacidade(): void
    {
        $this->renderPage(
            'privacidade',
            'Política de Privacidade — Imobil Fácil',
            'Informação sobre tratamento de dados pessoais na plataforma imobiliária Imobil Fácil, operada em Angola pela Pague Fácil.',
            'privacidade',
            'privacidade, dados pessoais, rgpd angola, imobil facil'
        );
    }

    public function termos(): void
    {
        $this->renderPage(
            'termos',
            'Termos e Condições — Imobil Fácil',
            'Regras de utilização da plataforma Imobil Fácil: contas, imóveis, solicitações, comissões e responsabilidades dos utilizadores.',
            'termos',
            'termos, condições, regulamento, imobil facil angola'
        );
    }

    private function renderPage(
        string $viewDir,
        string $title,
        string $description,
        string $slug,
        string $keywords = ''
    ): void {
        $base = rtrim(DIRPAGE, '/');
        $render = new ClassRender();
        $render->setTitle($title);
        $render->setDescription($description);
        $render->setKeywords($keywords !== '' ? $keywords : ClassSEO::DEFAULT_KEYWORDS);
        $render->setCanonical($base . '/' . $slug);
        $render->setOgTitle($title);
        $render->setOgDescription($description);
        $render->setOgImage(ClassSEO::defaultOgImage());
        $render->addStructuredData(ClassSEO::getBreadcrumbSchema([
            ['name' => 'Início', 'url' => $base],
            ['name' => $this->breadcrumbLabel($slug), 'url' => $base . '/' . $slug],
        ]));
        $render->setDir('legal/' . $viewDir);
        $render->renderLayout();
    }

    private function breadcrumbLabel(string $slug): string
    {
        return match ($slug) {
            'cookies' => 'Política de Cookies',
            'privacidade' => 'Política de Privacidade',
            'termos' => 'Termos e Condições',
            default => 'Base legal',
        };
    }
}
