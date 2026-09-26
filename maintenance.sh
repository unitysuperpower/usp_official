#!/bin/bash
set -euo pipefail

# Laravel Maintenance Commands for cPanel
# Usage: Run these commands when performing updates

LARAVEL_PATH="${LARAVEL_PATH:-/home/username/laravel}"

echo "======================================"
echo "Laravel Maintenance & Optimization"
echo "======================================"

# Navigate to Laravel directory
cd "$LARAVEL_PATH"

echo ""
echo "1. Putting application in maintenance mode..."
MAINTENANCE_SECRET="$(php -r 'echo bin2hex(random_bytes(24));')"
php artisan down --refresh=15 --secret="$MAINTENANCE_SECRET"
echo "   ✓ Maintenance mode enabled"
echo "   Access during maintenance: https://yourdomain.com/$MAINTENANCE_SECRET"

echo ""
echo "2. Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "   ✓ Caches cleared"

echo ""
echo "3. Running database migrations..."
php artisan migrate --force
echo "   ✓ Migrations completed"

echo ""
echo "4. Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo "   ✓ Optimization completed"

echo ""
echo "5. Saving a private sitemap index snapshot..."
php artisan sitemap:generate
echo "   ✓ Snapshot saved; public sitemaps update live"

echo ""
echo "6. Bringing application back online..."
php artisan up
echo "   ✓ Application is now live"

echo ""
echo "======================================"
echo "Maintenance Complete!"
echo "======================================"
echo ""
echo "Next steps:"
echo "  - Test your website: https://yourdomain.com"
echo "  - Check logs: storage/logs/laravel.log"
echo "  - Monitor for 24 hours"
echo ""
