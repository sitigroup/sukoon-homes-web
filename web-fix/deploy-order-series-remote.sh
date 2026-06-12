#!/usr/bin/env bash
set -e
ADMIN=/www/wwwroot/admin-homes

rm -rf /tmp/tv-order-series-extract
mkdir -p /tmp/tv-order-series-extract
tar -xzf /tmp/tv-order-series.tgz -C /tmp/tv-order-series-extract
rsync -a /tmp/tv-order-series-extract/TrustVerification/ "$ADMIN/app/Plugins/TrustVerification/"
chown -R www:www "$ADMIN/app/Plugins/TrustVerification"

cd "$ADMIN"
php artisan migrate --force
php artisan view:clear
php artisan config:clear

php artisan tinker --execute="echo 'preview=' . App\Plugins\TrustVerification\Services\TrustVerificationOrderNumberService::preview() . PHP_EOL; print_r(App\Plugins\TrustVerification\Models\TvSetting::whereIn('key',['order_number_prefix','order_number_format','order_number_next_sequence'])->pluck('value','key')->all());"

echo ORDER_SERIES_DEPLOY_OK
