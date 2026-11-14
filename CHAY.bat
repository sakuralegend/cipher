@echo off
rem Bam dup file nay de chay thu Cipher Cert bang PHP va MySQL cua XAMPP, khong can Apache, khong can phpMyAdmin.
rem   1. Tim XAMPP (C:\xampp, D:\xampp, E:\xampp, F:\xampp; khong thay thi hoi duong dan).
rem   2. Bat MySQL neu chua bat.
rem   3. Chua co CSDL cipher_dev thi tao va nap database\schema.sql, seed.sql, seed_demo.sql theo thu tu do.
rem      Co roi thi hoi co nap lai khong; 10 giay khong tra loi la giu nguyen du lieu dang co.
rem   4. Chay web bang PHP (cong 8000, ban thi thu toi 8005) roi mo trinh duyet.
rem Loi nhan khong dau vi cua so lenh Windows hay vo chu tieng Viet co dau.
rem File nay khong chay duoc (may chan file .bat, MySQL dat mat khau root...) thi lam tay theo README.md, Cach 2.
setlocal EnableExtensions
cd /d "%~dp0"

rem Chinh file nay goi lai chinh no o che do phu de mo trinh duyet sau khi web da nghe (xem buoc 4).
if "%~1"=="mo_trinh_duyet" goto mo_trinh_duyet

title Cipher Cert
echo ============================================================
echo   Cipher Cert - He thong quan ly chung chi so
echo ============================================================
echo.

rem ---------- 0. Du file chua ----------
rem Bam CHAY.bat ngay trong file nen thi chuong trinh nen chi bung rieng file nay ra thu muc tam, khong co
rem database\ va public\ (do 13/11/2026: WinRAR bung vao Temp\Rar$DIa...). Dung ngay va bao giai nen truoc.
if not exist "database\schema.sql" goto loi_chua_giai_nen
if not exist "public\index.php" goto loi_chua_giai_nen

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
rem mysqladmin ping tra 0 khi MySQL dang chay, ke ca khi bi tu choi mat khau (loi do bat o buoc 3).
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

rem ---------- 3. CSDL ----------
rem Web doc CSDL cipher_dev bang tai khoan root khong mat khau (mac dinh cua XAMPP, xem config\db.php).
"%MYSQL%" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 goto loi_mat_khau
rem Co bang users moi coi la da nap: CSDL tao roi ma chua nap file nao thi van nap tu dau.
"%MYSQL%" -u root cipher_dev -e "SELECT 1 FROM users LIMIT 1" >nul 2>&1
if errorlevel 1 goto nap_du_lieu
echo [3/4] Da co CSDL cipher_dev.
echo       Nap lai du lieu mau se XOA moi thu da sua va lam moi ngay het han cho du cac moc 30, 15, 7 ngay.
choice /c CK /t 10 /d K /m "      Nap lai du lieu mau tu dau? C = co, K = khong (10 giay khong chon la K)"
if errorlevel 2 goto chay_web
:nap_du_lieu
echo [3/4] Dang tao CSDL cipher_dev va nap du lieu mau...
"%MYSQL%" -u root -e "CREATE DATABASE IF NOT EXISTS cipher_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
if errorlevel 1 goto loi_nap
rem Thu tu bat buoc: schema tao bang (xoa bang cu), seed them tai khoan va 9 chung chi, seed_demo them 41 chung chi.
rem Dung "|| goto" chu khong "if errorlevel": mo file that bai thi lenh khong chay, errorlevel khong doi, se bao Xong sai.
for %%F in (schema seed seed_demo) do (
    echo       database\%%F.sql
    "%MYSQL%" -u root --default-character-set=utf8mb4 cipher_dev < "database\%%F.sql" || goto loi_nap
)
echo       Xong: 50 chung chi mau, 2 tai khoan.

rem ---------- 4. Web ----------
:chay_web
set /a CONG=8000
:thu_cong
"%PHP%" -r "exit(@fsockopen('localhost', %CONG%, $m, $t, 1) ? 0 : 1);"
if errorlevel 1 goto cong_trong
set /a CONG+=1
if %CONG% LEQ 8005 goto thu_cong
goto loi_cong
:cong_trong
echo [4/4] Web chay tai: http://localhost:%CONG%/
echo.
echo   Tai khoan    Mat khau       Vai tro
echo   admin        Cipher@2026    Admin, toan quyen
echo   nhanvien     Cipher@2026    Nhan vien quan tri
echo.
echo   Trinh duyet se tu mo. DONG CUA SO NAY LA TAT WEB.
echo   MySQL van chay; tat bang XAMPP Control Panel hoac dong cua so MySQL thu nho.
echo ============================================================
echo   NHAT KY: moi trang mo la mot dong: gio, cach gui, dia chi, ma tra ve, thoi gian xu ly, nguoi.
echo   Dong thao tac mo dau bang muc: [THONG TIN] viec binh thuong; [CANH BAO] dang nhap sai, thu hoi,
echo   khoa, doc chung chi khong duoc, gui form bi tu choi; [NGUY HIEM] xoa, dau hieu do mat khau hay do mang noi bo.
echo ============================================================
rem Mo trinh duyet o mot tien trinh phu: no cho web nghe roi moi mo, con cua so nay giu web chay.
start "" /b cmd /c ""%~f0" mo_trinh_duyet %CONG%"
rem -q: PHP thoi in dong Accepted/Closing va dong truy cap cua no (do 14/11/2026); web tu in nhat ky co nghia hon
rem (inRaManHinh trong src\nhat_ky.php, chi chay khi web chay bang php -S nhu o day).
"%PHP%" -q -S localhost:%CONG% -t public
echo.
echo Web da dung.
pause
exit /b 0

:mo_trinh_duyet
rem Tien trinh phu thua huong bien PHP cua cua so chinh. Cho toi da 10 giay cho web nghe o cong %2,
rem vi mo trinh duyet truoc khi web nghe thi trang dau tien bao loi.
set /a CHO=0
:cho_web
"%PHP%" -r "exit(@fsockopen('localhost', %2, $m, $t, 1) ? 0 : 1);"
if not errorlevel 1 goto web_da_nghe
set /a CHO+=1
if %CHO% GEQ 10 goto web_da_nghe
ping -n 2 127.0.0.1 >nul
goto cho_web
:web_da_nghe
start "" "http://localhost:%2/"
exit /b 0

:loi_chua_giai_nen
echo LOI: Khong thay thu muc database va public canh file nay:
echo      %~dp0
echo Co le ban dang bam CHAY.bat ngay trong file nen .zip, nen chi rieng file nay duoc bung ra thu muc tam.
echo Hay giai nen ca file .zip truoc: bam chuot phai vao file nen, chon Extract All hoac Giai nen,
echo roi bam dup CHAY.bat trong thu muc vua giai nen.
goto dung
:loi_xampp
echo.
echo LOI: Khong thay PHP hoac MySQL trong "%XAMPP%".
echo Cai XAMPP tu https://www.apachefriends.org roi chay lai file nay, hoac lam tay theo README.md, Cach 2.
goto dung
:loi_mysql
echo.
echo LOI: Bat MySQL khong len sau 40 giay.
echo Mo XAMPP Control Panel, bam Start o dong MySQL, xem loi o do, roi chay lai file nay.
goto dung
:loi_mat_khau
echo.
echo LOI: MySQL tu choi tai khoan root khong mat khau.
echo May nay da dat mat khau cho root. Lam theo README.md: Cach 2, muc Cau hinh,
echo de khai bao mat khau cho web, roi nap CSDL bang phpMyAdmin.
goto dung
:loi_nap
echo.
echo LOI: Nap du lieu mau khong thanh cong, xem dong loi phia tren.
goto dung
:loi_cong
echo.
echo LOI: Cong 8000 den 8005 deu dang ban. Tat bot chuong trinh dang dung cac cong nay roi chay lai.
goto dung
:dung
echo.
pause
exit /b 1
