$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot

function Resolve-Executable([string]$label, [string[]]$candidates, [switch]$Optional) {
  foreach ($candidate in $candidates) {
    if ([string]::IsNullOrWhiteSpace($candidate)) { continue }

    if ($candidate -match '[\\/]') {
      if (Test-Path $candidate) { return $candidate }
      continue
    }

    $command = Get-Command $candidate -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($command) { return $command.Source }
  }

  if ($Optional) { return $null }
  throw "$label non trovato nel PATH o nei percorsi noti."
}

function Resolve-ProjectDir([string]$basePath, [string]$preferredName, [string]$markerFile) {
  if (Test-Path (Join-Path $basePath $markerFile)) { return $basePath }

  $preferred = Join-Path $basePath $preferredName
  if (Test-Path (Join-Path $preferred $markerFile)) { return $preferred }

  $candidate = Get-ChildItem -Path $basePath -Directory -ErrorAction SilentlyContinue |
    Where-Object { Test-Path (Join-Path $_.FullName $markerFile) } |
    Select-Object -First 1

  if ($candidate) { return $candidate.FullName }
  throw "Cartella progetto non trovata (marker: $markerFile) in $basePath"
}

function Stop-ProjectProcesses([string]$projectRoot, [string]$nuxtRoot, [string]$caddyConfigPath) {
  $processes = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue
  $nuxtPatterns = @(
    $nuxtRoot,
    'node_modules\@nuxt\cli\bin\nuxi.mjs',
    '@nuxt\cli\dist\dev\index.mjs',
    '--port 3000',
    '--port 3001',
    '--port 3303'
  )

  $processes |
    Where-Object {
      $_.Name -eq 'php.exe' -and
      $_.CommandLine -and
      $_.CommandLine.Contains($projectRoot)
    } |
    ForEach-Object {
      Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
    }

  $processes |
    Where-Object {
      $_.Name -eq 'node.exe' -and
      $_.CommandLine -and
      (
        $_.CommandLine.Contains($nuxtRoot) -or
        ($nuxtPatterns | Where-Object { $_ -and $_.CommandLine -match [regex]::Escape($_) } | Select-Object -First 1)
      )
    } |
    ForEach-Object {
      Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
    }

  if ($caddyConfigPath) {
    $processes |
      Where-Object {
        $_.Name -eq 'caddy.exe' -and
        $_.CommandLine -and
        $_.CommandLine.Contains($caddyConfigPath)
      } |
      ForEach-Object {
        Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
      }
  }
}

function Stop-ProjectPortListeners([int[]]$ports) {
  $connections = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue |
    Where-Object { $ports -contains $_.LocalPort }

  foreach ($connection in $connections) {
    try {
      $process = Get-Process -Id $connection.OwningProcess -ErrorAction SilentlyContinue
      if ($process -and $process.Name -in @('node', 'php', 'caddy')) {
        Stop-Process -Id $connection.OwningProcess -Force -ErrorAction SilentlyContinue
      }
    } catch {}
  }
}

function Test-TcpEndpoint([string]$targetHost, [int]$port) {
  $client = $null
  try {
    $client = New-Object System.Net.Sockets.TcpClient
    $async = $client.BeginConnect($targetHost, $port, $null, $null)
    if (-not $async.AsyncWaitHandle.WaitOne(1500, $false)) { return $false }
    $client.EndConnect($async)
    return $true
  } catch {
    return $false
  } finally {
    if ($client) {
      try { $client.Close() } catch {}
    }
  }
}

function Resolve-ReachableProxyTarget([int]$port, [string[]]$hosts) {
  foreach ($targetHost in ($hosts | Where-Object { $_ } | Select-Object -Unique)) {
    if (Test-TcpEndpoint -targetHost $targetHost -port $port) {
      return "${targetHost}:$port"
    }
  }

  return "127.0.0.1:$port"
}

$laravelDir = Resolve-ProjectDir -basePath $root -preferredName 'apps/api' -markerFile 'artisan'
$nuxtDir = Resolve-ProjectDir -basePath $root -preferredName 'apps/web' -markerFile 'nuxt.config.ts'

$phpCmd = Resolve-Executable 'PHP' @(
  'C:\Users\Feder\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe',
  'php.exe',
  'php'
)
$nodeCmd = Resolve-Executable 'Node' @(
  'C:\Program Files\nodejs\node.exe',
  'node.exe',
  'node'
)
$nodeDir = Split-Path -Parent $nodeCmd
if ($nodeDir -and -not (($env:Path -split ';') -contains $nodeDir)) {
  $env:Path = "$nodeDir;$env:Path"
}
$composerCmd = Resolve-Executable 'Composer' @(
  'C:\composer\composer.bat',
  'composer.bat',
  'composer'
)
$npmCmd = Resolve-Executable 'npm' @(
  'C:\Program Files\nodejs\npm.cmd',
  'npm.cmd',
  'npm'
)
$caddyCmd = Resolve-Executable 'Caddy' @(
  'C:\Users\Feder\AppData\Local\Microsoft\WinGet\Links\caddy.exe',
  'caddy.exe',
  'caddy'
) -Optional

$caddyFile = $null
if ($env:CADDYFILE_OVERRIDE -and (Test-Path $env:CADDYFILE_OVERRIDE)) {
  $caddyFile = $env:CADDYFILE_OVERRIDE
} else {
  $caddyCandidates = @(
    (Join-Path $root 'infra\caddy\Caddyfile'),
    (Join-Path $root 'Caddyfile'),
    (Join-Path $root 'Caddyfile.example')
  )
  $caddyFile = $caddyCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
}

$env:NUXT_PUBLIC_API_BASE = if ($env:NUXT_PUBLIC_API_BASE) { $env:NUXT_PUBLIC_API_BASE } else { 'http://127.0.0.1:8787' }

if (-not (Test-Path (Join-Path $laravelDir 'vendor\autoload.php'))) {
  Push-Location $laravelDir
  try {
    & $composerCmd install --no-interaction --prefer-dist --no-dev
  } catch {
    & $composerCmd install --no-interaction --prefer-dist --no-dev --ignore-platform-req=php
  }
  Pop-Location
}

$envFile = Join-Path $laravelDir '.env'
if (-not (Test-Path $envFile) -and (Test-Path (Join-Path $laravelDir '.env.example'))) {
  Copy-Item (Join-Path $laravelDir '.env.example') $envFile -Force
}

$dbPath = Join-Path $laravelDir 'database\database.sqlite'
if (-not (Test-Path $dbPath)) { New-Item -ItemType File -Path $dbPath -Force | Out-Null }

if (Test-Path $envFile) {
  $envContent = Get-Content $envFile -Raw
  if ($envContent -match '(?m)^DB_CONNECTION=') {
    $envContent = [regex]::Replace($envContent, '(?m)^DB_CONNECTION=.*$', 'DB_CONNECTION=sqlite')
  } else {
    $envContent += "`nDB_CONNECTION=sqlite"
  }

  # Non fissiamo DB_DATABASE con un path assoluto: Laravel sa gia' risolvere
  # database_path('database.sqlite') sia in WSL sia con php.exe su Windows.
  $envContent = [regex]::Replace($envContent, '(?m)^DB_DATABASE=.*(?:\r?\n)?', '')

  if ($envContent -match '(?m)^SESSION_DRIVER=') {
    $envContent = [regex]::Replace($envContent, '(?m)^SESSION_DRIVER=.*$', 'SESSION_DRIVER=file')
  } else {
    $envContent += "`nSESSION_DRIVER=file"
  }

  if ($envContent -match '(?m)^QUEUE_CONNECTION=') {
    $envContent = [regex]::Replace($envContent, '(?m)^QUEUE_CONNECTION=.*$', 'QUEUE_CONNECTION=sync')
  } else {
    $envContent += "`nQUEUE_CONNECTION=sync"
  }

  if ($envContent -match '(?m)^CACHE_STORE=') {
    $envContent = [regex]::Replace($envContent, '(?m)^CACHE_STORE=.*$', 'CACHE_STORE=file')
  } else {
    $envContent += "`nCACHE_STORE=file"
  }

  $hostCandidates = @()
  $existingStateful = @()
  $existingCorsOrigins = @()

  $statefulMatch = [regex]::Match($envContent, '(?m)^SANCTUM_STATEFUL_DOMAINS=(.*)$')
  if ($statefulMatch.Success) {
    $existingStateful = $statefulMatch.Groups[1].Value.Split(',') | ForEach-Object { $_.Trim() } | Where-Object { $_ }
  }

  $corsMatch = [regex]::Match($envContent, '(?m)^CORS_ALLOWED_ORIGINS=(.*)$')
  if ($corsMatch.Success) {
    $existingCorsOrigins = $corsMatch.Groups[1].Value.Split(',') | ForEach-Object { $_.Trim() } | Where-Object { $_ }
  }

  if ($env:WSL_HOST_GATEWAY) {
    $hostCandidates += $env:WSL_HOST_GATEWAY
  }

  try {
    $hostCandidates += Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
      Where-Object { $_.IPAddress -and $_.IPAddress -notmatch '^(127|169\.254)\.' } |
      Select-Object -ExpandProperty IPAddress
  } catch {}

  $statefulItems = @(
    $existingStateful
    'localhost',
    '127.0.0.1',
    'localhost:8787',
    '127.0.0.1:8787',
    'localhost:3001',
    '127.0.0.1:3001',
    'localhost:8000',
    '127.0.0.1:8000',
    '*.trycloudflare.com'
  )

  $corsOrigins = @(
    $existingCorsOrigins
    'http://127.0.0.1:8787',
    'http://localhost:8787',
    'http://127.0.0.1:3001',
    'http://localhost:3001',
    'http://127.0.0.1:8000',
    'http://localhost:8000'
  )

  foreach ($ip in ($hostCandidates | Where-Object { $_ -and $_ -ne '0.0.0.0' -and $_ -ne '127.0.0.1' } | Select-Object -Unique)) {
    $statefulItems += @($ip, "${ip}:8787", "${ip}:3001", "${ip}:8000")
    $corsOrigins += @("http://${ip}:8787", "http://${ip}:3001", "http://${ip}:8000")
  }

  $statefulDomains = ($statefulItems | Where-Object { $_ } | Select-Object -Unique) -join ','
  $allowedOrigins = ($corsOrigins | Where-Object { $_ } | Select-Object -Unique) -join ','

  if ($envContent -match '(?m)^SANCTUM_STATEFUL_DOMAINS=') {
    $envContent = [regex]::Replace($envContent, '(?m)^SANCTUM_STATEFUL_DOMAINS=.*$', "SANCTUM_STATEFUL_DOMAINS=$statefulDomains")
  } else {
    $envContent += "`nSANCTUM_STATEFUL_DOMAINS=$statefulDomains"
  }

  if ($envContent -match '(?m)^CORS_ALLOWED_ORIGINS=') {
    $envContent = [regex]::Replace($envContent, '(?m)^CORS_ALLOWED_ORIGINS=.*$', "CORS_ALLOWED_ORIGINS=$allowedOrigins")
  } else {
    $envContent += "`nCORS_ALLOWED_ORIGINS=$allowedOrigins"
  }

  if ($envContent -match '(?m)^APP_FRONTEND_URL=') {
    $envContent = [regex]::Replace($envContent, '(?m)^APP_FRONTEND_URL=.*$', 'APP_FRONTEND_URL=http://127.0.0.1:8787')
  } else {
    $envContent += "`nAPP_FRONTEND_URL=http://127.0.0.1:8787"
  }

  Set-Content -Path $envFile -Value $envContent -NoNewline

  Push-Location $laravelDir
  if (-not ((Get-Content $envFile -Raw) -match 'APP_KEY=base64:')) {
    & $phpCmd artisan key:generate --force | Out-Null
  }
  try { & $phpCmd artisan migrate --force | Out-Null } catch {}
  try { & $phpCmd artisan db:seed --class=Database\Seeders\DatabaseSeeder --force | Out-Null } catch {}
  try { & $phpCmd artisan storage:link | Out-Null } catch {}
  Pop-Location
}

if (-not $hostCandidates) {
  $hostCandidates = @()
  if ($env:WSL_HOST_GATEWAY) {
    $hostCandidates += $env:WSL_HOST_GATEWAY
  }
}

if (-not (Test-Path (Join-Path $nuxtDir 'node_modules'))) {
  Push-Location $nuxtDir
  & $npmCmd install
  Pop-Location
}

$laravelLog = Join-Path $env:TEMP 'laravel.log'
$laravelErrLog = Join-Path $env:TEMP 'laravel.err.log'
$nuxtLog = Join-Path $env:TEMP 'nuxt.log'
$nuxtErrLog = Join-Path $env:TEMP 'nuxt.err.log'
$caddyLog = Join-Path $env:TEMP 'caddy.log'
$caddyErrLog = Join-Path $env:TEMP 'caddy.err.log'

Stop-ProjectProcesses -projectRoot $laravelDir -nuxtRoot $nuxtDir -caddyConfigPath $caddyFile
Stop-ProjectPortListeners -ports @(3000, 3001, 3303, 8000, 8787)
Start-Sleep -Seconds 1

foreach ($logPath in @($laravelLog, $laravelErrLog, $nuxtLog, $nuxtErrLog, $caddyLog, $caddyErrLog)) {
  if (Test-Path $logPath) {
    Remove-Item $logPath -Force -ErrorAction SilentlyContinue
  }
}

Start-Process -FilePath $phpCmd `
  -WorkingDirectory $laravelDir `
  -ArgumentList 'artisan','serve','--host','0.0.0.0','--port','8000' `
  -WindowStyle Minimized `
  -RedirectStandardOutput $laravelLog `
  -RedirectStandardError $laravelErrLog

Start-Process -FilePath $nodeCmd `
  -WorkingDirectory $nuxtDir `
  -ArgumentList '--max-old-space-size=6144','.\node_modules\@nuxt\cli\bin\nuxi.mjs','dev','--host','127.0.0.1','--port','3001' `
  -WindowStyle Minimized `
  -RedirectStandardOutput $nuxtLog `
  -RedirectStandardError $nuxtErrLog

Start-Sleep -Seconds 2

$proxyHostCandidates = @()
$proxyHostCandidates += $hostCandidates | Where-Object { $_ -and $_ -ne '0.0.0.0' }
$proxyHostCandidates += @('localhost', '127.0.0.1')
$env:SF_BACKEND_PROXY_TARGET = Resolve-ReachableProxyTarget -port 8000 -hosts $proxyHostCandidates
$env:SF_FRONTEND_PROXY_TARGET = Resolve-ReachableProxyTarget -port 3001 -hosts $proxyHostCandidates

if ($caddyCmd) {
  Start-Process -FilePath $caddyCmd `
    -WorkingDirectory $root `
    -ArgumentList 'run','--config',$caddyFile `
    -WindowStyle Minimized `
    -RedirectStandardOutput $caddyLog `
    -RedirectStandardError $caddyErrLog
  Write-Output 'OK Apri: http://127.0.0.1:8787'
  Write-Output "OK Proxy frontend Caddy -> $($env:SF_FRONTEND_PROXY_TARGET)"
  Write-Output "OK Proxy backend Caddy -> $($env:SF_BACKEND_PROXY_TARGET)"
} else {
  Write-Output 'WARN Caddy non trovato. Apri: http://127.0.0.1:3001 (Nuxt)'
}

Write-Output "INFO Root progetto: $root"
Write-Output "INFO Frontend dir: $nuxtDir"
Write-Output "INFO Backend dir: $laravelDir"
Write-Output "INFO Launcher frontend: node.exe diretto"
Write-Output "INFO Log frontend: $nuxtLog"
Write-Output "INFO Log frontend err: $nuxtErrLog"
Write-Output "INFO Log backend: $laravelLog"
Write-Output "INFO Log backend err: $laravelErrLog"
Write-Output "INFO Log caddy: $caddyLog"
Write-Output "INFO Log caddy err: $caddyErrLog"
