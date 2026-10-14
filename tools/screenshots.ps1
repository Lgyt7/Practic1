# Снимки страниц для проверки вёрстки.
#
# Edge в headless-режиме не даёт вьюпорт меньше ~492 px, поэтому страницы
# открываются внутри <iframe> нужной ширины: медиазапросы внутри фрейма
# считаются по его ширине.
#
# Запуск:
#   php -S 127.0.0.1:8088 -t .
#   powershell -NoProfile -ExecutionPolicy Bypass -File tools/screenshots.ps1
#
# Скриншоты складываются в %TEMP%\vetclinic\shots.

$edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$shots = Join-Path $env:TEMP 'vetclinic\shots'
$base = 'http://127.0.0.1:8088/tools/viewport.html'

if (-not (Test-Path $edge)) {
    Write-Error "Не найден браузер: $edge"
    exit 1
}

New-Item -ItemType Directory -Force -Path $shots | Out-Null

# 'имя' — префикс файла, 'q' — параметры страницы-обёртки, 'h' — высота снимка.
$pages = @(
    @{ n = 'mobile_index';       h = 844;  q = '?page=/index.php&role=guest&w=390&h=844' },
    @{ n = 'mobile_login';       h = 844;  q = '?page=/frontend/login.php&role=guest&w=390&h=844' },
    @{ n = 'mobile_register';    h = 1100; q = '?page=/frontend/register.php&role=guest&w=390&h=1100' },
    @{ n = 'mobile_create';      h = 1700; q = '?page=/frontend/application_create.php&role=user&w=390&h=1700' },
    @{ n = 'mobile_applications'; h = 3400; q = '?page=/frontend/applications.php&role=user&w=390&h=3400' },
    @{ n = 'mobile_admin';       h = 3000; q = '?page=/frontend/admin.php&role=admin&w=390&h=3000' },
    @{ n = 'mobile_admin_search'; h = 2600; q = '?page=/frontend/admin.php%3Fq%3D%D0%91%D0%B0%D1%80%D1%81&role=admin&w=390&h=2600' },
    @{ n = 'desktop_admin';      h = 1500; q = '?page=/frontend/admin.php&role=admin&w=1280&h=1500' },
    @{ n = 'desktop_applications'; h = 1500; q = '?page=/frontend/applications.php&role=user&w=1280&h=1500' },
    @{ n = 'desktop_index';      h = 1600; q = '?page=/index.php&role=guest&w=1280&h=1600' }
)

foreach ($p in $pages) {
    $out = Join-Path $shots ($p.n + '.png')

    if (Test-Path $out) { Remove-Item $out }

    $win = if ($p.q -match 'w=(\d+)') { [int] $Matches[1] + 20 } else { 520 }

    & $edge --headless=new --disable-gpu --hide-scrollbars `
        --window-size=$win,$($p.h + 40) --virtual-time-budget=4000 `
        --screenshot="$out" ($base + $p.q) 2>&1 | Out-Null
}

Get-ChildItem $shots -Filter '*.png' | Select-Object Name, Length
