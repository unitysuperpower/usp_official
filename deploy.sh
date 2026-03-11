#!/bin/bash

# ==============================================
# Laravel cPanel Deployment Script
# Run this after uploading files to cPanel
# ==============================================

# IMPORTANT: Update these paths for your cPanel
LARAVEL_PATH="/home/username/laravel"
PUBLIC_HTML="/home/username/public_html"

echo "======================================"
echo "Laravel cPanel Deployment"
echo "======================================"
echo ""

# Check if we're in the correct directory
if [ ! -f "$LARAVEL_PATH/artisan" ]; then
    echo "❌ Error: Laravel installation not found at $LARAVEL_PATH"
    echo "Please update LARAVEL_PATH in this script"
    exit 1
fi

cd $LARAVEL_PATH

echo "✓ Found Laravel installation"
echo ""

# Step 1: Install Composer Dependencies
echo "1. Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --optimize-autoloader --no-dev
    echo "   ✓ Composer dependencies installed"
else
    echo "   ⚠ Composer not found. Please install dependencies manually."
fi
echo ""

# Step 2: Setup Environment
echo "2. Setting up environment..."
if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        cp .env.production .env
        echo "   ✓ Created .env from .env.production"
        echo "   ⚠ Please update database credentials in .env"
    else
        echo "   ❌ No .env or .env.production found!"
        exit 1
    fi
else
    echo "   ✓ .env file exists"
fi
echo ""

# Step 3: Generate Application Key
echo "3. Generating application key..."
php artisan key:generate --force
echo "   ✓ Application key generated"
echo ""

# Step 4: Set Permissions
echo "4. Setting file permissions..."
chmod -R 755 storage bootstrap/cache
find storage -type d -exec chmod 755 {} \;
find storage -type f -exec chmod 644 {} \;
echo "   ✓ Permissions set"
echo ""

# Step 5: Clear Old Caches
echo "5. Clearing old caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "   ✓ Caches cleared"
echo ""

# Step 6: Run Migrations
echo "6. Running database migrations..."
read -p "   Run migrations? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo "   ✓ Migrations completed"
else
    echo "   ⚠ Skipped migrations"
fi
echo ""

# Step 7: Storage Link
echo "7. Creating storage link..."
php artisan storage:link
echo "   ✓ Storage linked"
echo ""

# Step 8: Optimize for Production
echo "8. Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo "   ✓ Optimization completed"
echo ""

# Step 9: Generate Sitemap
echo "9. Generating sitemap..."
php artisan sitemap:generate
echo "   ✓ Sitemap generated"
echo ""

# Step 10: Update robots.txt
echo "10. Updating robots.txt..."
if [ -f "$PUBLIC_HTML/robots.txt" ]; then
    # This will need manual update with actual domain
    echo "   ⚠ Please manually update $PUBLIC_HTML/robots.txt with your domain"
    echo "   Replace {{APP_URL}} with https://yourdomain.com"
else
    echo "   ⚠ robots.txt not found in public_html"
fi
echo ""

# Step 11: Copy .htaccess
echo "11. Setting up .htaccess..."
if [ -f ".htaccess.cpanel" ]; then
    if [ ! -f "$PUBLIC_HTML/.htaccess" ]; then
        cp .htaccess.cpanel $PUBLIC_HTML/.htaccess
        echo "   ✓ .htaccess copied to public_html"
    else
        echo "   ⚠ .htaccess already exists in public_html"
        echo "   Review .htaccess.cpanel for any needed updates"
    fi
else
    echo "   ⚠ .htaccess.cpanel not found"
fi
echo ""

echo "======================================"
echo "Deployment Steps Completed!"
echo "======================================"
echo ""

echo "📋 Post-Deployment Checklist:"
echo ""
echo "[ ] Update .env with your database credentials"
echo "[ ] Update .env with your APP_URL (https://yourdomain.com)"
echo "[ ] Update .env with your mail settings"
echo "[ ] Update robots.txt with your actual domain"
echo "[ ] Setup cron job for scheduler:"
echo "    * * * * * cd $LARAVEL_PATH && php artisan schedule:run >> /dev/null 2>&1"
echo "[ ] Enable SSL certificate in cPanel"
echo "[ ] Test your website: https://yourdomain.com"
echo "[ ] Submit sitemap to Google Search Console"
echo ""

echo "🔍 Important Files to Review:"
echo "  - $LARAVEL_PATH/.env (database credentials)"
echo "  - $PUBLIC_HTML/.htaccess (Apache config)"
echo "  - $PUBLIC_HTML/robots.txt (SEO directives)"
echo ""

echo "📊 Test These URLs:"
echo "  - https://yourdomain.com (homepage)"
echo "  - https://yourdomain.com/services (services)"
echo "  - https://yourdomain.com/blogs (blog)"
echo "  - https://yourdomain.com/sitemap.xml (sitemap)"
echo "  - https://yourdomain.com/login (login)"
echo ""

echo "✅ Deployment script completed successfully!"
echo ""
echo "📚 For detailed instructions, see DEPLOYMENT_GUIDE.md"
echo ""
