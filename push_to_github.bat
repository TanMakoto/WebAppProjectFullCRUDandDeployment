@echo off
chcp 65001 > nul
echo ============================================================
echo   🚀 นำโปรเจกต์ Northwind BMS ขึ้น GitHub
echo ============================================================
echo.
echo ขั้นตอน:
echo 1. ไปที่ https://github.com/new แล้วสร้าง Repository ใหม่
echo 2. คัดลอก URL ของ Repository (เช่น https://github.com/YourUsername/northwind-bms.git)
echo.
set /p REPO_URL="วาง URL ของ GitHub Repository ที่นี่: "

if "%REPO_URL%"=="" (
    echo [!] ไม่ได้ระบุ URL ยกเลิกการทำงาน
    pause
    exit /b
)

echo.
echo กำลังเชื่อมต่อไปยัง %REPO_URL% ...
git remote remove origin 2>nul
git remote add origin %REPO_URL%
git branch -M main

echo กำลัง Push โค้ดขึ้น GitHub...
git push -u origin main

if %ERRORLEVEL% equ 0 (
    echo.
    echo ============================================================
    echo   ✅ นำโค้ดขึ้น GitHub สำเร็จเรียบร้อยแล้ว!
    echo ============================================================
) else (
    echo.
    echo [X] เกิดข้อผิดพลาด กรุณาตรวจสอบสิทธิ์การเข้าถึงหรือ URL
)
pause

