# Пересборка backend/database/er_diagram.png
#
# mermaid-cli в системе нет, поэтому диаграмма собирается так:
#   tools/er_diagram.html  →  Edge headless  →  backend/database/er_diagram.png
#
# Источник правды — backend/database/er_diagram.mmd (текстовая версия
# той же схемы). После правки схемы БД обновите оба файла и запустите скрипт.
#
# Запуск:
#   php -S 127.0.0.1:8088 -t .
#   powershell -NoProfile -ExecutionPolicy Bypass -File tools/er_diagram.ps1

$edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$target = Join-Path $PSScriptRoot '..\backend\database\er_diagram.png'

if (-not (Test-Path $edge)) {
    Write-Error "Не найден браузер: $edge"
    exit 1
}

# Ширина страницы зафиксирована в style.css генератора (1180 px),
# окно берётся с запасом на рамку браузера.
& $edge --headless=new --disable-gpu --hide-scrollbars `
    --window-size=1180,1100 --virtual-time-budget=4000 `
    --screenshot="$target" 'http://127.0.0.1:8088/tools/er_diagram.html' 2>&1 | Out-Null

Get-Item $target | Select-Object Name, Length, LastWriteTime