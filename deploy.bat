@echo off
REM ============================================================
REM Rabeq Express Store — Production Build & Deploy Script
REM ============================================================
REM This script:
REM   1. Installs production PHP dependencies
REM   2. Builds the Vue SPA frontend
REM   3. Copies Vue dist files to Laravel's public/ directory
REM   4. Builds Filament/Laravel Vite assets
REM   5. Ready to push to GitHub for Hostinger auto-deploy
REM ============================================================

echo.
echo ========================================
echo  Rabeq Express Store — Production Build
echo ========================================
echo.

REM Step 1: Install PHP production dependencies
echo [1/5] Installing Composer dependencies (production)...
call composer install --optimize-autoloader --no-dev
echo.

REM Step 2: Build root Vite assets (Filament/Tailwind)
echo [2/5] Building Filament/Laravel Vite assets...
call npm install
call npm run build
echo.

REM Step 3: Build Vue SPA frontend
echo [3/5] Building Vue SPA frontend...
cd frontend
call npm install
call npm run build
cd ..
echo.

REM Step 4: Copy Vue dist to Laravel public/
echo [4/5] Copying Vue SPA files to public/...

REM Copy index.html
copy /Y "frontend\dist\index.html" "public\index.html"

REM Copy assets folder
if exist "public\assets" rmdir /S /Q "public\assets"
xcopy "frontend\dist\assets" "public\assets" /E /I /Y /Q

REM Copy favicon and other root files from Vue dist
if exist "frontend\dist\favicon.png" copy /Y "frontend\dist\favicon.png" "public\favicon.png"
if exist "frontend\dist\favicon.ico" copy /Y "frontend\dist\favicon.ico" "public\favicon.ico"

REM Copy any other static files from frontend/dist/public (fonts, images, etc.)
if exist "frontend\dist\fonts" xcopy "frontend\dist\fonts" "public\fonts" /E /I /Y /Q
if exist "frontend\dist\images" xcopy "frontend\dist\images" "public\images" /E /I /Y /Q

echo.

REM Step 5: Done
echo [5/5] Build complete!
echo.
echo ========================================
echo  Next Steps:
echo ========================================
echo  1. Commit all changes: git add -A ^&^& git commit -m "Production build"
echo  2. Push to GitHub: git push origin main
echo  3. Hostinger auto-deploys from GitHub
echo  4. Create .env on server (copy .env.production content)
echo  5. Visit: https://rabeq-express-store.buyonlineskd.com/setup/run?token=rabeq-deploy-2026
echo ========================================
echo.

pause
