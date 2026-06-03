param(
    [string]$BaseUrl = 'http://localhost',
    [switch]$RunHttp
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

if (-not (Test-Path "$repoRoot/src/vendor/autoload.php")) {
    Write-Host '[bootstrap] src/vendor ausente — a executar composer install...'
    & pwsh -File "$repoRoot/scripts/composer-install.ps1" -Dev
    if ($LASTEXITCODE -ne 0) {
        throw 'composer install falhou.'
    }
}

$failures = @()
$phpCmd = $null

function Add-Failure {
    param([string]$Message)
    $script:failures += $Message
}

function Join-Url {
    param(
        [string]$Base,
        [string]$Path
    )

    return ($Base.TrimEnd('/') + '/' + $Path.TrimStart('/'))
}

function Assert-Contains {
    param(
        [string]$Path,
        [string]$Pattern,
        [string]$Description
    )

    if (-not (Test-Path $Path)) {
        Add-Failure "Arquivo nao encontrado: ${Path} ($Description)"
        return
    }

    $match = Select-String -Path $Path -Pattern $Pattern -SimpleMatch
    if (-not $match) {
        Add-Failure "Nao encontrado em ${Path}: $Description"
    }
}

function Get-CsrfTokenFromHtml {
    param([string]$Html)

    if ([string]::IsNullOrWhiteSpace($Html)) {
        return $null
    }

    $regexA = [regex]::Match($Html, 'name=["'']csrf_token["''][^>]*value=["'']([^"'']+)["'']', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($regexA.Success) {
        return $regexA.Groups[1].Value
    }

    $regexB = [regex]::Match($Html, 'value=["'']([^"'']+)["''][^>]*name=["'']csrf_token["'']', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($regexB.Success) {
        return $regexB.Groups[1].Value
    }

    return $null
}

function Invoke-HttpScenario {
    param(
        [string]$Method,
        [string]$Url,
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session,
        [hashtable]$Form = @{}
    )

    try {
        $invokeParams = @{
            Uri = $Url
            Method = $Method
            WebSession = $Session
            MaximumRedirection = 0
            SkipHttpErrorCheck = $true
            ErrorAction = 'SilentlyContinue'
        }

        if ($Method -ne 'GET' -and $Form.Count -gt 0) {
            $invokeParams['Form'] = $Form
        }

        $response = Invoke-WebRequest @invokeParams
        return [pscustomobject]@{
            Success = $true
            StatusCode = [int]$response.StatusCode
            Location = [string]($response.Headers.Location)
            Content = [string]$response.Content
            Error = ''
        }
    } catch {
        return [pscustomobject]@{
            Success = $false
            StatusCode = 0
            Location = ''
            Content = ''
            Error = $_.Exception.Message
        }
    }
}

function Assert-HttpStatus {
    param(
        [pscustomobject]$Result,
        [int[]]$AllowedStatus,
        [string]$Description
    )

    if (-not $Result.Success) {
        Add-Failure "HTTP falhou em $Description :: $($Result.Error)"
        return
    }

    if ($AllowedStatus -notcontains $Result.StatusCode) {
        Add-Failure "Status inesperado em $Description :: recebido=$($Result.StatusCode), esperado=$($AllowedStatus -join ',')"
    }
}

function Assert-HttpLocationContains {
    param(
        [pscustomobject]$Result,
        [string]$Expected,
        [string]$Description
    )

    if (-not $Result.Success) {
        return
    }

    if ([string]::IsNullOrWhiteSpace($Result.Location)) {
        Add-Failure "Sem cabecalho Location em $Description"
        return
    }

    if ($Result.Location -notlike "*$Expected*") {
        Add-Failure "Redirect inesperado em $Description :: Location=$($Result.Location), esperado conter=$Expected"
    }
}

Write-Host "[1/4] Lint PHP da base ativa..."
$phpOnPath = Get-Command php -ErrorAction SilentlyContinue
if ($phpOnPath) {
    $phpCmd = $phpOnPath.Source
} elseif (Test-Path 'C:\xampp\php\php.exe') {
    $phpCmd = 'C:\xampp\php\php.exe'
} else {
    Add-Failure 'PHP nao encontrado no PATH e fallback C:\xampp\php\php.exe inexistente.'
}

$phpFiles = Get-ChildItem -Path "$repoRoot/app", "$repoRoot/src", "$repoRoot/public", "$repoRoot/config" -Recurse -File -Filter *.php |
    Where-Object { $_.FullName -notmatch '[\\/]vendor[\\/]' }

if ($phpCmd) {
    foreach ($file in $phpFiles) {
        $result = & $phpCmd -l $file.FullName 2>&1
        if ($LASTEXITCODE -ne 0) {
            Add-Failure "Lint falhou: $($file.FullName) :: $result"
        }
    }
}

Write-Host "[2/4] Regras criticas em controllers..."
Assert-Contains -Path "$repoRoot/public/index.php" -Pattern 'ErrorHandler::register()' -Description 'handler global de erros registado'
Assert-Contains -Path "$repoRoot/config/config.php" -Pattern "define('LOG_CHANNEL'" -Description 'LOG_CHANNEL configuravel'
Assert-Contains -Path "$repoRoot/.gitignore" -Pattern 'src/vendor/' -Description 'src/vendor ignorado pelo git'
Assert-Contains -Path "$repoRoot/app/controller/ControllerRequest.php" -Pattern 'public function updateStatus($id, $status = null)' -Description 'updateStatus com status opcional'
Assert-Contains -Path "$repoRoot/app/controller/ControllerRequest.php" -Pattern "`$status = `$_POST['status'] ?? `$status;" -Description 'fallback de status via POST'
Assert-Contains -Path "$repoRoot/app/controller/ControllerProperty.php" -Pattern "if (`$_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate(`$_POST['csrf_token'] ?? ''))" -Description 'approve/reject com POST+CSRF'

Write-Host "[3/4] Regras criticas em views/rotas..."
Assert-Contains -Path "$repoRoot/app/view/dashboard/requests/Main.php" -Pattern 'request/updateStatus/' -Description 'formulario de update status'
Assert-Contains -Path "$repoRoot/app/view/dashboard/requests/Main.php" -Pattern 'method="POST"' -Description 'request actions via POST'
Assert-Contains -Path "$repoRoot/app/view/dashboard/requests/Main.php" -Pattern 'ClassCsrf::field()' -Description 'csrf em request actions'
Assert-Contains -Path "$repoRoot/app/view/property/moderate/Main.php" -Pattern 'property/approve/' -Description 'acao de aprovar imovel'
Assert-Contains -Path "$repoRoot/app/view/property/moderate/Main.php" -Pattern 'property/reject/' -Description 'acao de rejeitar imovel'
Assert-Contains -Path "$repoRoot/app/view/property/moderate/Main.php" -Pattern 'ClassCsrf::field()' -Description 'csrf na moderacao de imovel'
Assert-Contains -Path "$repoRoot/src/classes/ClassRoutes.php" -Pattern 'moderate_users' -Description 'rota snake_case de moderacao'

# CSP must not allow unsafe-inline scripts.
$cspLine = (Select-String -Path "$repoRoot/public/index.php" -Pattern "Content-Security-Policy" -SimpleMatch | Select-Object -First 1).Line
if ($cspLine -and $cspLine -match "script-src[^;]*'unsafe-inline'") {
    Add-Failure "CSP contem 'unsafe-inline' em script-src — remova essa diretiva"
}
if ($cspLine -and -not ($cspLine -match "script-src")) {
    Add-Failure "CSP nao define script-src em public/index.php"
}

# No inline <script> blocks (without src=) allowed in views — JSON-LD is exempt.
$inlineScriptFiles = Get-ChildItem -Path "$repoRoot/app/view" -Recurse -Filter '*.php' |
    Select-String -Pattern '<script\b(?![^>]*\bsrc=)(?![^>]*type\s*=\s*["'']application/ld\+json["''])' -AllMatches |
    Select-Object -ExpandProperty Path -Unique
if ($inlineScriptFiles) {
    foreach ($f in $inlineScriptFiles) {
        Add-Failure "Bloco <script> inline encontrado em: $f"
    }
}

# No inline event-handler attributes (onclick=, onchange=, etc.) in views.
$inlineHandlerFiles = Get-ChildItem -Path "$repoRoot/app/view" -Recurse -Filter '*.php' |
    Select-String -Pattern '\bon[a-z]+\s*=' -AllMatches |
    Select-Object -ExpandProperty Path -Unique
if ($inlineHandlerFiles) {
    foreach ($f in $inlineHandlerFiles) {
        Add-Failure "Atributo de evento inline encontrado em: $f (use data-* + script.js)"
    }
}

Write-Host "[4/4] Cenarios HTTP locais..."
if ($RunHttp) {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    $loginPage = Invoke-HttpScenario -Method 'GET' -Url (Join-Url -Base $BaseUrl -Path 'login') -Session $session
    Assert-HttpStatus -Result $loginPage -AllowedStatus @(200) -Description 'GET /login'

    $csrfToken = $null
    if ($loginPage.Success -and $loginPage.StatusCode -eq 200) {
        $csrfToken = Get-CsrfTokenFromHtml -Html $loginPage.Content
        if ([string]::IsNullOrWhiteSpace($csrfToken)) {
            Add-Failure 'Nao foi possivel extrair csrf_token de /login'
        }
    }

    if (-not [string]::IsNullOrWhiteSpace($csrfToken)) {
        $authPost = Invoke-HttpScenario -Method 'POST' -Url (Join-Url -Base $BaseUrl -Path 'authenticate') -Session $session -Form @{
            csrf_token = $csrfToken
            login = 'smoke.invalid@example.com'
            password = 'invalid-password'
        }
        Assert-HttpStatus -Result $authPost -AllowedStatus @(302) -Description 'POST /authenticate (credencial invalida)'
        Assert-HttpLocationContains -Result $authPost -Expected 'login' -Description 'POST /authenticate (credencial invalida)'
    }

    $updateStatusPost = Invoke-HttpScenario -Method 'POST' -Url (Join-Url -Base $BaseUrl -Path 'request/updateStatus/1') -Session $session -Form @{
        csrf_token = 'invalid'
        status = 'analise'
    }
    Assert-HttpStatus -Result $updateStatusPost -AllowedStatus @(302) -Description 'POST /request/updateStatus/1 (sem auth)'
    Assert-HttpLocationContains -Result $updateStatusPost -Expected 'login' -Description 'POST /request/updateStatus/1 (sem auth)'

    $approvePost = Invoke-HttpScenario -Method 'POST' -Url (Join-Url -Base $BaseUrl -Path 'property/approve/1') -Session $session -Form @{ csrf_token = 'invalid' }
    Assert-HttpStatus -Result $approvePost -AllowedStatus @(302) -Description 'POST /property/approve/1 (sem auth)'
    Assert-HttpLocationContains -Result $approvePost -Expected 'login' -Description 'POST /property/approve/1 (sem auth)'

    $rejectPost = Invoke-HttpScenario -Method 'POST' -Url (Join-Url -Base $BaseUrl -Path 'property/reject/1') -Session $session -Form @{ csrf_token = 'invalid' }
    Assert-HttpStatus -Result $rejectPost -AllowedStatus @(302) -Description 'POST /property/reject/1 (sem auth)'
    Assert-HttpLocationContains -Result $rejectPost -Expected 'login' -Description 'POST /property/reject/1 (sem auth)'

    # Register page must return 200 (publicly accessible).
    $registerPage = Invoke-HttpScenario -Method 'GET' -Url (Join-Url -Base $BaseUrl -Path 'register') -Session $session
    Assert-HttpStatus -Result $registerPage -AllowedStatus @(200) -Description 'GET /register (pagina publica)'
} else {
    Write-Host ' - HTTP smoke desativado (use -RunHttp -BaseUrl http://localhost)'
}

if ($failures.Count -gt 0) {
    Write-Host "`nSMOKE CHECK: FALHOU" -ForegroundColor Red
    $failures | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
    exit 1
}

Write-Host "`nSMOKE CHECK: OK" -ForegroundColor Green
exit 0
