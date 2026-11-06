param([switch]$Browser)

$ErrorActionPreference = 'Stop'
$repository = Split-Path -Parent $PSScriptRoot
Push-Location -LiteralPath $repository
try {
    & docker compose config --quiet
    if ($LASTEXITCODE -ne 0) { throw 'Compose configuration failed.' }
    & docker compose exec -T backend composer check
    if ($LASTEXITCODE -ne 0) { throw 'Backend checks failed.' }
    & docker compose exec -T backend composer smoke
    if ($LASTEXITCODE -ne 0) { throw 'Backend smoke checks failed.' }
    & docker compose exec -T backend composer test
    if ($LASTEXITCODE -ne 0) { throw 'Backend behavior tests failed.' }
    & docker build --target checks -f docker/web.Dockerfile .
    if ($LASTEXITCODE -ne 0) { throw 'Frontend build, tests or dependency audit failed.' }
    if ($Browser) {
        & node scripts/browser-check.mjs
        if ($LASTEXITCODE -ne 0) { throw 'Browser checks failed. Install web dependencies and Chromium first.' }
    } else {
        Write-Host 'Docker checks passed. Browser checks were not requested; use -Browser after installing the browser test dependencies.'
    }
} finally {
    Pop-Location
}
