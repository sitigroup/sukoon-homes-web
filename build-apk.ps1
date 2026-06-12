# Quick release build from cursr (after fix-build-clean.ps1 if needed).
$ErrorActionPreference = "Continue"
$log = "C:\Users\Sukoon\cursr\build-apk-result.txt"
function Log($m) { Add-Content -Path $log -Value $m -Encoding utf8 }
"" | Set-Content $log -Encoding utf8
Log "BUILD_START $(Get-Date -Format o)"

$flutter = "G:\develop\flutter\bin\flutter.bat"
$env:PUB_CACHE = "G:\develop\pub-cache"
$env:ANDROID_HOME = "G:\develop\Android\Sdk"
$env:JAVA_HOME = "G:\develop\Android\Android Studio\jbr"
Set-Location "C:\Users\Sukoon\cursr\ebroker-sukoon-app"

& $flutter build apk --release 2>&1 | ForEach-Object { Log $_ }
$apk = "build\app\outputs\flutter-apk\app-release.apk"
$out = "C:\Users\Sukoon\cursr\Sukoon-Homes-1.4.2-85-FINAL.apk"
if ((Test-Path $apk) -and $LASTEXITCODE -eq 0) {
  Copy-Item $apk $out -Force
  Log "BUILD_OK size=$((Get-Item $out).Length)"
} else {
  Log "BUILD_FAIL exit=$LASTEXITCODE — try: build-final-release.ps1"
}
