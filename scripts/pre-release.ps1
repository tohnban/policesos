param(
    [string]$BaseUrl = 'http://localhost',
    [switch]$SkipHttp
)

$ErrorActionPreference = 'Stop'

Write-Host '[pre-release] Running static smoke checks...'
& pwsh -File "$PSScriptRoot/regression-smoke.ps1"
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

if (-not $SkipHttp) {
    Write-Host '[pre-release] Running local HTTP smoke checks (requires web server at BaseUrl)...'
    & pwsh -File "$PSScriptRoot/regression-smoke.ps1" -RunHttp -BaseUrl $BaseUrl
    if ($LASTEXITCODE -ne 0) {
        Write-Host '[pre-release] HTTP checks failed. Ensure Apache/local server is running or use -SkipHttp.'
        exit $LASTEXITCODE
    }
}

Write-Host '[pre-release] All checks passed.'
exit 0
