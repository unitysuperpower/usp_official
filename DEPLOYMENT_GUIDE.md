# 🚀 Laravel Application - cPanel Deployment Guide

## 📋 Pre-Deployment Checklist

✅ All packages installed via composer
✅ SEO tools and sitemap configured
✅ Production assets built (npm run build)
✅ All caches optimized
✅ Database migrations ready
✅ .env.production file configured

---

## 📁 cPanel File Structure

Your cPanel hosting typically has this structure:

```
/home/username/
├── public_html/              (This is your web root - place Laravel's public folder contents here)
├── laravel/                  (Create this folder for Laravel application files)
├── logs/
├── mail/
└── etc/
```

**IMPORTANT:** Laravel application files go OUTSIDE public_html for security!

---

## 🔧 Step 1: Prepare Your Local Files

### 1.1 Update Production Environment File

Open `.env.production` and update these values:

```env
APP_NAME="Your App Name"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE    # Generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost                    # Usually 'localhost' on cPanel
DB_PORT=3306
DB_DATABASE=your_cpanel_database     # From cPanel MySQL Databases
DB_USERNAME=your_cpanel_db_user      # From cPanel MySQL Databases
DB_PASSWORD=your_secure_password     # From cPanel MySQL Databases

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com        # Usually mail.yourdomain.com on cPanel
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# For WebSocket (if using subdomain like ws.yourdomain.com)
REVERB_HOST=ws.yourdomain.com
REVERB_PORT=8080
REVERB_SCHEME=https
```

### 1.2 Create ZIP Archive

Create a ZIP file of your entire project (excluding node_modules and vendor):

**Files to EXCLUDE from ZIP:**
- `node_modules/`
- `vendor/`
- `.git/`
- `.env` (use .env.production instead)
- `storage/logs/*.log`
- `storage/framework/cache/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`

---

## 📤 Step 2: Upload to cPanel

### 2.1 Using File Manager

1. Login to cPanel
2. Open **File Manager**
3. Navigate to your home directory (e.g., `/home/username/`)
4. Create a new folder named `laravel` (outside public_html)
5. Upload your ZIP file to `/home/username/laravel/`
6. Extract the ZIP file
7. Delete the ZIP file after extraction

### 2.2 Set Correct Permissions

In cPanel File Manager, set these permissions:

```
storage/              → 755 (folders)
storage/*             → 644 (files)
bootstrap/cache/      → 755
public/               → 755
```

**Command via SSH (if available):**
```bash
cd /home/username/laravel
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs
find storage -type d -exec chmod 755 {} \;
find storage -type f -exec chmod 644 {} \;
```

---

## 🗄️ Step 3: Setup Database

### 3.1 Create Database in cPanel

1. Go to cPanel → **MySQL® Databases**
2. Create a new database (e.g., `username_laravel`)
3. Create a database user (e.g., `username_admin`)
4. Set a strong password
5. Add user to database with **ALL PRIVILEGES**
6. Note down: database name, username, and password

### 3.2 Import Database (if you have existing data)

1. Go to cPanel → **phpMyAdmin**
2. Select your database
3. Click **Import** tab
4. Upload your SQL file
5. Click **Go**

---

## 🔗 Step 4: Link Public Folder to public_html

You have two options:

### Option A: Symlink (Recommended - if your host allows)

Via SSH:
```bash
cd /home/username
rm -rf public_html
ln -s /home/username/laravel/public public_html
```

### Option B: Copy Public Contents + Update index.php

If symlinks aren't allowed:

1. Copy all files from `/home/username/laravel/public/` to `/home/username/public_html/`
2. Edit `/home/username/public_html/index.php`
3. Update these lines:

**Original:**
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Change to:**
```php
require __DIR__.'/../laravel/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel/bootstrap/app.php';
```

4. Copy `.htaccess.cpanel` to `/home/username/public_html/.htaccess`

---

## ⚙️ Step 5: Install Composer Dependencies

### Via SSH (Recommended):

```bash
cd /home/username/laravel
composer install --optimize-autoloader --no-dev
```

### Via cPanel Terminal (if available):

Same commands as above.

### Via PHP Command Line:

If SSH is not available, use cPanel Terminal or create a PHP script to run:
```php
<?php
chdir('/home/username/laravel');
shell_exec('composer install --optimize-autoloader --no-dev');
?>
```

---

## 🔐 Step 6: Configure Environment

### 6.1 Setup .env File

1. Rename `.env.production` to `.env`:
   ```bash
   cd /home/username/laravel
   mv .env.production .env
   ```

2. Generate application key:
   ```bash
   php artisan key:generate
   ```

### 6.2 Update robots.txt

Edit `/public_html/robots.txt` and replace `{{APP_URL}}` with your actual domain:
```
Sitemap: https://yourdomain.com/sitemap.xml
```

---

## 🏃 Step 7: Run Migrations & Optimizations

```bash
cd /home/username/laravel

# Run database migrations
php artisan migrate --force

# Run seeders (if needed)
php artisan db:seed --force

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate sitemap
php artisan sitemap:generate

# Create storage link (if not exists)
php artisan storage:link
```

---

## 📝 Step 8: Setup Cron Jobs for Scheduler

Laravel's scheduler needs a cron job to run.

### In cPanel:

1. Go to **Cron Jobs**
2. Add a new cron job:

**Command:**
```bash
cd /home/username/laravel && php artisan schedule:run >> /dev/null 2>&1
```

**Schedule:** `* * * * *` (Every minute)

This will automatically:
- Generate sitemap daily
- Run any scheduled tasks
- Clean up old sessions

---

## 🔌 Step 9: WebSocket Setup (Laravel Reverb)

### Option A: Use Pusher/Ably (Recommended for shared hosting)

Update `.env`:
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=us2
```

### Option B: Self-hosted Reverb (VPS/Dedicated only)

Requires:
1. Node.js installed
2. Port 8080 open
3. Process manager (PM2/Supervisor)
4. SSL certificate for WebSocket subdomain

**Supervisor config** (`/etc/supervisor/conf.d/reverb.conf`):
```ini
[program:reverb]
command=php /home/username/laravel/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/home/username/laravel
autostart=true
autorestart=true
user=username
redirect_stderr=true
stdout_logfile=/home/username/laravel/storage/logs/reverb.log
```

---

## 🔒 Step 10: SSL Certificate (HTTPS)

### In cPanel:

1. Go to **SSL/TLS Status**
2. Select your domain
3. Click **Run AutoSSL** (if using Let's Encrypt)
4. Wait for certificate generation
5. Verify HTTPS is working

**Force HTTPS:** Already configured in `.htaccess.cpanel`

---

## 🎨 Step 11: Frontend Assets

If you make changes to CSS/JS:

**Local:**
```bash
npm run build
```

**Upload:**
- Upload new files from `public/build/` to `/public_html/build/`
- Or upload entire `public/build/` folder

---

## ✅ Step 12: Final Checks

### Test These URLs:

- ✅ `https://yourdomain.com` → Homepage loads
- ✅ `https://yourdomain.com/services` → Services page
- ✅ `https://yourdomain.com/blogs` → Blog page
- ✅ `https://yourdomain.com/login` → Login page
- ✅ `https://yourdomain.com/admin` → Admin redirect
- ✅ `https://yourdomain.com/sitemap.xml` → Sitemap XML
- ✅ `https://yourdomain.com/robots.txt` → Robots file

### Check Permissions:

```bash
# Should be writable
storage/framework/sessions/
storage/framework/views/
storage/framework/cache/
storage/logs/
bootstrap/cache/
```

### Verify Database Connection:

Create a test file `db-test.php` in public_html:
```php
<?php
require __DIR__.'/../laravel/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    DB::connection()->getPdo();
    echo "Database connected successfully!";
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
```

Access: `https://yourdomain.com/db-test.php`
**Delete this file after testing!**

---

## 🐛 Common Issues & Solutions

### Issue 1: 500 Internal Server Error

**Solutions:**
- Check `.htaccess` file is present in public_html
- Verify permissions: `chmod -R 755 storage bootstrap/cache`
- Check error logs: `/home/username/laravel/storage/logs/laravel.log`
- Verify PHP version (Laravel 12 requires PHP 8.2+)

### Issue 2: Blank Page / No Styles

**Solutions:**
- Run `php artisan storage:link`
- Check `public/build/manifest.json` exists
- Verify asset paths in `.env`: `ASSET_URL=https://yourdomain.com`
- Clear browser cache

### Issue 3: Database Connection Error

**Solutions:**
- Verify database credentials in `.env`
- Check database exists in cPanel
- Ensure database user has privileges
- Try `DB_HOST=localhost` instead of `127.0.0.1`

### Issue 4: Session/Cache Issues

**Solutions:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Issue 5: File Upload Errors

**Solutions:**
- Check `storage/app/public` permissions
- Verify `storage` symlink: `ls -la public_html/storage`
- Check PHP upload limits in `.htaccess` or cPanel

---

## 📊 Performance Optimization

### Enable OPcache (if available)

In cPanel → **Select PHP Version** → **Options**:
- ✅ Enable `opcache`
- Set `opcache.memory_consumption=128`
- Set `opcache.max_accelerated_files=10000`

### Database Optimization

```bash
php artisan optimize
php artisan db:show  # Verify connection
```

### CDN Setup (Optional)

For static assets, consider using:
- Cloudflare (Free CDN + SSL)
- BunnyCDN
- AWS CloudFront

---

## 🔄 Future Updates

When updating your application:

1. **Backup first:**
   ```bash
   cp -r /home/username/laravel /home/username/laravel_backup
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Upload changes:**
   - Upload modified files via FTP/File Manager
   - Run migrations: `php artisan migrate --force`

3. **Clear & optimize caches:**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Regenerate sitemap:**
   ```bash
   php artisan sitemap:generate
   ```

---

## 📱 SEO Checklist

✅ **Sitemap:** `https://yourdomain.com/sitemap.xml`
✅ **Robots.txt:** `https://yourdomain.com/robots.txt`
✅ **Meta tags:** All pages have SEO meta tags
✅ **SSL:** HTTPS enabled and forced
✅ **Google Search Console:** Submit sitemap
✅ **Google Analytics:** Add tracking code (optional)

### Submit Sitemap to Search Engines:

**Google Search Console:**
1. Go to https://search.google.com/search-console
2. Add your property (domain)
3. Submit sitemap: `https://yourdomain.com/sitemap.xml`

**Bing Webmaster Tools:**
1. Go to https://www.bing.com/webmasters
2. Add your site
3. Submit sitemap

---

## 📞 Support & Maintenance

### Daily Automated Tasks:
- Sitemap generation (via cron)
- Session cleanup
- Cache optimization

### Weekly Manual Tasks:
- Check error logs: `storage/logs/laravel.log`
- Monitor disk space
- Review database size

### Monthly Tasks:
- Update composer dependencies: `composer update`
- Review and optimize database
- Check SSL certificate expiry
- Backup database and files

---

## 🎉 Deployment Complete!

Your Laravel application is now live and production-ready with:
- ✅ SEO optimization with meta tags
- ✅ Auto-generating sitemap
- ✅ SSL/HTTPS enabled
- ✅ Production caching enabled
- ✅ Optimized assets
- ✅ Database configured
- ✅ Scheduler setup
- ✅ Security hardened

**Next Steps:**
1. Test all functionality thoroughly
2. Submit sitemap to Google Search Console
3. Setup monitoring (e.g., UptimeRobot)
4. Configure backups (daily/weekly)
5. Add Google Analytics (optional)

---

## 📧 Need Help?

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check cPanel error logs
3. Review this guide's troubleshooting section
4. Contact your hosting support for server-specific issues

**Good luck with your deployment! 🚀**
