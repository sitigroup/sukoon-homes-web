# Fix Gradle/Kotlin cache errors after cross-drive (C: project + G: pub-cache) builds.
$ErrorActionPreference = "Continue"
$app = "C:\Users\Sukoon\cursr\ebroker-sukoon-app"
$flutter = "G:\develop\flutter\bin\flutter.bat"
if (-not (Test-Path $flutter)) { Write-Host "Flutter not found at $flutter"; exit 1 }

$env:PUB_CACHE = "G:\develop\pub-cache"
$env:ANDROID_HOME = "G:\develop\Android\Sdk"
$env:JAVA_HOME = "G:\develop\Android\Android Studio\jbr"

Set-Location $app
Write-Host "Stopping Gradle daemons..."
& "$app\android\gradlew.bat" --stop 2>$null

Write-Host "Removing build caches..."
@(
  "$app\build",
  "$app\android\.gradle",
  "$app\android\app\build",
  "$app\android\.kotlin"
) | ForEach-Object {
  if (Test-Path $_) {
    Remove-Item $_ -Recurse -Force -ErrorAction SilentlyContinue
    Write-Host "  removed $_"
  }
}

Write-Host "flutter clean..."
& $flutter clean
Write-Host "Done. Run: flutter run   OR   flutter build apk --release"
