# Pit o Cuixa - Windows scheduled sync (dinahosting Windows hosting)
#
# Entry point for the hosting panel's "Tareas programadas Windows": the panel
# runs cron-sync-windows.bat (same folder), which executes THIS script.
#
# What it does:
#   1. Reads SERVICE_API_TOKEN and SITE_URL from the project .env. The root is
#      found by walking UP from this script's folder until a .env exists, so
#      the pair works whether it lives in <root>\scripts\ or inside <root>\www\
#      (the panel's script picker may only browse the web root).
#   2. POSTs to {SITE_URL}/api/update-menu with "Authorization: Bearer <token>"
#      (same single authentication path used by scripts/cron-sync.php on Linux).
#   3. Appends a timestamped outcome line to data/cron-sync.log (the same log
#      file the CLI cron uses, next to the resolved .env).
#
# Fail-closed (AUTH-2): if SERVICE_API_TOKEN is missing/empty, nothing is sent.
# NOTE: these files contain no secrets - the token only lives in the .env.
#
# Manual test (dev machine): powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\cron-sync-windows.ps1

$ErrorActionPreference = 'Stop'

# --- Locate the project root: walk up from this script's folder until a .env
# --- is found (max 4 levels); fall back to the parent folder.
$startDir = $PSScriptRoot
if (-not $startDir) {
    $startDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
$envFile = $null
$dir     = $startDir
for ($i = 0; $i -lt 4 -and $dir; $i++) {
    $candidate = Join-Path $dir '.env'
    if (Test-Path -LiteralPath $candidate) { $envFile = $candidate; break }
    $dir = Split-Path -Parent $dir
}
if (-not $envFile) {
    $envFile = Join-Path (Split-Path -Parent $startDir) '.env'
}
$rootDir = Split-Path -Parent $envFile
$logFile = Join-Path (Join-Path $rootDir 'data') 'cron-sync.log'

function Write-Log([string]$message) {
    $line = '[' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss') + '] ' + $message
    try { Add-Content -LiteralPath $logFile -Value $line -Encoding UTF8 } catch { }
    Write-Output $line
}

# --- Load .env (same parser rules as Config::load(): trim, skip comments,
# --- strip one pair of surrounding quotes) --------------------------------
$settings = @{}
if (Test-Path -LiteralPath $envFile) {
    foreach ($raw in Get-Content -LiteralPath $envFile) {
        $line = $raw.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { continue }
        $idx = $line.IndexOf('=')
        if ($idx -lt 1) { continue }
        $key = $line.Substring(0, $idx).Trim()
        $val = $line.Substring($idx + 1).Trim()
        if ($val.Length -ge 2 -and ($val[0] -eq '"' -or $val[0] -eq "'") -and $val[$val.Length - 1] -eq $val[0]) {
            $val = $val.Substring(1, $val.Length - 2)
        }
        $settings[$key] = $val
    }
}

$token = ''
if ($settings.ContainsKey('SERVICE_API_TOKEN')) { $token = $settings['SERVICE_API_TOKEN'] }
if ($token -eq '') {
    Write-Log 'ABORT: SERVICE_API_TOKEN missing/empty in .env - sync refused (fail-closed).'
    exit 1
}

$siteUrl = 'https://pitocuixa.es'
if ($settings.ContainsKey('SITE_URL') -and $settings['SITE_URL'] -ne '') {
    $siteUrl = $settings['SITE_URL'].TrimEnd('/')
}
$url = $siteUrl + '/api/update-menu'

try {
    # Windows PowerShell 5.1 may default to legacy TLS; force TLS 1.2+.
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

    $response = Invoke-WebRequest -Uri $url -Method Post -UseBasicParsing `
        -Headers @{ Authorization = ('Bearer ' + $token) } `
        -TimeoutSec 300

    Write-Log ('OK: HTTP ' + [int]$response.StatusCode + ' ' + $response.Content)
    exit 0
} catch {
    $httpCode = ''
    if ($_.Exception.Response) {
        $httpCode = 'HTTP ' + [int]$_.Exception.Response.StatusCode + ' '
    }
    Write-Log ('FAILED: ' + $httpCode + $_.Exception.Message)
    exit 1
}
