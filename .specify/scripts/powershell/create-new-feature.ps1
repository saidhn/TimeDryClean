# create-new-feature.ps1
# Creates a new feature branch and specification structure

param(
    [Parameter(Mandatory=$false)]
    [string]$Json,
    
    [Parameter(Mandatory=$false)]
    [int]$Number,
    
    [Parameter(Mandatory=$false)]
    [string]$ShortName,
    
    [Parameter(ValueFromRemainingArguments=$true)]
    [string[]]$FeatureDescription
)

# Parse arguments
$description = if ($FeatureDescription) { $FeatureDescription -join " " } else { $Json }

if ([string]::IsNullOrWhiteSpace($description)) {
    Write-Error "Feature description is required"
    exit 1
}

# Get git root
$gitRoot = git rev-parse --show-toplevel 2>$null
if ($LASTEXITCODE -ne 0) {
    $gitRoot = (Get-Location).Path
}

# Generate short name if not provided
if ([string]::IsNullOrWhiteSpace($ShortName)) {
    # Extract keywords from description
    $words = $description -split '\s+' | Where-Object { $_.Length -gt 3 }
    $ShortName = ($words | Select-Object -First 3) -join '-'
    $ShortName = $ShortName.ToLower() -replace '[^a-z0-9-]', ''
}

# Determine number if not provided
if ($Number -eq 0) {
    # Check remote branches
    $remoteBranches = git ls-remote --heads origin 2>$null | Select-String "refs/heads/(\d+)-$ShortName$"
    $remoteNumbers = $remoteBranches | ForEach-Object { 
        if ($_ -match "refs/heads/(\d+)-$ShortName$") { [int]$matches[1] }
    }
    
    # Check local branches
    $localBranches = git branch 2>$null | Select-String "^\s*\*?\s*(\d+)-$ShortName$"
    $localNumbers = $localBranches | ForEach-Object {
        if ($_ -match "^\s*\*?\s*(\d+)-$ShortName$") { [int]$matches[1] }
    }
    
    # Check specs directories
    $specsPath = Join-Path $gitRoot "specs"
    $specDirs = @()
    if (Test-Path $specsPath) {
        $specDirs = Get-ChildItem -Path $specsPath -Directory | Where-Object {
            $_.Name -match "^(\d+)-$ShortName$"
        } | ForEach-Object {
            if ($_.Name -match "^(\d+)-$ShortName$") { [int]$matches[1] }
        }
    }
    
    # Find highest number
    $allNumbers = @($remoteNumbers) + @($localNumbers) + @($specDirs)
    $maxNumber = if ($allNumbers.Count -gt 0) { ($allNumbers | Measure-Object -Maximum).Maximum } else { 0 }
    $Number = $maxNumber + 1
}

# Create branch name
$branchName = "$Number-$ShortName"

# Create feature directory structure
$featureDir = Join-Path $gitRoot "specs" $branchName
$checklistsDir = Join-Path $featureDir "checklists"
$artifactsDir = Join-Path $featureDir "artifacts"

New-Item -ItemType Directory -Force -Path $featureDir | Out-Null
New-Item -ItemType Directory -Force -Path $checklistsDir | Out-Null
New-Item -ItemType Directory -Force -Path $artifactsDir | Out-Null

# Create spec file path
$specFile = Join-Path $featureDir "spec.md"

# Create or checkout branch
$currentBranch = git rev-parse --abbrev-ref HEAD 2>$null
git checkout -b $branchName 2>$null

if ($LASTEXITCODE -ne 0) {
    # Branch might already exist
    git checkout $branchName 2>$null
}

# Output JSON for consumption by workflow
$output = @{
    BRANCH_NAME = $branchName
    SPEC_FILE = $specFile
    FEATURE_DIR = $featureDir
    CHECKLISTS_DIR = $checklistsDir
    ARTIFACTS_DIR = $artifactsDir
    NUMBER = $Number
    SHORT_NAME = $ShortName
    DESCRIPTION = $description
} | ConvertTo-Json -Compress

Write-Output $output

exit 0
