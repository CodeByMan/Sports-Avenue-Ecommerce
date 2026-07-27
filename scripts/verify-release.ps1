$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))

foreach ($command in @('composer', 'php', 'npm')) {
    if (-not (Get-Command $command -ErrorAction SilentlyContinue)) {
        throw "$command is required."
    }
}

$composerVersionOutput = composer --version --no-ansi
if ($composerVersionOutput -notmatch 'Composer version (?<version>\d+\.\d+\.\d+)') {
    throw "Unable to determine the Composer version."
}

if ([version]$Matches.version -lt [version]'2.8.0') {
    throw "Composer 2.8 or newer is required; found $($Matches.version)."
}

$verifyDatabase = Join-Path (Get-Location) 'database/release-verification.sqlite'

try {
    composer validate --strict
    composer install --no-interaction --prefer-dist
    composer check-platform-reqs
    composer audit --locked --abandoned=report
    php artisan about

    New-Item -ItemType File -Path $verifyDatabase -Force | Out-Null
    $env:APP_ENV = 'testing'
    $env:APP_DEBUG = 'false'
    $env:APP_KEY = (php artisan key:generate --show --no-ansi).Trim()
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $verifyDatabase
    $env:DB_FOREIGN_KEYS = 'true'
    $env:CACHE_STORE = 'array'
    $env:SESSION_DRIVER = 'array'
    $env:QUEUE_CONNECTION = 'sync'
    $env:MAIL_MAILER = 'array'
    $env:ALLOW_DEMO_SEEDING = 'true'
    $env:MIDTRANS_SERVER_KEY = 'release-verification-server-key'
    $env:MIDTRANS_CLIENT_KEY = 'release-verification-client-key'

    php artisan migrate:fresh --seed --force
    vendor/bin/pint --test
    vendor/bin/phpunit --colors=never --do-not-cache-result
    npm ci
    npm audit --audit-level=moderate
    npm run build
    php artisan route:list
    php artisan config:cache
    php artisan event:cache
    php artisan config:clear
    php artisan event:clear

    Write-Host "Release verification completed successfully against disposable SQLite database: $verifyDatabase" -ForegroundColor Green
}
finally {
    if (Test-Path $verifyDatabase) {
        Remove-Item $verifyDatabase -Force
    }

    php artisan config:clear 2>$null | Out-Null
    php artisan event:clear 2>$null | Out-Null
}
