@echo off
chcp 936 >nul
REM ==========================================
REM  Website License Generator (Node.js)
REM ==========================================

echo ==============================
echo      Website License Generator
echo ==============================
echo.

set "NODE_CMD="

REM 1. Try system PATH
where node >nul 2>&1
if %errorlevel% equ 0 (
    for /f "delims=" %%a in ('where node 2^>nul') do set "NODE_CMD=%%a"
    if defined NODE_CMD goto :found
)

REM 2. Try common install paths
if not defined NODE_CMD if exist "C:\Program Files\nodejs\node.exe" set "NODE_CMD=C:\Program Files\nodejs\node.exe"
if not defined NODE_CMD if exist "C:\Program Files (x86)\nodejs\node.exe" set "NODE_CMD=C:\Program Files (x86)\nodejs\node.exe"
if not defined NODE_CMD if exist "D:\nodejs\node.exe" set "NODE_CMD=D:\nodejs\node.exe"
if not defined NODE_CMD if exist "E:\nodejs\node.exe" set "NODE_CMD=E:\nodejs\node.exe"

REM 3. Try WorkBuddy managed Node
if not defined NODE_CMD if exist "C:\Users\Administrator\.workbuddy\binaries\node\versions\22.22.2\node.exe" set "NODE_CMD=C:\Users\Administrator\.workbuddy\binaries\node\versions\22.22.2\node.exe"

if not defined NODE_CMD (
    echo [ERROR] Node.js not found!
    echo Please install Node.js: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

:found
echo [OK] Node.js found
echo.

:input_domain
set "domain="
set /p domain="Enter client domain (e.g. example.com): "
if "%domain%"=="" (
    echo [WARN] Domain cannot be empty!
    goto :input_domain
)
echo.
"%NODE_CMD%" "%~dp0gen_license.js" %domain%
echo.
pause
