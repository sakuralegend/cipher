@echo off
rem Bam dup file nay de chay bo test tu dong (tests\chay.php) bang PHP va MySQL cua XAMPP.
rem   1-2. Tim XAMPP, bat MySQL: phan chung voi CHAY_WEB.bat, nam o database\chuan_bi.bat.
rem   3. Chua co CSDL cipher_test thi tao. Bo test tu nap lai CSDL nay truoc moi test, khong dung toi cipher_dev cua web.
rem   4. Chay bo test, roi DUNG CHO BAM PHIM de kip doc ket qua (user 15/11/2026: bam dup tests\chay.php thi cua so
rem      dong ngay khi chay xong). Dong cuoi la "XANH: <so> test" khi moi test dat.
setlocal EnableExtensions
cd /d "%~dp0"
title Cipher Cert - Bo test
rem Bo test in chu tieng Viet co dau dang UTF-8; trang ma 65001 de cua so lenh doc dung.
chcp 65001 >nul
echo ============================================================
echo   Cipher Cert - Bo test tu dong
echo ============================================================
echo.

rem ---------- 0. Du file chua ----------
if not exist "database\chuan_bi.bat" goto loi_chua_giai_nen
if not exist "tests\chay.php" goto loi_chua_giai_nen

rem ---------- 1-2. XAMPP, MySQL ----------
call "database\chuan_bi.bat"
if errorlevel 1 goto dung

rem ---------- 3. CSDL test ----------
"%MYSQL%" -u root -e "CREATE DATABASE IF NOT EXISTS cipher_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
if errorlevel 1 goto dung
echo [3/4] Da co CSDL cipher_test.

rem ---------- 4. Chay test ----------
echo [4/4] Dang chay bo test, mat khoang 40 giay...
echo.
"%PHP%" tests\chay.php
echo.
echo ============================================================
echo   Dong cuoi "XANH: ... test" la moi test dat; "DO: ..." thi xem cac dong dau X phia tren.
echo ============================================================
pause
exit /b 0

:loi_chua_giai_nen
echo LOI: Khong thay thu muc database va tests canh file nay:
echo      %~dp0
echo Hay giai nen ca file .zip truoc, roi bam dup CHAY_TEST.bat trong thu muc vua giai nen.
:dung
echo.
pause
exit /b 1
