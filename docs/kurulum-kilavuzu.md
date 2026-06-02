# ERP Kurulum Kılavuzu

## Gereksinimler

- Docker & Docker Compose
- Make
- PHP 8.3+ (Docker içinde sağlanır)
- MySQL 8, Redis (Docker ile gelir)

## Hızlı Başlangıç

```bash
# 1. Repoyu klonla
git clone <repo-url> erp
cd erp

# 2. Ortam dosyasını hazırla
cp .env.example .env

# 3. Konteynerleri başlat
make up

# 4. Bağımlılıkları yükle
make composer CMD="install"

# 5. Uygulama anahtarını oluştur
make artisan CMD="key:generate"

# 6. Veritabanını kur
make fresh

# 7. http://localhost:8082/admin/erp adresini aç
```

## .env Yapılandırması

```env
APP_URL=http://localhost:8082

DB_HOST=mysql
DB_DATABASE=erp
DB_USERNAME=erp
DB_PASSWORD=secret

ERP_CURRENCY=TRY
ERP_CURRENCY_SYMBOL=₺
ERP_COMPANY_NAME="Şirket Adı"
ERP_TAX_RATE=20
```

## Üretim Sunucusu Kurulumu

```bash
# 1. Ortamı production olarak ayarla
APP_ENV=production
APP_DEBUG=false

# 2. Cache'leri doldur
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Storage symlink
php artisan storage:link

# 4. Queue worker (Supervisor)
# /etc/supervisor/conf.d/erp-worker.conf:
# [program:erp-worker]
# command=php /var/www/erp/artisan queue:work redis --sleep=3 --tries=3

# 5. Cron (Scheduler)
# * * * * * cd /var/www/erp && php artisan schedule:run >> /dev/null 2>&1
```

## Nginx Yapılandırması

```nginx
server {
    listen 80;
    server_name erp.domain.com;
    root /var/www/erp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## İlk Kurulum Sonrası

1. `http://your-domain/admin/erp/setup` adresine git
2. Şirket bilgilerini gir
3. Para birimi ve vergi ayarlarını yap
4. Dashboard'a yönlendirileceksin

### Demo Veri Yükleme

```bash
make artisan CMD="db:seed --class=ErpDemoSeeder"
```

Demo admin: `admin@erp.test` / `password`
