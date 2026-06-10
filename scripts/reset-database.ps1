<#
.SYNOPSIS
    Repõe a base de dados a zero (DROP + schema consolidado).
.PARAMETER EmptyOnly
    Apenas estrutura + catálogos (países, settings, métodos de pagamento).
    Sem utilizadores, imóveis, pedidos nem dados de demonstração.
.EXAMPLE
    pwsh -File scripts/reset-database.ps1 -EmptyOnly
    pwsh -File scripts/reset-database.ps1
#>
param(
    [switch]$EmptyOnly,
    [string]$MysqlPath = '',
    [string]$SchemaFile = ''
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot

if ($SchemaFile -eq '') {
    $SchemaFile = Join-Path $repoRoot 'database_schema.sql'
}
if (-not (Test-Path $SchemaFile)) {
    throw "Schema nao encontrado: $SchemaFile"
}

$envFile = Join-Path $repoRoot '.env'
$dbHost = 'localhost'
$dbPort = '3306'
$dbName = 'imobil_db'
$dbUser = 'root'
$dbPass = ''

if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { return }
        if ($line -match '^([^=]+)=(.*)$') {
            switch ($matches[1].Trim()) {
                'DB_HOST' { $dbHost = $matches[2].Trim() }
                'DB_PORT' { $dbPort = $matches[2].Trim() }
                'DB_NAME' { $dbName = $matches[2].Trim() }
                'DB_USER' { $dbUser = $matches[2].Trim() }
                'DB_PASS' { $dbPass = $matches[2].Trim() }
            }
        }
    }
}

if ($MysqlPath -eq '') {
    foreach ($c in @(
        'C:\xampp\mysql\bin\mysql.exe',
        'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe'
    )) {
        if (Test-Path $c) { $MysqlPath = $c; break }
    }
    if ($MysqlPath -eq '') {
        $cmd = Get-Command mysql -ErrorAction SilentlyContinue
        if ($cmd) { $MysqlPath = $cmd.Source }
    }
}
if ($MysqlPath -eq '' -or -not (Test-Path $MysqlPath)) {
    throw 'mysql.exe nao encontrado. Passe -MysqlPath.'
}

function Get-MysqlArgs {
    $a = @('-h', $dbHost, '-P', $dbPort, '-u', $dbUser, '--default-character-set=utf8mb4')
    if ($dbPass -ne '') { $a += "-p$dbPass" }
    return $a
}

function Invoke-MysqlSql {
    param([string]$Sql)
    & $MysqlPath @(Get-MysqlArgs) -e $Sql
    if ($LASTEXITCODE -ne 0) { throw "MySQL falhou: $Sql" }
}

function Import-SqlFile {
    param([string]$File)
    Get-Content -Path $File -Raw -Encoding UTF8 | & $MysqlPath @(Get-MysqlArgs)
    if ($LASTEXITCODE -ne 0) { throw "Import falhou: $File" }
}

Write-Host "=== Reset base de dados: $dbName ===" -ForegroundColor Cyan

Invoke-MysqlSql -Sql "DROP DATABASE IF EXISTS ``$dbName``; CREATE DATABASE ``$dbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

$sqlToImport = $SchemaFile
if ($EmptyOnly) {
    Write-Host 'Modo producao: estrutura + catalogos, sem dados de demonstracao.'
    $content = Get-Content -Path $SchemaFile -Raw -Encoding UTF8
    $marker = '-- Password hash: "password"'
    $idx = $content.IndexOf($marker)
    if ($idx -lt 0) {
        throw "Marcador de seed nao encontrado em database_schema.sql ($marker)"
    }
    $tempSchema = Join-Path $env:TEMP "imobil_empty_$([guid]::NewGuid().ToString('N')).sql"
    Set-Content -Path $tempSchema -Value $content.Substring(0, $idx).TrimEnd() -Encoding UTF8 -NoNewline
    $sqlToImport = $tempSchema
}

Write-Host "Importar: $(Split-Path $sqlToImport -Leaf) ..."
Import-SqlFile -File $sqlToImport

if ($EmptyOnly -and $sqlToImport -ne $SchemaFile) {
    Remove-Item $sqlToImport -Force -ErrorAction SilentlyContinue
}

Write-Host ''
Write-Host "Base $dbName reposta com sucesso." -ForegroundColor Green
if (-not $EmptyOnly) {
    Write-Host 'Inclui dados de demonstracao (senha das contas: password).'
} else {
    Write-Host 'Base vazia pronta para producao — registe o primeiro admin via /register ou SQL.'
}
