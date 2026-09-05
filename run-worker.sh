#!/usr/bin/env bash
set -euo pipefail
php artisan queue:work database --queue=vapt --sleep=1 --tries=1 --timeout=1800
