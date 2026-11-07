# PowerShell Script to Package Northwind BMS for Project Submission
$sourceDir = "d:\wachira"
$zipPath = "d:\wachira\Northwind_BMS_Submission.zip"

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Write-Host "Packaging Northwind BMS project for submission..." -ForegroundColor Cyan

$exclude = @(
    "Northwind_BMS_Submission.zip",
    ".git",
    "inspect_*.js",
    "extract_*.js",
    "*.log"
)

$tempFolder = Join-Path $env:TEMP "Northwind_BMS_Package"
if (Test-Path $tempFolder) {
    Remove-Item $tempFolder -Recurse -Force
}
New-Item -ItemType Directory -Path $tempFolder | Out-Null

Get-ChildItem -Path $sourceDir | Where-Object {
    $name = $_.Name
    $skip = $false
    foreach ($ex in $exclude) {
        if ($name -like $ex) { $skip = $true; break }
    }
    -not $skip
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $tempFolder -Recurse -Force
}

Compress-Archive -Path "$tempFolder\*" -DestinationPath $zipPath -Force
Remove-Item $tempFolder -Recurse -Force

Write-Host "Created submission ZIP successfully:" -ForegroundColor Green
Write-Host "   -> $zipPath" -ForegroundColor Yellow
$size = [math]::Round(((Get-Item $zipPath).Length / 1MB), 2)
Write-Host "   -> Size: $size MB" -ForegroundColor Gray
