# 📋 Quick Deployment Checklist

## Before Upload

- [ ] Update `.env.production` with production values
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `npm run build` for production assets
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan sitemap:generate`
- [ ] Create ZIP file (exclude: node_modules, vendor, .git, .env)

## cPanel Setup

- [ ] Create database in cPanel MySQL Databases
- [ ] Create database user with ALL PRIVILEGES
- [ ] Note database credentials

## File Upload

- [ ] Upload ZIP to `/home/username/laravel/`
- [ ] Extract ZIP file
- [ ] Delete ZIP file
- [ ] Set permissions: `chmod -R 755 storage bootstrap/cache`

## Public Folder Setup

Choose ONE option:

**Option A: Symlink**
- [ ] SSH: `ln -s /home/username/laravel/public public_html`

**Option B: Copy + Edit**
- [ ] Copy `/laravel/public/*` to `/public_html/`
- [ ] Edit `/public_html/index.php` paths
- [ ] Copy `.htaccess.cpanel` to `/public_html/.htaccess`

## Environment Configuration

- [ ] Rename `.env.production` to `.env`
- [ ] Update database credentials in `.env`
- [ ] Update APP_URL in `.env`
- [ ] Run `php artisan key:generate`
- [ ] Update `public_html/robots.txt` with actual domain

## Database & Optimization

- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan db:seed --force` (if needed)
- [ ] Run `php artisan storage:link`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan sitemap:generate`

## Cron Job Setup

- [ ] Add cron job in cPanel:
  ```
  * * * * * cd /home/username/laravel && php artisan schedule:run >> /dev/null 2>&1
  ```

## SSL Certificate

- [ ] Enable SSL in cPanel SSL/TLS Status
- [ ] Run AutoSSL (Let's Encrypt)
- [ ] Verify HTTPS works

## Final Testing

- [ ] Test homepage: `https://yourdomain.com`
- [ ] Test services: `https://yourdomain.com/services`
- [ ] Test blogs: `https://yourdomain.com/blogs`
- [ ] Test login: `https://yourdomain.com/login`
- [ ] Test admin: `https://yourdomain.com/admin`
- [ ] Verify sitemap: `https://yourdomain.com/sitemap.xml`
- [ ] Verify robots.txt: `https://yourdomain.com/robots.txt`
- [ ] Check all images load
- [ ] Check all styles load
- [ ] Test form submissions
- [ ] Test file uploads

## SEO Submission

- [ ] Submit sitemap to Google Search Console
- [ ] Submit sitemap to Bing Webmaster Tools
- [ ] Add Google Analytics (optional)
- [ ] Setup monitoring (UptimeRobot/Pingdom)

## Backup

- [ ] Setup automatic database backups
- [ ] Setup automatic file backups
- [ ] Test restore procedure

## Post-Deployment

- [ ] Monitor error logs: `storage/logs/laravel.log`
- [ ] Check disk space usage
- [ ] Verify cron job runs (check logs after 24h)
- [ ] Test all critical features
- [ ] Document any hosting-specific configurations

---

## Common Commands for Reference

```bash
# Navigate to Laravel directory
cd /home/username/laravel

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Database
php artisan migrate --force
php artisan db:seed --force

# Sitemap
php artisan sitemap:generate

# Storage link
php artisan storage:link

# Set permissions
chmod -R 755 storage bootstrap/cache
find storage -type d -exec chmod 755 {} \;
find storage -type f -exec chmod 644 {} \;
```

---

## Files to Upload to cPanel

```
/home/username/laravel/          (entire Laravel app except node_modules, vendor)
/home/username/public_html/      (copy from public/ OR symlink)
```

## Critical Files Checklist

- [ ] `.env` (from `.env.production`)
- [ ] `.htaccess` (in public_html, from `.htaccess.cpanel`)
- [ ] `composer.json` and `composer.lock`
- [ ] `public/build/` (production assets)
- [ ] `public/sitemap.xml`
- [ ] `public/robots.txt`
- [ ] All `app/` files
- [ ] All `config/` files
- [ ] All `database/` files
- [ ] All `resources/` files
- [ ] All `routes/` files
- [ ] `bootstrap/` folder
- [ ] `storage/` folder (with correct permissions)

---

## Deployment Status

- **Status:** Ready for deployment ✅
- **Environment:** Production
- **PHP Version Required:** 8.2 or higher
- **MySQL Version:** 5.7 or higher
- **Composer:** 2.x
- **Node.js:** 18.x or higher (for local builds)

---

**📚 Full detailed guide:** See `DEPLOYMENT_GUIDE.md`
