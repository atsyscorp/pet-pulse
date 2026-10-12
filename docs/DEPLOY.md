# Despliegue de PetPulse en un VPS con Docker

Guía para poner PetPulse en producción en un servidor Linux propio (DigitalOcean,
Hetzner, AWS EC2, Linode, etc.). Todo lo necesario está en el repositorio:

| Archivo | Función |
|---|---|
| `Dockerfile` | Construye dos imágenes: `app` (PHP-FPM) y `web` (Caddy con los archivos públicos). |
| `compose.yaml` | Levanta los 7 servicios: Caddy, PHP-FPM, Reverb, cola, programador, PostgreSQL 16 y Redis. |
| `.env.production.example` | Plantilla de variables de entorno de producción. |
| `deploy/Caddyfile` | Servidor web: HTTPS automático (Let's Encrypt), estáticos, PHP y WebSocket. |
| `deploy/entrypoint.sh` | Arranque de cada contenedor: espera a PostgreSQL, cachea config y migra (solo `app`). |
| `deploy/php.ini`, `deploy/php-fpm.conf` | Ajustes de PHP y del pool de FPM. |
| `deploy/backup.sh` | Copia de seguridad diaria de PostgreSQL. |

## 1. Requisitos

- VPS con **Ubuntu 22.04/24.04** (o Debian 12), mínimo **2 vCPU y 4 GB de RAM**, 40 GB de disco.
- Un **dominio** apuntando a la IP pública del servidor (registro A, y AAAA si hay IPv6),
  por ejemplo `petpulse.suclinica.com`. Caddy lo necesita para emitir el certificado TLS.
- Puertos **80 y 443** abiertos en el cortafuegos del proveedor.

## 2. Preparar el servidor (una sola vez)

```bash
# Como root o con sudo
apt update && apt upgrade -y
curl -fsSL https://get.docker.com | sh
usermod -aG docker $USER        # cierre sesión y vuelva a entrar

# Cortafuegos básico
ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443/tcp && ufw allow 443/udp && ufw enable
```

## 3. Obtener el código

```bash
sudo mkdir -p /opt/petpulse && sudo chown $USER /opt/petpulse
git clone https://github.com/atsyscorp/pet-pulse.git /opt/petpulse
cd /opt/petpulse
```

## 4. Configurar el entorno

```bash
cp .env.production.example .env
```

Edite `.env` y complete cada valor marcado `CHANGE-ME`:

```bash
APP_URL=https://petpulse.suclinica.com
APP_DOMAIN=petpulse.suclinica.com
ACME_EMAIL=sistemas@suclinica.com
DB_PASSWORD=$(openssl rand -base64 32)      # genere y pegue el valor
REVERB_APP_KEY=$(openssl rand -hex 16)
REVERB_APP_SECRET=$(openssl rand -hex 32)
```

Genere la clave de la aplicación y péguela en `APP_KEY`:

```bash
docker compose run --rm --no-deps app php artisan key:generate --show
```

> `REVERB_APP_KEY` y `APP_DOMAIN` se incrustan en el JavaScript al construir la imagen.
> Si los cambia después, vuelva a construir (`docker compose up -d --build`).

## 5. Primer arranque

```bash
docker compose up -d --build
docker compose logs -f app       # espere a ver "INFO Running migrations" y el arranque de php-fpm
```

En el primer arranque el contenedor `app` ejecuta las migraciones automáticamente y Caddy
solicita el certificado TLS (tarda unos segundos). Compruebe:

```bash
curl -I https://petpulse.suclinica.com/up        # HTTP/2 200
docker compose ps                                 # todos los servicios "healthy" o "running"
```

## 6. Crear la primera clínica y el usuario administrador

Todavía no hay pantalla de registro. Cree el primer inquilino con Tinker:

```bash
docker compose exec app php artisan tinker
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

(El inicio de sesión aún depende de la pantalla de autenticación, que está en la lista de
próximos pasos del README; la ruta `/dev/login` **no existe** en producción.)

## 7. Actualizar a una nueva versión

```bash
cd /opt/petpulse
git pull
docker compose up -d --build        # reconstruye, migra y reinicia con cero pérdida de datos
docker compose image prune -f       # libera imágenes antiguas
```

Las migraciones se ejecutan solas al arrancar `app`. Los datos viven en volúmenes Docker
(`pgdata`, `redisdata`, `storage`) y sobreviven a cualquier reconstrucción.

## 8. Copias de seguridad

```bash
./deploy/backup.sh                      # volcado comprimido en deploy/backups/
crontab -e
15 2 * * * cd /opt/petpulse && ./deploy/backup.sh >> deploy/backups/backup.log 2>&1
```

Copie `deploy/backups/` fuera del servidor (S3, Backblaze, rsync a otra máquina). Para
restaurar:

```bash
gunzip -c deploy/backups/petpulse-20261012-0215.sql.gz | docker compose exec -T db psql -U petpulse -d petpulse
```

## 9. Operación diaria

| Tarea | Comando |
|---|---|
| Ver registros | `docker compose logs -f app reverb queue` |
| Estado de servicios | `docker compose ps` |
| Consola Laravel | `docker compose exec app php artisan tinker` |
| Consola PostgreSQL | `docker compose exec db psql -U petpulse` |
| Modo mantenimiento | `docker compose exec app php artisan down` / `up` |
| Reiniciar un servicio | `docker compose restart reverb` |

## 10. Escalar

- **Más clínicas / más carga PHP:** suba `pm.max_children` en `deploy/php-fpm.conf`
  (≈ RAM disponible ÷ 60 MB) y reconstruya.
- **Más de un servidor de Reverb:** active `REVERB_SCALING_ENABLED=true` (usa Redis
  para sincronizar los servidores).
- **Base de datos gestionada:** apunte `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD` a un
  PostgreSQL externo (RDS, DigitalOcean Managed DB), quite el servicio `db` del
  `compose.yaml` y elimine `DB_HOST: db` del bloque `x-app-env`.

## Diagnóstico

| Síntoma | Causa habitual |
|---|---|
| Caddy no obtiene certificado | DNS aún no apunta al servidor, o puerto 80/443 cerrado. `docker compose logs web`. |
| El tablero no se actualiza en vivo | Revise `docker compose logs reverb`; confirme que `REVERB_APP_KEY` del `.env` es el mismo con el que se construyó la imagen. |
| Error 502 | `app` todavía migrando o sin salud: `docker compose ps` y `docker compose logs app`. |
| "No application encryption key" | Falta `APP_KEY` en `.env` (paso 4). |
