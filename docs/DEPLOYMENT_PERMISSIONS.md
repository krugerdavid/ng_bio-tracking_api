# Permisos en el servidor (Laravel en Digital Ocean)

Si ves errores como:

```
The stream or file "/var/www/ng-api/storage/logs/laravel-....log" could not be opened in append mode: Permission denied
```

es porque el usuario con el que corre el servidor web no puede escribir en `storage/` ni en `bootstrap/cache/`.

## Solución: permisos en el servidor

Conéctate por SSH al droplet y, desde la raíz del proyecto (ej. `/var/www/ng-api`), ejecuta:

```bash
cd /var/www/ng-api

# Propietario: usuario con el que despliegas. Grupo: el usuario del servidor web (www-data en Apache/Nginx en Debian/Ubuntu).
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Apache

En servidores **Apache** (Debian/Ubuntu) el usuario suele ser `www-data`. Los comandos de arriba sirven tal cual. Para comprobar:

```bash
ps aux | grep apache
# o
ps aux | grep www-data
```

Si Apache corre con otro usuario, usa ese en lugar de `www-data` en el `chown`.

### Nginx + PHP-FPM

Si usas Nginx, el proceso PHP suele correr como `www-data` (PHP-FPM). Si en tu instalación el usuario es `nginx`, cambia:

```bash
sudo chown -R $USER:nginx storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Después de subir nuevos archivos (git pull, deploy), puede ser necesario repetir los `chmod` si los nuevos archivos no heredan permisos:

```bash
sudo chmod -R 775 storage bootstrap/cache
```

## Comprobar usuario del servidor web

Para saber con qué usuario corre el servidor (y por tanto Laravel):

```bash
# Apache (Debian/Ubuntu suele ser www-data)
ps aux | grep apache

# Nginx + PHP-FPM
ps aux | grep php-fpm

# Usuario actual al ejecutar PHP por CLI (puede diferir del que sirve las peticiones web)
php -r "echo get_current_user();"
```

El usuario del proceso Apache o PHP-FPM (o su grupo) debe poder escribir en `storage/` y `bootstrap/cache/`. Con `chown ... :www-data` y `chmod 775`, el grupo `www-data` tiene permiso de escritura.

---

## Error "This password does not use the Bcrypt algorithm"

Si en el log (una vez arreglados los permisos) o en la respuesta de la API aparece algo como **"This password does not use the Bcrypt algorithm"**, significa que en la base de datos hay un usuario cuya contraseña **no** está hasheada con Bcrypt (p. ej. está en texto plano, o con otro algoritmo).

Laravel usa Bcrypt por defecto (`Hash::make()` / `Hash::check()`). Para que el login funcione, esa contraseña debe estar guardada como hash Bcrypt.

**Qué hacer:**

1. **Usuario creado a mano o importado**: actualizar la contraseña desde la aplicación (cambio de contraseña) o con Tinker en el servidor:

   ```bash
   cd /var/www/ng-api
   php artisan tinker
   >>> $u = \App\Models\User::where('email', 'el@email.com')->first();
   >>> $u->password = \Illuminate\Support\Facades\Hash::make('nueva-contraseña-segura');
   >>> $u->save();
   ```

2. **Seeders o migraciones**: asegurarse de que siempre se use `Hash::make()` al guardar contraseñas, nunca texto plano.
