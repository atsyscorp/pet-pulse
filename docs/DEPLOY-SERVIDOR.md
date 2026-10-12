# Despliegue de PetPulse en un servidor propio (sin Docker)

Guía para montar PetPulse en un servidor Linux que **ya tiene PostgreSQL**, bajo un
subdominio (por ejemplo `petpulse.suclinica.com`). Si prefiere contenedores, use
[DEPLOY.md](DEPLOY.md).

Archivos del repositorio que se usan aquí:

| Archivo | Función |
|---|---|
| `deploy/nginx/petpulse.conf` | Bloque de servidor de Nginx: PHP-FPM, estáticos y el WebSocket de Reverb. |
| `deploy/systemd/petpulse-reverb.service` | Mantiene el servidor Reverb (tiempo real) siempre en marcha. |
| `deploy/systemd/petpulse-queue.service` | Mantiene el trabajador de colas siempre en marcha. |
| `deploy/deploy.sh` | Script de actualización: `git pull`, dependencias, assets, migraciones, cachés, reinicios. |

## 1. Requisitos en el servidor

- **PHP 8.3 o superior** (FPM) con las extensiones: `pgsql`, `pdo_pgsql`, `mbstring`, `xml`,
  `curl`, `zip`, `intl`, `bcmath`, `opcache`, `pcntl`.
- **Composer 2**, **Node.js 20+** y npm (solo para compilar los assets).
- **Nginx** (o Apache, ver nota al final) y **certbot** para el certificado TLS.
- **PostgreSQL 14+** en marcha.
- El subdominio apuntando a la IP del servidor (registro A / AAAA).

En Ubuntu 24.04:

```bash
sudo apt update
sudo apt install -y nginx certbot python3-certbot-nginx git unzip \
  php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-intl php8.4-bcmath php8.4-opcache
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

## 2. Base de datos

```bash
sudo -u postgres psql
```

```sql
CREATE USER petpulse WITH PASSWORD 'una-contraseña-larga';
CREATE DATABASE petpulse OWNER petpulse ENCODING 'UTF8';
\q
```

Compruebe que el servidor acepta conexiones locales con contraseña
(`/etc/postgresql/*/main/pg_hba.conf`, línea `host all all 127.0.0.1/32 scram-sha-256`).

## 3. Código

```bash
sudo mkdir -p /var/www/petpulse && sudo chown $USER:www-data /var/www/petpulse
git clone https://github.com/atsyscorp/pet-pulse.git /var/www/petpulse
cd /var/www/petpulse

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --no-audit --no-fund
```

## 4. Configuración (`.env`)

```bash
cp .env.example .env
php artisan key:generate
```

Edite `.env` con estos valores (el resto puede quedar como está):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://petpulse.suclinica.com

LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=petpulse
DB_USERNAME=petpulse
DB_PASSWORD=una-contraseña-larga

SESSION_SECURE_COOKIE=true

# Reverb: el navegador se conecta por el subdominio (wss://…/app/clave) y Nginx
# lo reenvía al puerto 8080 local. Genere claves nuevas: openssl rand -hex 16 / 32
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=petpulse
REVERB_APP_KEY=<openssl rand -hex 16>
REVERB_APP_SECRET=<openssl rand -hex 32>
REVERB_HOST=petpulse.suclinica.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> Los valores `VITE_*` se incrustan en el JavaScript al compilar. Si cambia la clave de
> Reverb o el dominio, repita `npm run build`.

Compile los assets, migre y prepare las cachés:

```bash
npm run build
php artisan migrate --force
php artisan optimize
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## 5. Nginx y TLS

```bash
sudo cp deploy/nginx/petpulse.conf /etc/nginx/sites-available/petpulse
sudo sed -i 's/petpulse.suclinica.com/SU-SUBDOMINIO/' /etc/nginx/sites-available/petpulse
sudo ln -s /etc/nginx/sites-available/petpulse /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

sudo certbot --nginx -d SU-SUBDOMINIO      # certificado + redirección a HTTPS
```

Si su versión de PHP no es 8.4, ajuste la línea `fastcgi_pass unix:/run/php/php8.4-fpm.sock`.

## 6. Procesos permanentes: Reverb, cola y programador

```bash
sudo cp deploy/systemd/petpulse-reverb.service deploy/systemd/petpulse-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now petpulse-reverb petpulse-queue
sudo systemctl status petpulse-reverb petpulse-queue
```

Programador de tareas (cron de `www-data`):

```bash
sudo crontab -u www-data -e
* * * * * cd /var/www/petpulse && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Comprobación

```bash
curl -I https://SU-SUBDOMINIO/up          # HTTP/2 200
sudo journalctl -u petpulse-reverb -n 20  # "Starting server on 127.0.0.1:8080"
```

Abra el tablero en dos navegadores; al registrar una dosis en uno, el otro debe
actualizarse en menos de un segundo. Si no lo hace, revise la sección *Diagnóstico*.

## 8. Primera clínica y administrador

Aún no hay pantalla de registro; créelos con Tinker:

```bash
cd /var/www/petpulse && php artisan tinker
```

```php
$clinic = App\Models\Clinic::create(['name' => 'Clínica Veterinaria Los Andes', 'tax_id' => '900123456-7']);
App\Models\User::create([
    'clinic_id' => $clinic->id,
    'name' => 'Administrador',
    'email' => 'admin@suclinica.com',
    'password' => 'una-contraseña-segura',   // se cifra automáticamente
    'role' => App\Enums\UserRole::Admin,
]);
```

La ruta `/dev/login/{id}` **no existe en producción** (`APP_ENV=production`); el inicio de
sesión requiere la pantalla de autenticación, pendiente en el README.

## 9. Actualizar a una versión nueva

```bash
cd /var/www/petpulse && ./deploy/deploy.sh
```

El script pone la aplicación en mantenimiento, trae el código, instala dependencias,
compila assets, migra, regenera cachés, reinicia Reverb y la cola y vuelve a levantar.

## 10. Copias de seguridad

```bash
sudo crontab -e
15 2 * * * pg_dump -U petpulse -h 127.0.0.1 petpulse | gzip > /var/backups/petpulse-$(date +\%Y\%m\%d).sql.gz
```

Guarde una contraseña en `~/.pgpass` (`127.0.0.1:5432:petpulse:petpulse:contraseña`,
permisos 600) y copie los volcados fuera del servidor.

## Diagnóstico

| Síntoma | Causa habitual |
|---|---|
| Error 500 / página en blanco | Permisos de `storage/` o `bootstrap/cache/` (paso 4), o `APP_KEY` vacío. Vea `storage/logs/laravel.log`. |
| El tablero no se actualiza en vivo | `petpulse-reverb` caído, o la `REVERB_APP_KEY` cambió después de `npm run build`. Abra la consola del navegador: debe conectar a `wss://SU-SUBDOMINIO/app/…`. |
| 502 en `/app/…` | Nginx no llega a `127.0.0.1:8080`: `sudo journalctl -u petpulse-reverb`. |
| Cookies o redirecciones a `http://` | `APP_URL` sin `https` o `SESSION_SECURE_COOKIE` ausente. |
| "could not connect to server" (PostgreSQL) | `DB_HOST`/contraseña incorrectos o `pg_hba.conf` sin la línea para `127.0.0.1`. |

### Nota sobre Apache / cPanel

Si el servidor usa Apache, el `.htaccess` de `public/` ya enruta a Laravel; falta el proxy
del WebSocket. Con `mod_proxy_wstunnel` activado, añada al VirtualHost:

```apache
ProxyPass        /app  ws://127.0.0.1:8080/app
ProxyPassReverse /app  ws://127.0.0.1:8080/app
ProxyPass        /apps http://127.0.0.1:8080/apps
ProxyPassReverse /apps http://127.0.0.1:8080/apps
```

En alojamientos compartidos (cPanel sin acceso root) no es posible mantener Reverb en
marcha ni usar systemd: PetPulse necesita un VPS o servidor dedicado.
