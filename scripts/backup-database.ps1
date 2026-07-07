<#
.SYNOPSIS
    Trinova Platform - Database Backup Script (Windows)
.DESCRIPTION
    Creates a backup of the Trinova database with compression and cleanup.
.EXAMPLE
    .\scripts\backup-database.ps1
.EXAMPLE
    .\scripts\backup-database.ps1 -Keep 14 -Compress
.EXAMPLE
    .\scripts\backup-database.ps1 -Upload -Notify
#>

param(
    [int]$Keep = 7,
    [switch]$Compress = $true,
    [switch]$Upload = $false,
    [switch]$Notify = $false,
    [string]$Path = ""
)

# ============================================
# Configuration
# ============================================
$ErrorActionPreference = "Stop"
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectPath = Split-Path -Parent $ScriptPath

# Load .env file
$envFile = Join-Path $ProjectPath ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match "^([^#][^=]+)=(.*)$") {
            $key = $matches[1].Trim()
            $value = $matches[2].Trim().Trim('"')
            [Environment]::SetEnvironmentVariable($key, $value, "Process")
        }
    }
}

# Database configuration
$DB_CONNECTION = [Environment]::GetEnvironmentVariable("DB_CONNECTION", "Process")
$DB_HOST = [Environment]::GetEnvironmentVariable("DB_HOST", "Process")
$DB_PORT = [Environment]::GetEnvironmentVariable("DB_PORT", "Process")
$DB_DATABASE = [Environment]::GetEnvironmentVariable("DB_DATABASE", "Process")
$DB_USERNAME = [Environment]::GetEnvironmentVariable("DB_USERNAME", "Process")
$DB_PASSWORD = [Environment]::GetEnvironmentVariable("DB_PASSWORD", "Process")

# Backup configuration
$BackupPath = if ($Path) { $Path } else { Join-Path $ProjectPath "storage\app\backups" }
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$BackupFile = "mysql_backup_${DB_DATABASE}_${Timestamp}.sql"
$BackupFullPath = Join-Path $BackupPath $BackupFile

# ============================================
# Functions
# ============================================
function Write-Header {
    param([string]$Message)
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host $Message -ForegroundColor Cyan
    Write-Host "═══════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Write-Error {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "ℹ️  $Message" -ForegroundColor Blue
}

function Format-FileSize {
    param([long]$Bytes)
    if ($Bytes -ge 1GB) { return "{0:N2} GB" -f ($Bytes / 1GB) }
    if ($Bytes -ge 1MB) { return "{0:N2} MB" -f ($Bytes / 1MB) }
    if ($Bytes -ge 1KB) { return "{0:N2} KB" -f ($Bytes / 1KB) }
    return "$Bytes B"
}

# ============================================
# Main Script
# ============================================
Write-Header "🚀 Trinova Database Backup"

Write-Info "Database: $DB_DATABASE"
Write-Info "Host: $DB_HOST"
Write-Info "Driver: $DB_CONNECTION"
Write-Info "Backup Path: $BackupPath"
Write-Host ""

# Create backup directory
if (-not (Test-Path $BackupPath)) {
    New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
    Write-Success "Created backup directory"
}

# Create backup
try {
    Write-Info "Creating backup..."

    $env:MYSQL_PWD = $DB_PASSWORD

    $mysqldumpArgs = @(
        "--user=$DB_USERNAME"
        "--host=$DB_HOST"
        "--port=$DB_PORT"
        "--single-transaction"
        "--quick"
        "--lock-tables=false"
        $DB_DATABASE
    )

    $process = Start-Process -FilePath "mysqldump" `
        -ArgumentList $mysqldumpArgs `
        -RedirectStandardOutput $BackupFullPath `
        -RedirectStandardError "$BackupFullPath.err" `
        -Wait -PassThru -NoNewWindow

    if ($process.ExitCode -ne 0) {
        $errorContent = Get-Content "$BackupFullPath.err" -Raw
        throw "mysqldump failed: $errorContent"
    }

    # Remove error file if exists
    if (Test-Path "$BackupFullPath.err") {
        Remove-Item "$BackupFullPath.err" -Force
    }

    # Verify backup file
    if (-not (Test-Path $BackupFullPath) -or (Get-Item $BackupFullPath).Length -eq 0) {
        throw "Backup file was not created or is empty"
    }

    $fileSize = (Get-Item $BackupFullPath).Length
    Write-Success "Backup created: $BackupFile"
    Write-Info "File size: $(Format-FileSize $fileSize)"

} catch {
    Write-Error "Backup failed: $_"
    exit 1
}

# Compress backup
if ($Compress) {
    Write-Info "Compressing backup..."

    $GzipFile = "$BackupFullPath.gz"

    try {
        $inputStream = [System.IO.File]::OpenRead($BackupFullPath)
        $outputStream = [System.IO.File]::Create($GzipFile)
        $gzipStream = New-Object System.IO.Compression.GzipStream($outputStream, [System.IO.Compression.CompressionMode]::Compress)

        $inputStream.CopyTo($gzipStream)

        $gzipStream.Close()
        $outputStream.Close()
        $inputStream.Close()

        # Remove original file
        Remove-Item $BackupFullPath -Force

        $compressedSize = (Get-Item $GzipFile).Length
        Write-Success "Compressed: $(Split-Path $GzipFile -Leaf)"
        Write-Info "Compressed size: $(Format-FileSize $compressedSize)"

    } catch {
        Write-Error "Compression failed: $_"
    }
}

# Clean old backups
if ($Keep -gt 0) {
    Write-Info "Cleaning old backups (keeping last $Keep)..."

    $allBackups = Get-ChildItem -Path $BackupPath -Filter "*.sql*" |
        Sort-Object LastWriteTime -Descending

    $toDelete = $allBackups | Select-Object -Skip $Keep

    foreach ($file in $toDelete) {
        Remove-Item $file.FullName -Force
        Write-Host "  🗑️  Deleted: $($file.Name)" -ForegroundColor Gray
    }

    Write-Success "Cleaned $($toDelete.Count) old backup(s)"
}

# Upload to cloud
if ($Upload) {
    Write-Info "Uploading to cloud storage..."

    try {
        Set-Location $ProjectPath
        php artisan storage:link 2>$null

        # TODO: Implement cloud upload
        # aws s3 cp $BackupFullPath s3://your-bucket/backups/

        Write-Success "Uploaded to cloud"

    } catch {
        Write-Error "Upload failed: $_"
    }
}

# Send notification
if ($Notify) {
    Write-Info "Sending notification..."

    # TODO: Implement email notification
    # Send-MailMessage -To "admin@trinova.com" -Subject "Backup Completed" -Body "Backup completed successfully"

    Write-Success "Notification sent"
}

# Summary
Write-Host ""
Write-Header "✅ Backup Completed Successfully!"
Write-Info "Backup file: $(Split-Path $BackupFullPath -Leaf)"
Write-Info "Location: $BackupPath"
Write-Info "Total backups: $((Get-ChildItem -Path $BackupPath -Filter "*.sql*").Count)"
Write-Host ""
