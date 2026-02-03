# Cola en DigitalOcean (Droplet con Apache + PHP)

Guía para ejecutar la cola de Laravel en un droplet con Apache y PHP, sin servicios extra. Se usa el driver **database** y **Supervisor** para mantener el worker en marcha.

## 1. Configuración de Laravel

En el droplet, en el `.env` de tu proyecto:

```env
QUEUE_CONNECTION=database
```

(Opcional, para auditoría en cola):

```env
AUDIT_QUEUE=true
```

Asegúrate de que las migraciones estén aplicadas (tablas `jobs`, `job_batches`, `failed_jobs`):

```bash
cd /ruta/a/tu/proyecto
php artisan migrate --force
```

## 2. Instalar Supervisor en el droplet

Supervisor mantiene el proceso `php artisan queue:work` corriendo y lo reinicia si se cae.

**Ubuntu/Debian:**

```bash
sudo apt update
sudo apt install supervisor -y
```

## 3. Configurar el worker de Laravel en Supervisor

Crea un archivo de configuración para tu aplicación:

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Contenido (ajusta `usuario`, `/var/www/ng-api` y `php` si aplica):

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ng-api/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/ng-api/storage/logs/worker.log
stopwaitsecs=3600
```

- **user**: el usuario con el que corre PHP/Apache (habitualmente `www-data`).
- **command**: ruta al `artisan` de tu proyecto; `queue:work database` usa la conexión `database`; `--max-time=3600` reinicia el worker cada hora para evitar fugas de memoria.
- **stdout_logfile**: ruta donde Supervisor escribe la salida del worker (crea el archivo o asegura permisos).

Guarda el archivo.

## 4. Activar y arrancar el worker

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

Comprobar estado:

```bash
sudo supervisorctl status
```

Deberías ver algo como:

```
laravel-worker:laravel-worker_00   RUNNING   pid 12345, uptime 0:00:05
```

## 5. Comandos útiles

| Acción | Comando |
|--------|---------|
| Ver estado | `sudo supervisorctl status` |
| Reiniciar worker | `sudo supervisorctl restart laravel-worker:*` |
| Parar worker | `sudo supervisorctl stop laravel-worker:*` |
| Ver logs del worker | `tail -f /var/www/ng-api/storage/logs/worker.log` |
| Ver jobs fallidos (Laravel) | `php artisan queue:failed` |
| Reintentar fallidos | `php artisan queue:retry all` |

## 6. Permisos

El usuario del worker (p. ej. `www-data`) debe poder leer/escribir en tu proyecto, sobre todo:

- `storage/` (logs, cache, sessions)
- `bootstrap/cache/`

Ejemplo:

```bash
sudo chown -R www-data:www-data /var/www/ng-api/storage /var/www/ng-api/bootstrap/cache
sudo chmod -R 775 /var/www/ng-api/storage /var/www/ng-api/bootstrap/cache
```

## 7. Despliegues

Tras cada despliegue (código nuevo), reinicia el worker para que use el código actualizado:

```bash
sudo supervisorctl restart laravel-worker:*
```

O añade este comando a tu script de deploy.

## 8. Opcional: Redis en DigitalOcean (escalar después)

Si más adelante quieres usar Redis (mejor rendimiento, Horizon, etc.):

1. Crea una base de datos **Managed Redis** en DigitalOcean.
2. En el droplet instala la extensión PHP Redis: `sudo apt install php-redis` (o el paquete que use tu versión de PHP).
3. En `.env`:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=tu-redis.db.ondigitalocean.com
REDIS_PASSWORD=...
REDIS_PORT=25061
# TLS según documentación de DO
```

4. En `config/queue.php` la conexión `redis` ya está definida; solo asegura que `REDIS_QUEUE_CONNECTION` apunte a la conexión correcta si tienes varias.
5. Cambia el comando en Supervisor a:

```ini
command=php /var/www/ng-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

Mientras tanto, el driver **database** en un solo droplet es suficiente y no requiere servicios extra.
