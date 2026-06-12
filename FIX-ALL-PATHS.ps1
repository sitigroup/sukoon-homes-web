# One-time / repeat fix: replace old "This PC" and H:\ paths in project config files.
# Run: powershell -ExecutionPolicy Bypass -File "C:\Users\Sukoon\cursr\FIX-ALL-PATHS.ps1"

$ErrorActionPreference = "Stop"
$roots = @(
    "C:\Users\Sukoon\cursr",
    "G:\develop\ebroker-sukoon-app"
) | Where-Object { Test-Path $_ }

$replacements = @(
    @{ Old = 'C:\Users\This PC\cursr'; New = 'C:\Users\Sukoon\cursr' },
    @{ Old = 'C:/Users/This PC/cursr'; New = 'C:/Users/Sukoon/cursr' },
    @{ Old = 'C:\Users\This PC\flutter'; New = 'G:\develop\flutter' },
    @{ Old = 'C:/Users/This PC/flutter'; New = 'G:/develop/flutter' },
    @{ Old = 'C:\\Users\\This PC\\cursr'; New = 'C:\\Users\\Sukoon\\cursr' },
    @{ Old = 'C:\\Users\\This PC\\flutter'; New = 'G:\\develop\\flutter' },
    @{ Old = 'H:\develop\'; New = 'G:\develop\' },
    @{ Old = 'H:/develop/'; New = 'G:/develop/' },
    @{ Old = 'H:\\develop\\'; New = 'G:\\develop\\' }
)

$extensions = @(
    '.md', '.mdc', '.json', '.properties', '.gradle', '.bat', '.ps1', '.cmd',
    '.xml', '.yaml', '.yml', '.env', '.example', '.txt', '.code-workspace'
)
$skipDirNames = @(
    'node_modules', 'vendor', '.git', 'build', '.dart_tool', '.gradle',
    'Pods', '.idea\caches', 'intermediates', 'cxx'
)

$changed = @()
foreach ($root in $roots) {
    Write-Host "`nScanning: $root"
    Get-ChildItem -Path $root -Recurse -File -ErrorAction SilentlyContinue | ForEach-Object {
        $rel = $_.FullName.Substring($root.Length)
        foreach ($skip in $skipDirNames) {
            if ($rel -match [regex]::Escape("\$skip\") -or $rel -match [regex]::Escape("/$skip/")) { return }
        }
        if ($extensions -notcontains $_.Extension) { return }
        try {
            $raw = [System.IO.File]::ReadAllText($_.FullName)
        } catch { return }
        $updated = $raw
        foreach ($r in $replacements) {
            $updated = $updated.Replace($r.Old, $r.New)
        }
        if ($updated -ne $raw) {
            [System.IO.File]::WriteAllText($_.FullName, $updated)
            $changed += $_.FullName
            Write-Host "  fixed: $($_.FullName)"
        }
    }
}

# Junction so old Cursor shortcuts still work
$old = 'C:\Users\This PC\cursr'
$new = 'C:\Users\Sukoon\cursr'
if (-not (Test-Path $old) -and (Test-Path $new)) {
    if (-not (Test-Path 'C:\Users\This PC')) { New-Item -ItemType Directory -Path 'C:\Users\This PC' -Force | Out-Null }
    cmd /c mklink /J `"$old`" `"$new`" 2>&1 | ForEach-Object { Write-Host $_ }
}

Write-Host "`nDone. Files updated: $($changed.Count)"
if ($changed.Count -gt 0) { $changed | Select-Object -Last 20 }
