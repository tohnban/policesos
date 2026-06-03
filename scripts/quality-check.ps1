param(
    [switch]$FixCs
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$srcDir = Join-Path $repoRoot 'src'
Set-Location $repoRoot

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
    Write-Host '[quality] A descarregar composer.phar...'
    Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $composerPhar
}

Write-Host '[quality] composer install (src/)...'
& $phpCmd $composerPhar install --working-dir=$srcDir --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Push-Location $srcDir
try {
    if ($FixCs) {
        Write-Host '[quality] php-cs-fixer (fix)...'
        & $phpCmd vendor/bin/php-cs-fixer fix --allow-risky=yes --config=$repoRoot\.php-cs-fixer.dist.php
    } else {
        Write-Host '[quality] php-cs-fixer (dry-run)...'
        & $phpCmd vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes --config=$repoRoot\.php-cs-fixer.dist.php
        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    }

    Write-Host '[quality] phpstan...'
    & $phpCmd vendor/bin/phpstan analyse -c $repoRoot\phpstan.neon.dist --memory-limit=512M
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
finally {
    Pop-Location
}

Write-Host '[quality] All checks passed.'
exit 0
