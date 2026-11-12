@echo off
rem Leonardo (leonardoaderito-cmyk): arranque local do servidor Laravel
cd /d "%~dp0"

if not exist vendor (
    echo A instalar dependencias PHP...
    call composer install
)
if not exist .env (
    copy .env.example .env >nul
    php artisan key:generate
)
if not exist database\database.sqlite (
    type nul > database\database.sqlite
)

php artisan migrate --force
if not exist public\build (
    call npm install
    call npm run build
)

start "" http://localhost:8002/entrar
php artisan serve --host=127.0.0.1 --port=8002
