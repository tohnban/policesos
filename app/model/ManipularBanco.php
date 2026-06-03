<?php

namespace App\model;

class ManipularBanco extends ClassConexao
{
    private $Crud;
    private $Contador;

    private const ALLOWED_TABLES = [
       'users',
       'password_resets',
       'email_verifications',
       'login_attempts',
       'api_tokens',
       'property_types',
       'countries',
       'regions',
       'settings',
       'payment_methods',
       'system_payment_channels',
       'user_payment_accounts',
       'payment_transactions',
       'subscription_plans',
       'user_subscriptions',
       'subscription_events',
       'properties',
       'favorites',
       'property_affiliates',
       'property_boost_requests',
       'property_behavior_events',
       'property_impressions',
       'requests',
       'request_chat_threads',
       'request_chat_messages',
       'request_chat_reads',
       'commissions',
       'notifications',
       'documents',
       'logs',
       'saved_searches',
       'metric_events',
       'background_jobs',
    ];

    protected static function assertTable(string $table): void
    {
        $table = trim($table);
        if ($table === '' || !in_array($table, self::ALLOWED_TABLES, true)) {
            throw new \InvalidArgumentException('Table not allowed: ' . $table);
        }
    }

    protected static function assertIdentifier(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Invalid SQL identifier: ' . $name);
        }
        return $name;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $allowedColumns
     */
    public function insert(string $table, array $data, array $allowedColumns)
    {
        self::assertTable($table);
        $allowed = array_flip(array_map([self::class, 'assertIdentifier'], $allowedColumns));
        $filtered = [];
        foreach ($data as $key => $value) {
            $key = self::assertIdentifier((string) $key);
            if (isset($allowed[$key])) {
                $filtered[$key] = $value;
            }
        }
        if ($filtered === []) {
            return false;
        }

        $columns = array_keys($filtered);
        $quoted = array_map(static fn (string $c) => '`' . $c . '`', $columns);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO `' . self::assertIdentifier($table) . '` (' . implode(',', $quoted) . ') VALUES (' . $placeholders . ')';
        $conn = $this->ConexaoDB();
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute(array_values($filtered));
        if (!$ok) {
            return false;
        }
        $insertId = (int) $conn->lastInsertId();
        return $insertId > 0 ? $insertId : true;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $allowedColumns
     */
    public function updateWhere(string $table, array $data, array $allowedColumns, string $whereSql, array $whereParams): bool
    {
        self::assertTable($table);
        $whereSql = trim($whereSql);
        if ($whereSql === '' || stripos($whereSql, ';') !== false) {
            throw new \InvalidArgumentException('Invalid WHERE clause.');
        }

        $allowed = array_flip(array_map([self::class, 'assertIdentifier'], $allowedColumns));
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $key = self::assertIdentifier((string) $key);
            if (isset($allowed[$key])) {
                $sets[] = '`' . $key . '` = ?';
                $params[] = $value;
            }
        }
        if ($sets === []) {
            return false;
        }

        $sql = 'UPDATE `' . self::assertIdentifier($table) . '` SET ' . implode(', ', $sets) . ' WHERE ' . $whereSql;
        $stmt = $this->ConexaoDB()->prepare($sql);
        return (bool) $stmt->execute(array_merge($params, $whereParams));
    }

    public function prepare($Query)
    {
        return $this->ConexaoDB()->prepare($Query);
    }

    private function preparedStatements($Query, $Parametro)
    {
        $this->Contador = count($Parametro);

        $this->Crud = $this->ConexaoDB()->prepare($Query);
        if ($this->Contador > 0) {
            for ($i = 1; $i <= $this->Contador; $i++) {
                $this->Crud->bindValue($i, $Parametro[$i - 1]);
            }
        }
        $this->Crud->execute();
    }
    public function Salvar($Tabela, $Condicao = null, $Parametro = null)
    {
        if (is_array($Tabela) && is_string($Condicao) && $Parametro === null) {
            $data = $Tabela;
            $table = $Condicao;
            return $this->insert($table, $data, array_keys($data));
        }

        self::assertTable((string) $Tabela);
        $this->preparedStatements("INSERT INTO {$Tabela} VALUES({$Condicao})", $Parametro);
        return $this->Crud;
    }
    public function Buscar($Campos, $Tabela, $Condicao, $Parametro)
    {
        trigger_error('ManipularBanco::Buscar is deprecated; use model prepare() with static SQL.', E_USER_DEPRECATED);
        self::assertTable((string) $Tabela);
        $this->preparedStatements("SELECT {$Campos} FROM {$Tabela} {$Condicao}", $Parametro);
        return $this->Crud;
    }
    public function Seleciona($Campos, $Tabela, $Condicao, $Parametro)
    {
        trigger_error('ManipularBanco::Seleciona is deprecated; use model prepare() with static SQL.', E_USER_DEPRECATED);
        self::assertTable((string) $Tabela);
        $this->preparedStatements("SELECT {$Campos} FROM {$Tabela} 	WHERE {$Condicao}", $Parametro);
        return $this->Crud;
    }
    public function ManipularStored($uspName, $Parametro)
    {
        trigger_error('ManipularBanco::ManipularStored is deprecated.', E_USER_DEPRECATED);
        $this->preparedStatements($uspName, $Parametro);
        return $this->Crud;
    }
    public function Actualizar($Tabela, $Set, $Condicao, $Parametro)
    {
        trigger_error('ManipularBanco::Actualizar is deprecated; use updateWhere() with a column whitelist.', E_USER_DEPRECATED);
        self::assertTable((string) $Tabela);
        $this->preparedStatements("UPDATE {$Tabela} SET {$Set} WHERE {$Condicao}", $Parametro);
        return $this->Crud;
    }
    public function Excluir($Tabela, $Condicao, $Parametro)
    {
        trigger_error('ManipularBanco::Excluir is deprecated; use model prepare() with static SQL.', E_USER_DEPRECATED);
        self::assertTable((string) $Tabela);
        $this->preparedStatements("DELETE FROM {$Tabela} WHERE {$Condicao}", $Parametro);
        return $this->Crud;
    }
}
