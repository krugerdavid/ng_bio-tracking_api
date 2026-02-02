# Base de datos MySQL en DigitalOcean

Guía para crear un cluster MySQL gestionado en DigitalOcean e integrarlo con este proyecto Laravel.

## 1. Crear el cluster MySQL en DigitalOcean

### Opción A: Panel web (Control Panel)

1. Entra en [DigitalOcean](https://cloud.digitalocean.com/) → **Databases** → **Create Database Cluster**.
2. Elige **MySQL** como motor.
3. Selecciona región (ej. `nyc1`, `sfo3`) y plan (ej. `db-s-1vcpu-1gb` para desarrollo).
4. Opcional: activa **High Availability** (varios nodos).
5. Asigna un nombre al cluster (ej. `ng-api-mysql`) y pulsa **Create Database Cluster**.
6. Espera a que el cluster esté en estado **Online**.

### Opción B: CLI (doctl)

```bash
doctl databases create ng-api-mysql --engine mysql --region nyc1 --size db-s-1vcpu-1gb --num-nodes 1
```

## 2. Obtener los datos de conexión

### Desde el panel

1. En **Databases**, abre tu cluster.
2. En **Connection Details** verás:
   - **Host** (ej. `db-mysql-xxx-xxx.db.ondigitalocean.com`)
   - **Port** (ej. `25060`)
   - **Database** (por defecto `defaultdb`)
   - **Username** (ej. `doadmin`)
   - **Password** (generada al crear el cluster)
   - **SSL**: DigitalOcean exige conexión SSL (recomendado).

### Desde la CLI

```bash
doctl databases connection <database-cluster-id>
```

Para conexión por red privada (VPC):

```bash
doctl databases connection <database-cluster-id> --private
```

## 3. Crear base de datos y usuario (opcional)

Por defecto existe la base `defaultdb` y el usuario `doadmin`. Si quieres una base dedicada:

1. En el cluster → **Users & Databases**.
2. **Add Database**: nombre ej. `ng_api`.
3. **Add User** (opcional): usuario con permisos solo sobre esa base.

## 4. Configurar el proyecto Laravel

### Variables de entorno (`.env`)

Descomenta y ajusta las variables de MySQL y pon `DB_CONNECTION=mysql`:

```env
DB_CONNECTION=mysql
DB_HOST=db-mysql-xxx-xxx.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=defaultdb
DB_USERNAME=doadmin
DB_PASSWORD=tu_password_del_panel
```

Si creaste una base y usuario propios, usa esos valores en `DB_DATABASE` y `DB_USERNAME`.

### Conexión SSL (recomendado)

DigitalOcean permite conexiones con SSL. Opciones:

**Opción 1 – Sin verificar certificado (solo desarrollo)**

En `config/database.php` la conexión MySQL ya usa `MYSQL_ATTR_SSL_CA` si está definido. Para aceptar cualquier certificado en desarrollo puedes usar el driver con opciones SSL (requiere ajuste en código o paquete específico). En producción es mejor usar la Opción 2.

**Opción 2 – Descargar CA de DigitalOcean (recomendado)**

1. En el cluster → **Connection Details** → **Certificate** → descarga el CA (ej. `ca-certificate.crt`).
2. Guarda el archivo en el proyecto, por ejemplo `storage/app/ca-certificate.crt` (y añade esa ruta a `.gitignore` si no quieres subir el certificado).
3. En `.env`:

```env
MYSQL_ATTR_SSL_CA=/ruta/completa/al/proyecto/storage/app/ca-certificate.crt
```

O usa una variable:

```env
MYSQL_ATTR_SSL_CA="${DO_MYSQL_CA_PATH}"
```

En producción, usa la ruta absoluta donde hayas colocado el CA en el servidor.

### Migraciones

Tras configurar `.env`:

```bash
php artisan migrate
```

## 5. Firewall y redes

- Por defecto el cluster acepta conexiones desde cualquier IP (**Trusted Sources**).
- En producción, restringe en **Trusted Sources** a la IP de tu app (App Platform, Droplet, etc.) o a tu VPC.
- Si la app está en el mismo datacenter/VPC, usa la conexión **private** (host y puerto privados) para más seguridad y menor latencia.

## 6. Resumen de variables `.env` para MySQL en DigitalOcean

| Variable           | Ejemplo / descripción                          |
|--------------------|-------------------------------------------------|
| `DB_CONNECTION`    | `mysql`                                         |
| `DB_HOST`          | Host del cluster (Connection Details)           |
| `DB_PORT`          | `25060` (o el que muestre el panel)             |
| `DB_DATABASE`      | `defaultdb` o la base que hayas creado          |
| `DB_USERNAME`      | `doadmin` o el usuario que hayas creado         |
| `DB_PASSWORD`      | Contraseña del usuario                          |
| `MYSQL_ATTR_SSL_CA`| Ruta al CA de DigitalOcean (recomendado en prod)|

Después de cambiar `.env`, reinicia la aplicación (o el servidor web) para que tome la nueva configuración.
