@echo off
echo Fixing Cursor terminal path: "This PC\cursr" -^> Sukoon\cursr
if not exist "C:\Users\Sukoon\cursr" (
  echo ERROR: C:\Users\Sukoon\cursr not found
  pause
  exit /b 1
)
if not exist "C:\Users\This PC" mkdir "C:\Users\This PC"
if exist "C:\Users\This PC\cursr" (
  echo Junction or folder already exists.
  dir "C:\Users\This PC\cursr"
) else (
  mklink /J "C:\Users\This PC\cursr" "C:\Users\Sukoon\cursr"
)
echo.
echo Done. Close Cursor and reopen this folder:
echo   C:\Users\Sukoon\cursr
echo Or open: cursr.code-workspace
pause
