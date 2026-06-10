<?php

namespace App\controller;

/**
 * Legacy facade for login, register, recover and related auth URLs.
 */
class ControllerAuth
{
    private ?ControllerAuthPages $pages = null;

    private function pages(): ControllerAuthPages
    {
        return $this->pages ??= new ControllerAuthPages();
    }

    public function login()
    {
        $this->pages()->login();
    }

    public function register()
    {
        $this->pages()->register();
    }


    private ?ControllerAuthSession $session = null;

    private function session(): ControllerAuthSession
    {
        return $this->session ??= new ControllerAuthSession();
    }

    public function authenticate()
    {
        $this->session()->authenticate();
    }

    public function logout()
    {
        $this->session()->logout();
    }


    private ?ControllerAuthRegistration $registration = null;

    private function registration(): ControllerAuthRegistration
    {
        return $this->registration ??= new ControllerAuthRegistration();
    }

    public function store()
    {
        $this->registration()->store();
    }

    public function verify()
    {
        $this->registration()->verify();
    }


    private ?ControllerAuthPassword $password = null;

    private function password(): ControllerAuthPassword
    {
        return $this->password ??= new ControllerAuthPassword();
    }

    public function recover()
    {
        $this->password()->recover();
    }

    public function reset()
    {
        $this->password()->reset();
    }
}
