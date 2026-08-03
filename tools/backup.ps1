param(
    [string]$DatabaseName = $(if ($env:EMS_DB_NAME) { $env:EMS_DB_NAME } else { 'employee_leave_system' }),
    [string]$DatabaseHost = $(if ($env:EMS_DB_HOST) { $env:EMS_DB_HOST } else { 'localhost' }),
    [string]$DatabaseUser = $(if ($env:EMS_DB_USER) { $env:EMS_DB_USER } else { 'root' }),
    [string]$DatabasePassword = $(if ($env:EMS_DB_PASSWORD) { $env:EMS_DB_PASSWORD } else { '' })
)

$ErrorActionPreference = 'Stop'
$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$backupRoot = [System.IO.Path]::GetFullPath((Join-Path $projectRoot 'storage\backups'))
$uploadsPath = [System.IO.Path]::GetFullPath((Join-Path $projectRoot 'uploads'))
$dumpTool = 'C:\xampp\mysql\bin\mysqldump.exe'

if (-not $backupRoot.StartsWith($projectRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Backup directory resolved outside the project.'
}
if (-not (Test-Path -LiteralPath $dumpTool -PathType Leaf)) {
    throw "mysqldump was not found at $dumpTool"
}

[System.IO.Directory]::CreateDirectory($backupRoot) | Out-Null
$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$databaseFile = Join-Path $backupRoot "ems_database_$stamp.sql"
$uploadsFile = Join-Path $backupRoot "ems_uploads_$stamp.zip"
$manifestFile = Join-Path $backupRoot "ems_backup_$stamp.json"

$dumpArgs = @(
    "--host=$DatabaseHost",
    "--user=$DatabaseUser",
    '--single-transaction',
    '--routines',
    '--triggers',
    '--events',
    '--default-character-set=utf8mb4',
    "--result-file=$databaseFile",
    $DatabaseName
)
if ($DatabasePassword -ne '') {
    $dumpArgs = @("--password=$DatabasePassword") + $dumpArgs
}

& $dumpTool @dumpArgs
if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $databaseFile)) {
    throw 'Database backup failed.'
}

if (Test-Path -LiteralPath $uploadsPath -PathType Container) {
    Compress-Archive -LiteralPath $uploadsPath -DestinationPath $uploadsFile -CompressionLevel Optimal -Force
} else {
    Compress-Archive -LiteralPath $databaseFile -DestinationPath $uploadsFile -CompressionLevel Optimal -Force
}

$artifacts = @($databaseFile, $uploadsFile) | ForEach-Object {
    $item = Get-Item -LiteralPath $_
    $hash = Get-FileHash -LiteralPath $_ -Algorithm SHA256
    [ordered]@{
        file = $item.Name
        bytes = $item.Length
        sha256 = $hash.Hash.ToLowerInvariant()
    }
}

$manifest = [ordered]@{
    created_at = (Get-Date).ToString('o')
    database = $DatabaseName
    host = $DatabaseHost
    artifacts = $artifacts
}
$manifest | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $manifestFile -Encoding UTF8

Write-Output "Backup completed: $backupRoot"
Write-Output "Database: $(Split-Path -Leaf $databaseFile)"
Write-Output "Uploads: $(Split-Path -Leaf $uploadsFile)"
Write-Output "Manifest: $(Split-Path -Leaf $manifestFile)"

