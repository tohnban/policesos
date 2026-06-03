<?php

namespace App\model;

class ClassConexao
{
    private static $sharedConnection = null;

    public function ConexaoDB()
    {
        if (self::$sharedConnection instanceof \PDO) {
            return self::$sharedConnection;
        }

        try {
            self::$sharedConnection = new \PDO(
                'mysql:host=' . HOST . ';dbname=' . DB . ';charset=utf8mb4',
                USER,
                PASS,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_PERSISTENT => defined('DB_PERSISTENT') ? (bool) DB_PERSISTENT : false,
                ]
            );
            return self::$sharedConnection;
        } catch (\PDOException $Erro) {
            throw $Erro;
        }
    }
}
