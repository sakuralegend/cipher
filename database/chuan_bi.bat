@echo off
rem Phan chung cua CHAY_WEB.bat va CHAY_TEST.bat (tach ra 15/11/2026 de hai file khong chep code cua nhau):
rem   1. Tim XAMPP (C:\xampp, D:\xampp, E:\xampp, F:\xampp; khong thay thi hoi duong dan).
rem   2. Bat MySQL neu chua bat, roi kiem MySQL nhan tai khoan root khong mat khau (mac dinh cua XAMPP).
rem Goi bang: call "database\chuan_bi.bat"  roi  if errorlevel 1 goto dung
rem Khong co setlocal: cac bien XAMPP, PHP, MYSQL dat o day phai con lai cho file goi dung tiep.
rem Loi thi in loi nhan va tra ve 1; file goi tu pause de nguoi dung kip doc.

rem ---------- 1. Tim XAMPP ----------
set "XAMPP="
for %%D in (C D E F) do if not defined XAMPP if exist "%%D:\xampp\php\php.exe" set "XAMPP=%%D:\xampp"
if defined XAMPP goto co_xampp
echo Khong tim thay XAMPP o C:\xampp, D:\xampp, E:\xampp, F:\xampp.
set /p "XAMPP=Nhap thu muc cai XAMPP, vi du C:\xampp roi bam Enter: "
rem Bo dau nhay neu nguoi dung keo tha thu muc vao cua so.
set "XAMPP=%XAMPP:"=%"
:co_xampp
set "PHP=%XAMPP%\php\php.exe"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
if not exist "%PHP%" goto loi_xampp
if not exist "%MYSQL%" goto loi_xampp
echo [1/4] XAMPP: %XAMPP%

rem ---------- 2. MySQL ----------
rem mysqladmin ping tra 0 khi MySQL dang chay, ke ca khi bi tu choi mat khau (loi do bat o cuoi buoc nay).
"%MYSQLADMIN%" -u root --connect-timeout=2 ping >nul 2>&1
if not errorlevel 1 goto mysql_san_sang
echo [2/4] MySQL chua bat, dang bat...
start "MySQL - dong cua so nay la tat MySQL" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
set /a CHO=0
:cho_mysql
ping -n 2 127.0.0.1 >nul
"%MYSQLADMIN%" -u root --connect-timeout=2 ping >nul 2>&1
if not errorlevel 1 goto mysql_san_sang
set /a CHO+=1
if %CHO% LSS 20 goto cho_mysql
goto loi_mysql
:mysql_san_sang
echo [2/4] MySQL dang chay.
rem Web va bo test doc CSDL bang tai khoan root khong mat khau (mac dinh cua XAMPP, xem config\db.php).
"%MYSQL%" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 goto loi_mat_khau
exit /b 0

:loi_xampp
echo.
echo LOI: Khong thay PHP hoac MySQL trong "%XAMPP%".
echo Cai XAMPP tu https://www.apachefriends.org roi chay lai, hoac lam tay theo README.md, Cach 2.
exit /b 1
:loi_mysql
echo.
echo LOI: Bat MySQL khong len sau 40 giay.
echo Mo XAMPP Control Panel, bam Start o dong MySQL, xem loi o do, roi chay lai.
exit /b 1
:loi_mat_khau
echo.
echo LOI: MySQL tu choi tai khoan root khong mat khau.
echo May nay da dat mat khau cho root. Lam theo README.md: Cach 2, muc Cau hinh,
echo de khai bao mat khau cho web, roi nap CSDL bang phpMyAdmin.
exit /b 1
