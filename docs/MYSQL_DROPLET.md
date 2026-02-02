# Nueva base de datos MySQL en un Droplet existente

Guía para crear una base de datos MySQL **nueva** en tu Droplet donde ya tienes Apache y MySQL usados por WordPress, y conectar este proyecto Laravel a esa base sin tocar la de WordPress.

## 1. Conectarte al Droplet

Por SSH:

```bash
ssh root@tu-ip-del-droplet
# o, si usas usuario con sudo:
ssh tu_usuario@tu-ip-del-droplet
```

## 2. Entrar en MySQL

Usa el usuario que tenga permisos para crear bases y usuarios (normalmente `root` de MySQL):

```bash
sudo mysql -u root -p
```

Si en tu Droplet MySQL está configurado con autenticación por socket para `root` (sin contraseña en consola):

```bash
sudo mysql
```

## 3. Crear la base de datos y el usuario para Laravel

**No reutilices** la base ni el usuario de WordPress. Crea una base y un usuario solo para este proyecto.

En el prompt de MySQL (`mysql>`), ejecuta (cambia `ng_api`, `ng_api_user` y `TU_PASSWORD_SEGURA` por lo que quieras):

```sql
-- Base de datos para el proyecto Laravel (ng-api)
CREATE DATABASE ng_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usuario solo para esa base (recomendado)
CREATE USER 'ng_api_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURA';

-- Permisos solo sobre ng_api
GRANT ALL PRIVILEGES ON ng_api.* TO 'ng_api_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Salir
EXIT;
```

- `ng_api`: nombre de la base.
- `ng_api_user`: usuario que usará Laravel.
- `TU_PASSWORD_SEGURA`: contraseña fuerte; la pondrás en el `.env` del proyecto.

Si tu aplicación Laravel se conecta desde **otro servidor** (no desde el mismo Droplet), crea el usuario permitiendo conexión remota (y luego abre solo el puerto 3306 para esa IP en el firewall):

```sql
CREATE USER 'ng_api_user'@'%' IDENTIFIED BY 'TU_PASSWORD_SEGURA';
GRANT ALL PRIVILEGES ON ng_api.* TO 'ng_api_user'@'%';
FLUSH PRIVILEGES;
```

En la mayoría de los casos (Laravel y WordPress en el mismo Droplet) usa `'ng_api_user'@'localhost'`.

## 4. Comprobar que la base existe

```bash
sudo mysql -u root -p -e "SHOW DATABASES;"
```

Deberías ver algo como `wordpress...` y `ng_api`.

Comprobar usuario:

```bash
sudo mysql -u root -p -e "SELECT user, host FROM mysql.user WHERE user = 'ng_api_user';"
```

## 5. Configurar el proyecto Laravel en el Droplet

En el `.env` del proyecto Laravel (en el mismo Droplet usa `127.0.0.1` o `localhost`):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ng_api
DB_USERNAME=ng_api_user
DB_PASSWORD=TU_PASSWORD_SEGURA
```

No hace falta SSL para conexiones locales (`127.0.0.1`), así que puedes dejar sin definir `MYSQL_ATTR_SSL_CA`.

## 6. Ejecutar migraciones desde el servidor

En el Droplet, dentro del directorio del proyecto Laravel:

```bash
cd /ruta/al/proyecto/ng-api
php artisan migrate
```

Si usas `www-data` para Apache:

```bash
sudo -u www-data php artisan migrate
```

## Resumen

| Qué                | WordPress (no tocar) | Laravel (nuevo)   |
|--------------------|----------------------|-------------------|
| Base de datos      | La que usa WordPress | `ng_api`          |
| Usuario MySQL      | El de WordPress      | `ng_api_user`     |
| Contraseña         | La de WordPress      | La que definiste  |

Así WordPress sigue usando su base y su usuario, y este proyecto Laravel usa su propia base y usuario en el mismo MySQL del Droplet.
