#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
php tools/check_requirements.php
printf '\nالنظام يعمل على: http://127.0.0.1:8010\n'
exec php -S 127.0.0.1:8010 -t public public/index.php
