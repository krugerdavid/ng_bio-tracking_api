# Base de datos: local vs producción

## Entornos

| Entorno     | Motor   | Uso                          |
|------------|---------|------------------------------|
| **Local**  | SQLite o MySQL (Docker) | Desarrollo en tu máquina     |
| **Producción** | **MySQL** | Servidor (ej. Digital Ocean) |

En producción se usa **MySQL** (Managed Database o Droplet). Ver [DIGITALOCEAN_MYSQL.md](DIGITALOCEAN_MYSQL.md) y [MYSQL_DROPLET.md](MYSQL_DROPLET.md).

## Desarrollo local

- **SQLite**: por defecto en `.env` (`DB_CONNECTION=sqlite`). No requiere instalar nada.
- **Docker (MySQL)**: si levantas el proyecto con `docker-compose up`, el servicio `db` usa MySQL 8 y monta `docker/mysql/data`. Esa carpeta **no debe versionarse** (está en `.gitignore`); contiene los datos del contenedor y se crea al iniciar MySQL.

Si usas Docker + MySQL en local, configura en tu `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=bio_tracker
DB_USERNAME=devuser
DB_PASSWORD=devpassword
```

## Producción (MySQL)

En el servidor, el `.env` debe usar MySQL con las variables indicadas en la documentación de Digital Ocean (host, puerto, base, usuario, contraseña, SSL si aplica).
