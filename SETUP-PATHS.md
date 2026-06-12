# Sukoon project paths (Windows)

After renaming the PC user from **This PC** to **Sukoon**, use only these paths.

## Folders

| Purpose | Path |
|---------|------|
| Cursor workspace | `C:\Users\Sukoon\cursr` |
| Flutter app (source) | `C:\Users\Sukoon\cursr\ebroker-sukoon-app` |
| Flutter app (build copy) | `G:\develop\ebroker-sukoon-app` |

## Tools (G: drive)

| Tool | Path |
|------|------|
| Flutter | `G:\develop\flutter\bin\flutter.bat` |
| Android SDK | `G:\develop\Android\Sdk` |
| ADB | `G:\develop\Android\Sdk\platform-tools\adb.exe` |
| JDK | `G:\develop\Android\Android Studio\jbr` |
| Pub cache | `G:\develop\pub-cache` |

## Fix old paths in files

```powershell
powershell -ExecutionPolicy Bypass -File "C:\Users\Sukoon\cursr\FIX-ALL-PATHS.ps1"
```

## Cursor

- **File → Open Folder** → `C:\Users\Sukoon\cursr`
- Or open `cursr.code-workspace`

Do **not** open `C:\Users\This PC\cursr` (missing unless junction was created).

## Flutter global config (run once)

```powershell
G:\develop\flutter\bin\flutter.bat config --android-sdk G:\develop\Android\Sdk
```

## Build / run (important)

Pub cache is on **G:** but the app folder may be on **C:**. If Gradle fails with `different roots` or `Could not delete ... caches-jvm`, run:

```powershell
powershell -ExecutionPolicy Bypass -File "C:\Users\Sukoon\cursr\fix-build-clean.ps1"
```

Then build from **G:** (recommended — same drive as pub-cache):

```powershell
cd G:\develop\ebroker-sukoon-app
$env:PUB_CACHE = "G:\develop\pub-cache"
G:\develop\flutter\bin\flutter.bat run
```

Or from `cursr` after clean (kotlin.incremental=false is set in gradle.properties):

```powershell
cd C:\Users\Sukoon\cursr\ebroker-sukoon-app
G:\develop\flutter\bin\flutter.bat run
```

## Build APK

```powershell
powershell -ExecutionPolicy Bypass -File "C:\Users\Sukoon\cursr\build-apk.ps1"
```

Output: `C:\Users\Sukoon\cursr\Sukoon-Homes-1.4.2-84-FIXED.apk`
