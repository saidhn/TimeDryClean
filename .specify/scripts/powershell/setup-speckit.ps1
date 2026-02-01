# setup-speckit.ps1
# Sets up the speckit workflow system for the project

param(
    [Parameter(Mandatory=$false)]
    [switch]$Force
)

Write-Host "Setting up SpecKit workflow system..." -ForegroundColor Cyan

# Get git root
$gitRoot = git rev-parse --show-toplevel 2>$null
if ($LASTEXITCODE -ne 0) {
    $gitRoot = (Get-Location).Path
}

Write-Host "Git root: $gitRoot" -ForegroundColor Gray

# Create directory structure
$directories = @(
    ".specify",
    ".specify/scripts",
    ".specify/scripts/powershell",
    ".specify/templates",
    "specs"
)

foreach ($dir in $directories) {
    $fullPath = Join-Path $gitRoot $dir
    if (-not (Test-Path $fullPath)) {
        New-Item -ItemType Directory -Force -Path $fullPath | Out-Null
        Write-Host "✓ Created directory: $dir" -ForegroundColor Green
    } else {
        Write-Host "✓ Directory exists: $dir" -ForegroundColor Gray
    }
}

# Check for required files
$requiredFiles = @{
    ".specify/scripts/powershell/create-new-feature.ps1" = "Feature creation script"
    ".specify/templates/spec-template.md" = "Specification template"
}

$missingFiles = @()
foreach ($file in $requiredFiles.Keys) {
    $fullPath = Join-Path $gitRoot $file
    if (-not (Test-Path $fullPath)) {
        $missingFiles += $file
        Write-Host "✗ Missing: $file" -ForegroundColor Yellow
    } else {
        Write-Host "✓ Found: $file" -ForegroundColor Green
    }
}

if ($missingFiles.Count -gt 0) {
    Write-Host "`nMissing required files. Please ensure all scripts are in place." -ForegroundColor Yellow
    Write-Host "Missing files:" -ForegroundColor Yellow
    $missingFiles | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
    exit 1
}

# Verify git repository
$isGitRepo = Test-Path (Join-Path $gitRoot ".git")
if (-not $isGitRepo) {
    Write-Host "`n⚠ Warning: Not a git repository. Some features may not work correctly." -ForegroundColor Yellow
}

# Check for .windsurf directory
$windsurfPath = Join-Path $gitRoot ".windsurf"
if (-not (Test-Path $windsurfPath)) {
    New-Item -ItemType Directory -Force -Path $windsurfPath | Out-Null
    Write-Host "✓ Created .windsurf directory" -ForegroundColor Green
}

$workflowsPath = Join-Path $windsurfPath "workflows"
if (-not (Test-Path $workflowsPath)) {
    New-Item -ItemType Directory -Force -Path $workflowsPath | Out-Null
    Write-Host "✓ Created .windsurf/workflows directory" -ForegroundColor Green
}

Write-Host "`n✓ SpecKit setup complete!" -ForegroundColor Green
Write-Host "`nYou can now use the following workflows:" -ForegroundColor Cyan
Write-Host "  /speckit.specify <feature description> - Create a new feature specification" -ForegroundColor White
Write-Host "  /speckit.clarify - Clarify underspecified areas in the spec" -ForegroundColor White
Write-Host "  /speckit.plan - Generate implementation plan" -ForegroundColor White
Write-Host "  /speckit.tasks - Generate actionable tasks" -ForegroundColor White
Write-Host "  /speckit.implement - Execute implementation" -ForegroundColor White

exit 0
