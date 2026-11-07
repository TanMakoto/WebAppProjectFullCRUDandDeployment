@echo off
chcp 65001 > nul
echo ============================================================
echo   🚀 กำลังเริ่มต้นเซิร์ฟเวอร์ Northwind BMS...
echo ============================================================
echo.
echo URL เว็บแอปพลิเคชัน: http://localhost:8000
echo URL เอกสาร API Docs: http://localhost:8000/docs/api.html
echo.
echo ข้อมูลเข้าสู่ระบบ (Default):
echo   Username: admin@northwind.co.th
echo   Password: admin
echo.
echo กด Ctrl+C เพื่อหยุดการทำงานของเซิร์ฟเวอร์
echo ============================================================

"C:\MAMP\bin\php\php8.2.14\php.exe" -S localhost:8000 -c php.ini
pause

