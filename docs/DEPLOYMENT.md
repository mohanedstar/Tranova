# 🚀 Deployment Guide

<div align="center">

**Complete guide for deploying Trinova Platform to production**

![Render](https://img.shields.io/badge/Render-Recommended-46E3B7?style=flat-square&logo=render)
![Docker](https://img.shields.io/badge/Docker-Supported-2496ED?style=flat-square&logo=docker)
![AWS](https://img.shields.io/badge/AWS-Supported-FF9900?style=flat-square&logo=amazon-aws)
![HTTPS](https://img.shields.io/badge/HTTPS-Required-00C853?style=flat-square)

</div>

---

## 📖 Table of Contents

- [Deployment Options](#-deployment-options)
- [Pre-Deployment Checklist](#-pre-deployment-checklist)
- [Deploy to Render (Recommended)](#-deploy-to-render-recommended)
- [Deploy to DigitalOcean](#-deploy-to-digitalocean)
- [Deploy to AWS](#-deploy-to-aws)
- [Deploy with Docker](#-deploy-with-docker)
- [Environment Configuration](#-environment-configuration)
- [Database Setup](#-database-setup)
- [Storage Configuration](#-storage-configuration)
- [SSL/HTTPS Setup](#-sslhttps-setup)
- [Queue Workers](#-queue-workers)
- [Cron Jobs](#-cron-jobs)
- [Monitoring](#-monitoring)
- [Backups](#-backups)
- [Security Hardening](#-security-hardening)
- [Updates & Rollbacks](#-updates--rollbacks)
- [Troubleshooting](#-troubleshooting)
- [Cost Estimation](#-cost-estimation)

---

## 🌐 Deployment Options

| Platform | Difficulty | Cost | Best For |
|----------|-----------|------|----------|
| **Render** | ⭐ Easy | Free tier | Quick deployment |
| **DigitalOcean** | ⭐⭐ Medium | $6/month | Production apps |
| **AWS** | ⭐⭐⭐ Advanced | Variable | Enterprise apps |
| **Docker** | ⭐⭐ Medium | Varies | Containerized apps |
| **Shared Hosting** | ⭐ Easy | $3-10/month | Budget projects |

---

## ✅ Pre-Deployment Checklist

Before deploying, ensure:

- [ ] All tests passing (`php artisan test`)
- [ ] `.env` configured for production
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database backups configured
- [ ] SSL certificate ready
- [ ] Domain configured
- [ ] Email service configured (SMTP/SendGrid/Mailgun)
- [ ] File permissions set correctly

---

## 🎯 Deploy to Render (Recommended)

### Why Render?

- ✅ Free tier available (750 hours/month)
- ✅ Easy deployment from GitHub
- ✅ Automatic HTTPS
- ✅ Good performance
- ✅ Simple configuration
- ✅ Auto-deploy on push

---

### Step 1: Prepare Your Project

#### Update `.env.example`

```env
APP_NAME=Trinova
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=trinova
DB_USERNAME=your-username
DB_PASSWORD=your-password

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@trinova.com
MAIL_FROM_NAME="Trinova Platform"

QUEUE_CONNECTION=sync
SESSION_DRIVER=database
```

#### Create `render.yaml` (Optional)

```yaml
services:
  - type: web
    name: trinova-api
    env: php
    buildCommand: "composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan route:cache"
    startCommand: "php artisan serve --host=0.0.0.0 --port=$PORT"
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: APP_KEY
        generateValue: true
      - key: DB_CONNECTION
        value: mysql
      - key: DB_HOST
        sync: false
      - key: DB_DATABASE
        sync: false
      - key: DB_USERNAME
        sync: false
      - key: DB_PASSWORD
        sync: false
  
  - type: mysql
    name: trinova-db
    plan: free
    databaseName: trinova
    user: trinova
```

---

### Step 2: Deploy via Dashboard

1. Go to [render.com](https://render.com)
2. Sign up/Login with GitHub
3. Click **New +** → **Web Service**
4. Connect your GitHub repository: `mohanedstar/Tranova`
5. Configure:

```
Name: trinova-api
Environment: PHP
Region: Oregon (US West)
Branch: main
Build Command: composer install --no-dev && php artisan config:cache
Start Command: php artisan serve --host=0.0.0.0 --port=$PORT
```

6. Add Environment Variables:

```
APP_NAME=Trinova
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trinova-api.onrender.com
APP_KEY=base64:your-generated-key
DB_CONNECTION=mysql
DB_HOST=your-db-host.onrender.com
DB_PORT=3306
DB_DATABASE=trinova
DB_USERNAME=trinova
DB_PASSWORD=your-password
MAIL_MAILER=log
QUEUE_CONNECTION=sync
```

7. Click **Create Web Service**

---

### Step 3: Setup Database

#### Option A: Render MySQL (Recommended)

1. In Render Dashboard, click **New +** → **MySQL**
2. Configure:
   - Name: `trinova-db`
   - Database: `trinova`
   - User: `trinova`
   - Password: (auto-generated)
3. Copy connection details
4. Add to Web Service environment variables

#### Option B: External Database

Use PlanetScale, ClearDB, or any MySQL provider:

1. Create database
2. Get connection details
3. Add to Render environment variables

---

### Step 4: Run Migrations

#### Via Render Shell

1. Go to your Web Service
2. Click **Shell** tab
3. Run:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

#### Via Render Command (Automatic)

Add to `render.yaml`:

```yaml
preDeployCommand: "php artisan migrate --force"
```

---

### Step 5: Generate APP_KEY

In Render Shell:

```bash
php artisan key:generate --show
```

Copy the output and add to environment variables as `APP_KEY`.

---

### Step 6: Test Deployment

```bash
# Check health
curl https://your-app.onrender.com/api/opportunities

# Test registration
curl -X POST https://your-app.onrender.com/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "student",
    "student_id": "20240001",
    "major": "IT",
    "university": "Test",
    "year_of_study": "3"
  }'
```

---

### Step 7: Enable Auto-Deploy

1. Go to Web Service settings
2. Enable **Auto-Deploy**
3. Every push to `main` will trigger deployment

---

## 🖥️ Deploy to DigitalOcean

### Step 1: Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LEMP stack
sudo apt install nginx mysql-server php8.3-fpm php8.3-mysql \
    php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml

# Secure MySQL
sudo mysql_secure_installation
```

### Step 2: Create Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE trinova;
CREATE USER 'trinova'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON trinova.* TO 'trinova'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Clone Project

```bash
cd /var/www
sudo git clone https://github.com/mohanedstar/Tranova.git
cd Trinova
sudo composer install --no-dev --optimize-autoloader
```

### Step 4: Configure Environment

```bash
sudo cp .env.example .env
sudo nano .env
```

Update with production values.

### Step 5: Generate Key & Migrate

```bash
sudo php artisan key:generate
sudo php artisan migrate --force
sudo php artisan db:seed --force
sudo php artisan storage:link
```

### Step 6: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/Tranova
sudo chmod -R 775 /var/www/Tranova/storage
sudo chmod -R 775 /var/www/Tranova/bootstrap/cache
```

### Step 7: Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/trinova
```

```nginx
server {
    listen 80;
    server_name trinova.com www.trinova.com;
    root /var/www/Tranova/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/trinova /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 8: Install SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d trinova.com -d www.trinova.com
```

### Step 9: Cache Configuration

```bash
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache
```

---

## ☁️ Deploy to AWS

### Using EC2

1. Launch EC2 instance (Ubuntu 22.04)
2. Follow DigitalOcean deployment steps
3. Configure Security Groups (ports 80, 443, 22)
4. Use RDS for database

### Using Elastic Beanstalk

```bash
# Install EB CLI
pip install awsebcli

# Initialize
eb init

# Create environment
eb create production

# Deploy
eb deploy
```

---

## 🐳 Deploy with Docker

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: trinova_app
    restart: unless-stopped
    working_dir: /var/www/
    volumes:
      - ./:/var/www
    networks:
      - trinova

  db:
    image: mysql:8.0
    container_name: trinova_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: trinova
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_PASSWORD: secret
      MYSQL_USER: trinova
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - trinova

  nginx:
    image: nginx:alpine
    container_name: trinova_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - trinova

networks:
  trinova:
    driver: bridge

volumes:
  dbdata:
```

### Dockerfile

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
```

### Run Docker

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## ⚙️ Environment Configuration

### Required Environment Variables

```env
# Application
APP_NAME=Trinova
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trinova.com
APP_KEY=base64:your-generated-key

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trinova
DB_USERNAME=trinova
DB_PASSWORD=StrongPassword123!

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@trinova.com
MAIL_FROM_NAME="Trinova Platform"

# Queue
QUEUE_CONNECTION=database

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_STORE=database

# Broadcasting
BROADCAST_CONNECTION=log
```

---

## 🗄️ Database Setup

### Production Database Best Practices

1. **Use strong passwords**
2. **Limit database user permissions**
3. **Enable SSL connections**
4. **Regular backups**
5. **Monitor performance**

### Migration Commands

```bash
# Run migrations
php artisan migrate --force

# Seed database
php artisan db:seed --force

# Rollback last migration
php artisan migrate:rollback

# Reset all migrations
php artisan migrate:reset
```

---

## 📦 Storage Configuration

### Local Storage

```bash
# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Cloud Storage (S3)

Update `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
AWS_USE_PATH_STYLE_ENDPOINT=false
```

---

## 🔒 SSL/HTTPS Setup

### Let's Encrypt (Free)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### Auto-renewal

```bash
sudo certbot renew --dry-run
```

Certbot will automatically renew certificates.

---

## ⚡ Queue Workers

### Setup Supervisor

```bash
sudo apt install supervisor
sudo nano /etc/supervisor/conf.d/trinova-worker.conf
```

```ini
[program:trinova-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/Tranova/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/Tranova/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start trinova-worker:*
```

---

## ⏰ Cron Jobs

### Setup Scheduler

```bash
crontab -e
```

Add:

```cron
* * * * * cd /var/www/Tranova && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Monitoring

### Laravel Telescope (Development)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

### Laravel Horizon (Queue Monitoring)

```bash
composer require laravel/horizon
php artisan horizon:install
```

### Uptime Monitoring

- [UptimeRobot](https://uptimerobot.com/) - Free
- [Pingdom](https://www.pingdom.com/) - Paid
- [StatusCake](https://www.statuscake.com/) - Free tier

---

## 💾 Backups

### Automated Backup Script

```bash
#!/bin/bash
BACKUP_DIR="/backups/trinova"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u trinova -p'password' trinova > $BACKUP_DIR/db_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/Tranova/storage

# Delete old backups (keep 7 days)
find $BACKUP_DIR -type f -mtime +7 -delete
```

### Cron Job

```cron
0 2 * * * /usr/local/bin/trinova-backup.sh
```

---

## 🔐 Security Hardening

### 1. Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. Fail2Ban

```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

### 3. File Permissions

```bash
sudo find /var/www/Tranova -type f -exec chmod 644 {} \;
sudo find /var/www/Tranova -type d -exec chmod 755 {} \;
```

### 4. Hide Laravel Version

```env
APP_DEBUG=false
```

### 5. Security Headers

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self'" always;
```

---

## 🔄 Updates & Rollbacks

### Deploy Updates

```bash
cd /var/www/Tranova
sudo git pull origin main
sudo composer install --no-dev --optimize-autoloader
sudo php artisan migrate --force
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache
sudo systemctl restart php8.3-fpm
```

### Rollback

```bash
sudo git log --oneline -10
sudo git checkout <commit-hash>
sudo php artisan migrate:rollback
```

---

## 🐛 Troubleshooting

### Problem: "Application Error"

**Solution:** Check logs in Render Dashboard or `/var/www/Tranova/storage/logs/laravel.log`

### Problem: "Database connection failed"

**Solution:**
- Verify database credentials
- Check database host
- Ensure database is running

### Problem: "502 Bad Gateway"

**Solution:**
- Check if port is correct
- Verify start command
- Check PHP version

### Problem: "Permission denied"

**Solution:**

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Problem: "Class not found"

**Solution:**

```bash
composer dump-autoload
```

---

## 💰 Cost Estimation

### Render

| Service | Free Tier | Paid Plan |
|---------|-----------|-----------|
| Web Service | 750 hours/month | $7/month |
| MySQL | 90 days | $7/month |
| **Total** | **Free (limited)** | **$14/month** |

### DigitalOcean

| Service | Cost |
|---------|------|
| Droplet (1GB) | $6/month |
| Managed Database | $15/month |
| **Total** | **$21/month** |

### AWS

| Service | Cost |
|---------|------|
| EC2 (t2.micro) | Free tier (12 months) |
| RDS (db.t2.micro) | Free tier (12 months) |
| **Total** | **Free (12 months)** |

---

## 📞 Support

For deployment issues:

- [Render Documentation](https://render.com/docs)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)
- [DigitalOcean Tutorials](https://www.digitalocean.com/community/tutorials)
- [Laracasts](https://laracasts.com/)

---

<div align="center">

**📖 Back to [README.md](../README.md) | [API Documentation](API.md) | [Frontend Guide](FRONTEND.md)**

**Made with ❤️ by Trinova Team**

</div>
