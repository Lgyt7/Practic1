$edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$target = Join-Path $PSScriptRoot '..\backend\database\er_diagram.png'

if (-not (Test-Path $edge)) {
    Write-Error "Не найден браузер: $edge"
    exit 1
}

& $edge --headless=new --disable-gpu --hide-scrollbars `
    --window-size=1180,1100 --virtual-time-budget=4000 `
    --screenshot="$target" 'http://127.0.0.1:8088/tools/er_diagram.html' 2>&1 | Out-Null

Get-Item $target | Select-Object Name, Length, LastWriteTime
