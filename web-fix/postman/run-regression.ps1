# Production-safe Trust Verification API run (Task 11B) — Safe Regression only
param(
    [ValidateSet('Production', 'Local')]
    [string] $Environment = 'Production'
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

$envFile = if ($Environment -eq 'Local') {
    'Sukoon-Local.postman_environment.json'
} else {
    'Sukoon-Production.postman_environment.json'
}

if (-not (Get-Command newman -ErrorAction SilentlyContinue)) {
    Write-Error 'Install Newman: npm install -g newman'
}

$delay = if ($env:TV_REQUEST_DELAY_MS) { $env:TV_REQUEST_DELAY_MS } else { '1500' }

$extra = @(
    '--env-var', 'run_destructive_tests=false'
    '--delay-request', $delay
)
if ($env:TV_CUSTOMER_PASSWORD) {
    $extra += '--env-var', "customer_password=$($env:TV_CUSTOMER_PASSWORD)"
}

Write-Host "Running Safe Regression only (run_destructive_tests=false, delay=${delay}ms)"

& newman run 'Sukoon-Trust-Verification.postman_collection.json' `
    -e $envFile `
    --folder 'Safe Regression' `
    --working-dir $ScriptDir `
    @extra
