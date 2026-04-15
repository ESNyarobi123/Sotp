# SKY Omada — TP-Link WiFi Billing & Captive Portal

A Laravel-based WiFi billing system with captive portal integration for TP-Link Omada SDN controllers. Supports mobile money payments via ClickPesa (M-Pesa, Airtel Money, Tigo Pesa, HaloPesa).

## Tech Stack

- **Backend:** Laravel 13, PHP 8.5, MySQL 8.0+
- **Frontend:** Livewire 4, Flux UI v2, Tailwind CSS v4, ApexCharts
- **Payment:** ClickPesa (USSD-PUSH mobile money)
- **Network:** TP-Link Omada SDN Controller (Open API)
- **Queue:** Redis + Laravel Horizon

---

## VPS Setup (Ubuntu 22.04+)

### 1. System Dependencies

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip
```

### 2. PHP 8.5

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.5 php8.5-fpm php8.5-cli php8.5-mysql php8.5-redis \
  php8.5-xml php8.5-curl php8.5-mbstring php8.5-zip php8.5-bcmath php8.5-gd
```

### 3. MySQL 8.0

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Create database
sudo mysql -e "CREATE DATABASE skyomada; CREATE USER 'skyomada'@'localhost' IDENTIFIED BY 'your_secure_password'; GRANT ALL PRIVILEGES ON skyomada.* TO 'skyomada'@'localhost'; FLUSH PRIVILEGES;"
```

### 4. Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
```

### 5. Nginx

```bash
sudo apt install -y nginx

# Create site config
sudo nano /etc/nginx/sites-available/skyomada
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/skyomada/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/skyomada /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 6. Composer & Node.js

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## Laravel Setup

```bash
cd /var/www
git clone <your-repo-url> skyomada
cd skyomada

composer install --no-dev --optimize-autoloader
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database, Redis, Omada, and ClickPesa credentials.

```bash
php artisan migrate --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Default Admin Login

- **Email:** admin@skyomada.com
- **Password:** password

> Change this immediately after first login.

---

## Omada Controller Setup

### Install Omada SDN Controller

```bash
# Download from TP-Link (example for v5.x)
wget https://static.tp-link.com/upload/software/2024/202401/Omada_SDN_Controller_v5.13.30.8_Linux_x64.deb
sudo dpkg -i Omada_SDN_Controller_*.deb
sudo apt install -f
```

The controller runs on port **8043** (HTTPS) by default.

### Configure Open API

1. Log into Omada Controller → **Settings → Platform → Open API**
2. Enable Open API and create an application
3. Note the **Client ID** and **Client Secret**
4. Set these in your `.env`:

```dotenv
OMADA_CONTROLLER_URL=https://your-vps-ip:8043
OMADA_CONTROLLER_ID=your_controller_id
OMADA_CLIENT_ID=your_client_id
OMADA_CLIENT_SECRET=your_client_secret
OMADA_SITE_ID=your_site_id
OMADA_VERIFY_SSL=false
```

### Configure Hotspot / External Portal

1. In Omada → **Hotspot Manager → External Portal**
2. Set the portal URL to: `https://your-domain.com/portal`
3. Create a Hotspot Operator for API-based authorization

---

## Queue Workers (Horizon)

```bash
# Install Horizon assets
php artisan horizon:install

# Run Horizon (development)
php artisan horizon
```

### Production (Supervisor)

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/skyomada/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/skyomada/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
```

---

## SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

## Logging

- **Application logs:** `storage/logs/laravel.log`
- **Omada API logs:** `storage/logs/omada.log` (daily rotation, 14 days)
- **Horizon dashboard:** `/horizon` (admin only)

---

## Testing

```bash
php artisan test --compact
```

---

## Environment Variables

See `.env.example` for all required configuration keys including:

- Database (MySQL)
- Redis
- Omada Controller (Open API credentials)
- ClickPesa (payment gateway)
- Mail (optional)

---

## License

Proprietary. All rights reserved.
