@echo off
chcp 65001 >nul
title DriveRentals - Khoi dong he thong
echo ===================================================
echo    🚗 DRIVERENTALS - ONE-CLICK DEV LAUNCHER
echo ===================================================
echo Dang khoi dong cac dich vu:
echo  1. Backend Laravel API (Port 8000)
echo  2. Frontend Vue 3 + Vite (Port 5173 / 5175)
echo  3. AI Assistant Flask NLP (Port 5005)
echo ===================================================

:: 1. Chay Backend Laravel
echo [1/3] Khoi dong Backend Laravel...
start "DriveRentals - Backend [Port 8000]" cmd /k "cd /d %~dp0BE && php artisan serve --port=8000"

:: 2. Chay AI Assistant
echo [2/3] Khoi dong AI Assistant...
if exist "%~dp0AI_Assistant\venv\Scripts\activate.bat" (
    start "DriveRentals - AI Assistant [Port 5005]" cmd /k "cd /d %~dp0AI_Assistant && call venv\Scripts\activate.bat && python main.py"
) else (
    start "DriveRentals - AI Assistant [Port 5005]" cmd /k "cd /d %~dp0AI_Assistant && python main.py"
)

:: 3. Chay Frontend Vue 3
echo [3/3] Khoi dong Frontend Vue Vite...
start "DriveRentals - Frontend [Vite]" cmd /k "cd /d %~dp0FE && npm run dev"

echo ===================================================
echo [OK] Tat ca dich vu da duoc khoi dong trong cac cua so rieng!
echo - Website: http://localhost:5173 (hoac port hien thi trong terminal FE)
echo - Backend API: http://127.0.0.1:8000
echo - AI Bot: http://127.0.0.1:5005
echo ===================================================
pause
