param(
    [string]$DbHost = '',
    [int]$DbPort = 0,
    [string]$DbUser = '',
    [string]$DbPass = '',
    [string]$DbName = 'imobil_db',
    [string]$MysqlPath = 'C:\xampp\mysql\bin\mysql.exe',
    [string]$SeedFile = ''
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($SeedFile)) {
    $SeedFile = Join-Path $PSScriptRoot 'seed_full_test_data.sql'
}

if (-not (Test-Path $MysqlPath)) {
    throw "mysql client nao encontrado em $MysqlPath"
}
if (-not (Test-Path $SeedFile)) {
    throw "arquivo de seed nao encontrado em $SeedFile"
}

$envFile = Join-Path $repoRoot '.env'
if (-not (Test-Path $envFile)) {
    $envFile = Join-Path $repoRoot '.env.example'
}

if (Test-Path $envFile) {
    $envMap = @{}
    Get-Content $envFile | ForEach-Object {
        if ($_ -match '^\s*#' -or $_ -match '^\s*$') { return }
        $parts = $_.Split('=', 2)
        if ($parts.Count -eq 2) {
            $k = $parts[0].Trim()
            $v = $parts[1].Trim()
            $envMap[$k] = $v
        }
    }

    if ([string]::IsNullOrWhiteSpace($DbHost) -and $envMap.ContainsKey('DB_HOST')) { $DbHost = $envMap['DB_HOST'] }
    if ($DbPort -eq 0 -and $envMap.ContainsKey('DB_PORT')) { $DbPort = [int]$envMap['DB_PORT'] }
    if ([string]::IsNullOrWhiteSpace($DbUser) -and $envMap.ContainsKey('DB_USER')) { $DbUser = $envMap['DB_USER'] }
    if ([string]::IsNullOrWhiteSpace($DbPass) -and $envMap.ContainsKey('DB_PASS')) { $DbPass = $envMap['DB_PASS'] }
    if ([string]::IsNullOrWhiteSpace($DbName) -and $envMap.ContainsKey('DB_NAME')) { $DbName = $envMap['DB_NAME'] }
}

if ([string]::IsNullOrWhiteSpace($DbHost)) { $DbHost = 'localhost' }
if ($DbPort -eq 0) { $DbPort = 3306 }
if ([string]::IsNullOrWhiteSpace($DbUser)) { $DbUser = 'root' }
if ([string]::IsNullOrWhiteSpace($DbName)) { $DbName = 'imobil_db' }

Write-Host "[seed] host=$DbHost port=$DbPort db=$DbName user=$DbUser"
Write-Host "[seed] executando: $SeedFile"

$command = "SOURCE $SeedFile"
& $MysqlPath --host=$DbHost --port=$DbPort --user=$DbUser --password=$DbPass --database=$DbName --default-character-set=utf8mb4 --execute=$command

if ($LASTEXITCODE -ne 0) {
    throw "seed falhou com codigo $LASTEXITCODE"
}

Write-Host '[seed] concluido com sucesso.'
