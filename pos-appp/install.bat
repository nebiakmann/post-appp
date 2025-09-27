@echo off
chcp 65001 >nul
title Restoran POS Sistemi - Otomatik Kurulum

echo.
echo ========================================
echo    RESTORAN POS SİSTEMİ KURULUMU
echo ========================================
echo.

:: XAMPP kontrolü
echo [1/5] XAMPP kontrol ediliyor...
if not exist "C:\xampp\apache\bin\httpd.exe" (
    echo HATA: XAMPP bulunamadı!
    echo Lütfen XAMPP'ı C:\xampp\ klasörüne kurun.
    echo İndirme: https://www.apachefriends.org/download.html
    pause
    exit /b 1
)
echo ✓ XAMPP bulundu

:: Dosya kopyalama
echo.
echo [2/5] Dosyalar kopyalanıyor...
if not exist "C:\xampp\htdocs\pos-app" mkdir "C:\xampp\htdocs\pos-app"
xcopy /E /I /Y "%~dp0*" "C:\xampp\htdocs\pos-app\"
echo ✓ Dosyalar kopyalandı

:: Apache başlatma
echo.
echo [3/5] Apache başlatılıyor...
taskkill /F /IM httpd.exe >nul 2>&1
net start Apache2.4 >nul 2>&1
if %errorlevel% neq 0 (
    echo UYARI: Apache servis başlatılamadı, manuel başlatma gerekebilir
) else (
    echo ✓ Apache başlatıldı
)

:: MySQL başlatma
echo.
echo [4/5] MySQL başlatılıyor...
taskkill /F /IM mysqld.exe >nul 2>&1
net start MySQL80 >nul 2>&1
if %errorlevel% neq 0 (
    echo UYARI: MySQL servis başlatılamadı, manuel başlatma gerekebilir
) else (
    echo ✓ MySQL başlatıldı
)

:: Veritabanı oluşturma
echo.
echo [5/5] Veritabanı oluşturuluyor...
echo Lütfen MySQL root şifrenizi girin (boş bırakabilirsiniz):
"C:\xampp\mysql\bin\mysql.exe" -u root -p -e "CREATE DATABASE IF NOT EXISTS pos_app CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;"
"C:\xampp\mysql\bin\mysql.exe" -u root -p pos_app < "C:\xampp\htdocs\pos-app\db.sql"
echo ✓ Veritabanı oluşturuldu

echo.
echo ========================================
echo           KURULUM TAMAMLANDI!
echo ========================================
echo.
echo Sistem şu adreste çalışıyor:
echo http://localhost/pos-app/
echo.
echo Ağ üzerinden erişim için:
echo http://[BILGISAYAR-IP]/pos-app/
echo.
echo IP adresinizi öğrenmek için: ipconfig
echo.

:: Tarayıcıda açma
set /p choice="Sistemi tarayıcıda açmak istiyor musunuz? (E/H): "
if /i "%choice%"=="E" (
    start http://localhost/pos-app/
)

echo.
echo Kurulum tamamlandı! KURULUM_REHBERI.md dosyasını okuyun.
pause

