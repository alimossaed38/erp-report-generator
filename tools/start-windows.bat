@echo off
chcp 65001 >nul
cd /d "%~dp0\.."
where php >nul 2>nul
if errorlevel 1 (
  echo PHP غير موجود في PATH.
  echo شغّل الأمر باستخدام مسار PHP داخل WAMP أو أضفه إلى PATH.
  pause
  exit /b 1
)
php tools\check_requirements.php
if errorlevel 1 (
  echo.
  pause
  exit /b 1
)
echo.
echo يعمل النظام الآن على: http://127.0.0.1:8010
echo اترك هذه النافذة مفتوحة أثناء الاستخدام.
php -S 127.0.0.1:8010 -t public public\index.php
