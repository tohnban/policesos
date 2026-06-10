param(
    [string]$PhpPath = '',
    [switch]$SkipSmoke,
    [switch]$SkipComposer
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

if ($PhpPath -eq '') {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) {
        $PhpPath = $phpCmd.Source
    } elseif (Test-Path 'C:\xampp\php\php.exe') {
        $PhpPath = 'C:\xampp\php\php.exe'
    } else {
        throw 'PHP not found. Pass -PhpPath.'
    }
}

Write-Host '=== Imobil — Deploy de Producao ===' -ForegroundColor Cyan

Write-Host '[1/6] Verificar .env...'
if (-not (Test-Path "$repoRoot\.env")) {
    if (Test-Path "$repoRoot\.env.production.example") {
        Write-Host '  AVISO: .env ausente. Copie .env.production.example para .env e configure.' -ForegroundColor Yellow
    }
    throw 'Ficheiro .env obrigatorio em producao.'
}

Write-Host '[2/6] Criar directorios de storage...'
& $PhpPath "$repoRoot\scripts\ensure-storage-dirs.php"
if ($LASTEXITCODE -ne 0) { throw 'ensure-storage-dirs falhou.' }

if (-not $SkipComposer) {
    Write-Host '[3/6] Composer install (sem dev)...'
    Push-Location "$repoRoot\src"
    if (Test-Path "$repoRoot\src\composer.phar") {
        & $PhpPath composer.phar install --no-dev --optimize-autoloader --no-interaction
    } else {
        $composer = Get-Command composer -ErrorAction SilentlyContinue
        if (-not $composer) {
            & pwsh -File "$repoRoot\scripts\composer-install.ps1"
        }
        & composer install --no-dev --optimize-autoloader --no-interaction
    }
    if ($LASTEXITCODE -ne 0) { Pop-Location; throw 'composer install falhou.' }
    Pop-Location
} else {
    Write-Host '[3/6] Composer ignorado (-SkipComposer).'
}

Write-Host '[4/6] Validacao de producao...'
& $PhpPath "$repoRoot\scripts\production-check.php"
if ($LASTEXITCODE -ne 0) { throw 'production-check falhou — corrija os erros antes do go-live.' }

Write-Host '[5/6] Limpar cache de pagina...'
& $PhpPath -r "require '$repoRoot/src/vendor/autoload.php'; require '$repoRoot/config/config.php'; \Src\classes\PageCache::flush(); echo 'PageCache flushed'.PHP_EOL;"

if (-not $SkipSmoke) {
    Write-Host '[6/6] Smoke check estatico...'
    & pwsh -File "$repoRoot\scripts\regression-smoke.ps1"
    if ($LASTEXITCODE -ne 0) { throw 'regression-smoke falhou.' }
} else {
    Write-Host '[6/6] Smoke ignorado (-SkipSmoke).'
}

Write-Host ''
Write-Host 'Deploy de producao preparado com sucesso.' -ForegroundColor Green
Write-Host 'Proximos passos manuais:'
Write-Host '  - Aplicar migracoes SQL pendentes'
Write-Host '  - Registar tarefas agendadas: .\scripts\cron_setup.ps1'
Write-Host '  - Validar HTTPS e APP_URL no .env'
Write-Host '  - Testar email (EMAIL_ENABLED=true)'
