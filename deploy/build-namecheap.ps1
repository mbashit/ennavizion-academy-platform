# Builds the two upload packages for Namecheap shared hosting (see DEPLOY.md):
#   dist\ennavizion-site.zip     -> extract into public_html
#   dist\ennavizion-backend.zip  -> extract into ~/ennavizion-backend
#
# The backend zip ships with vendor/ already installed (production dependencies only), so
# the server never needs to run Composer. It never contains .env, the local SQLite
# database, logs, sessions or uploaded files.
#
# Run from anywhere:  powershell -ExecutionPolicy Bypass -File deploy\build-namecheap.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$dist = Join-Path $root "dist"
$stage = Join-Path ([IO.Path]::GetTempPath()) "ennavizion-build"

Remove-Item $dist, $stage -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force $dist, $stage | Out-Null

# --- Static site -------------------------------------------------------------------------
Write-Host "Packaging the website..."
# tar (bundled with Windows 10+) writes forward-slash zip paths, which Linux servers need;
# PowerShell 5's Compress-Archive writes backslashes and breaks folders on extraction.
tar -a -c -f (Join-Path $dist "ennavizion-site.zip") -C (Join-Path $root "ennavizion") --exclude README.md .
if ($LASTEXITCODE) { throw "Zipping the website failed." }

# --- Backend -----------------------------------------------------------------------------
Write-Host "Copying the backend..."
$backend = Join-Path $stage "backend"
# /XJ skips junctions such as public\storage (recreated on the server by storage:link).
robocopy (Join-Path $root "backend") $backend /E /XJ /NFL /NDL /NJH /NJS /NP `
    /XD vendor node_modules .git tests `
    /XF .env .env.backup .env.production database.sqlite *.log .phpunit.result.cache | Out-Null
if ($LASTEXITCODE -ge 8) { throw "Copying the backend failed (robocopy exit $LASTEXITCODE)." }

# Local runtime files: keep the folders (Laravel needs them) but none of their contents.
foreach ($dir in "storage\logs", "storage\framework\cache\data", "storage\framework\sessions",
                 "storage\framework\views", "storage\app\public", "storage\app\private") {
    Get-ChildItem (Join-Path $backend $dir) -Force -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Recurse -Force
}
Remove-Item (Join-Path $backend "bootstrap\cache\*.php") -Force -ErrorAction SilentlyContinue

Write-Host "Installing production dependencies..."
composer install --working-dir="$backend" --no-dev --optimize-autoloader --no-interaction --quiet
if ($LASTEXITCODE) { throw "composer install failed." }

Write-Host "Packaging the backend..."
tar -a -c -f (Join-Path $dist "ennavizion-backend.zip") -C $backend .
if ($LASTEXITCODE) { throw "Zipping the backend failed." }

Remove-Item $stage -Recurse -Force
Get-ChildItem $dist | ForEach-Object { "{0,-28} {1,8:N1} MB" -f $_.Name, ($_.Length / 1MB) }
Write-Host "Done. Upload steps: DEPLOY.md"
