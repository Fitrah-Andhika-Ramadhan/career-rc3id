@echo off
if /I "%1 %2"=="this web" (
    echo ===================================================
    echo 🚀 Memulai CareerRC3ID Local Environment...
    echo ===================================================
    
    echo Menjalankan semua service di dalam satu jendela ini...
    
    start /B cmd /c "php artisan serve"
    start /B cmd /c "php artisan queue:work"
    start /B cmd /c "npm run dev"
    
    echo.
    echo ✅ Semua service sedang berjalan! (Output akan muncul di sini)
    echo 🌐 Silakan buka http://localhost:8000 di browser Anda.
    echo ===================================================
    echo Untuk menghentikan semua server, cukup tekan CTRL+C atau tutup jendela CMD ini.
) else (
    echo ❌ Perintah salah. Silakan ketik perintah berikut:
    echo    run this web
)
