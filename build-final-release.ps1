# Sukoon Homes final release APK (build from C: source only).
$ErrorActionPreference = "Continue"
$log = "C:\Users\Sukoon\cursr\build-final-release.log"
function Log($m) { Add-Content -Path $log -Value "$(Get-Date -Format o) $m" -Encoding utf8 }

"" | Set-Content $log -Encoding utf8
$flutter = "G:\develop\flutter\bin\flutter.bat"
$app = "C:\Users\Sukoon\cursr\ebroker-sukoon-app"

if (-not (Test-Path $flutter)) { Log "ERROR: Flutter missing"; exit 1 }

$env:PUB_CACHE = "G:\develop\pub-cache"
$env:ANDROID_HOME = "G:\develop\Android\Sdk"
$env:JAVA_HOME = "G:\develop\Android\Android Studio\jbr"

Set-Location $app
Log "flutter pub get"
& $flutter pub get 2>&1 | ForEach-Object { Log $_ }
Log "flutter build apk --release"
& $flutter build apk --release 2>&1 | ForEach-Object { Log $_ }

$apk = "$app\build\app\outputs\flutter-apk\app-release.apk"
$out = "C:\Users\Sukoon\cursr\Sukoon-Homes-1.4.3-88-FINAL.apk"
if ((Test-Path $apk) -and $LASTEXITCODE -eq 0) {
  Copy-Item $apk $out -Force
  Log "BUILD_OK $out size=$((Get-Item $out).Length)"
  exit 0
}
Log "BUILD_FAIL exit=$LASTEXITCODE"
exit 1
