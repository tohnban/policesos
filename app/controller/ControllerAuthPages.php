<?php

namespace App\controller;

use Src\classes\ClassAccess;
use Src\classes\ClassAuth;
use Src\classes\ClassRender;

class ControllerAuthPages
{

    public function login()
    {
        if (ClassAuth::check()) {
            $user = ClassAuth::user();
            $redirectTo = ClassAccess::canUseAccountStatusPage($user)
                ? DIRPAGE . 'dashboard/accountStatus'
                : DIRPAGE;
            header('Location: ' . $redirectTo);
            exit;
        }

        $render = new ClassRender();
        $render->setTitle('Entrar na Imobil Fácil');
        $render->setDescription('Aceda à sua conta Imobil Fácil com email ou telefone.');
        $render->setKeywords('login, entrar, conta, imobil');
        $render->setDir('auth/login');
        $render->renderLayout();
    }


    public function register()
    {
        if (ClassAuth::check()) {
            $user = ClassAuth::user();
            $redirectTo = ClassAccess::canUseAccountStatusPage($user)
                ? DIRPAGE . 'dashboard/accountStatus'
                : DIRPAGE;
            header('Location: ' . $redirectTo);
            exit;
        }

        $render = new ClassRender();
        $render->setTitle('Criar conta na Imobil Fácil');
        $render->setDescription('Registe-se na Imobil Fácil para explorar imóveis, solicitar visitas e gerir o seu perfil.');
        $render->setKeywords('registar, criar conta, imobil, imóveis');
        $render->setDir('auth/register');
        $render->renderLayout();
    }

}
