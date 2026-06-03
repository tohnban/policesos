param(
    [switch]$Dev
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$srcDir = Join-Path $repoRoot 'src'
Set-Location $srcDir

$phpCmd = $null
foreach ($candidate in @('php', 'c:\xampp\php\php.exe')) {
    if (Get-Command $candidate -ErrorAction SilentlyContinue) {
        $phpCmd = (Get-Command $candidate).Source
        break
    }
}

if (-not $phpCmd) {
    Write-Error 'PHP nao encontrado no PATH.'
}

$composerPhar = Join-Path $srcDir 'composer.phar'
if (-not (Test-Path $composerPhar)) {
    Write-Host '[composer] A descarregar composer.phar...'
    Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $composerPhar
}

$installArgs = @('install', '--no-interaction', '--prefer-dist')
if (-not $Dev) {
    $installArgs += '--no-dev'
}

Write-Host "[composer] composer $($installArgs -join ' ') (src/)..."
& $phpCmd $composerPhar @installArgs
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Write-Host '[composer] Dependencias instaladas.'
exit 0
