# DriveRentals - One-Click PowerShell Launcher
Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "   🚗 DRIVERENTALS - ONE-CLICK DEV LAUNCHER" -ForegroundColor Yellow
Write-Host "===================================================" -ForegroundColor Cyan

$root = $PSScriptRoot

# 1. Backend Laravel
Write-Host "[1/3] Khởi động Backend Laravel (Port 8000)..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root\BE'; Write-Host '--- BACKEND LARAVEL (8000) ---' -ForegroundColor Green; php artisan serve --port=8000"

# 2. AI Assistant
Write-Host "[2/3] Khởi động AI Assistant (Port 5005)..." -ForegroundColor Green
if (Test-Path "$root\AI_Assistant\venv\Scripts\Activate.ps1") {
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root\AI_Assistant'; & '.\venv\Scripts\Activate.ps1'; Write-Host '--- AI ASSISTANT (5005) ---' -ForegroundColor Magenta; python main.py"
} else {
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root\AI_Assistant'; Write-Host '--- AI ASSISTANT (5005) ---' -ForegroundColor Magenta; python main.py"
}

# 3. Frontend Vue 3
Write-Host "[3/3] Khởi động Frontend Vue Vite..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root\FE'; Write-Host '--- FRONTEND VUE 3 (VITE) ---' -ForegroundColor Cyan; npm run dev"

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host " [OK] Đã kích hoạt toàn bộ 3 dịch vụ!" -ForegroundColor Green
Write-Host " - Frontend: http://localhost:5173" -ForegroundColor Yellow
Write-Host " - Backend API: http://127.0.0.1:8000" -ForegroundColor Yellow
Write-Host " - AI Assistant: http://127.0.0.1:5005" -ForegroundColor Yellow
Write-Host "===================================================" -ForegroundColor Cyan
