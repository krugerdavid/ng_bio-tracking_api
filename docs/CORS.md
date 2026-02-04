# Configuración CORS

Este proyecto tiene CORS habilitado para las rutas de la API y Sanctum. La configuración se controla por variables de entorno.

## Variables en `.env`

| Variable | Descripción | Por defecto |
|----------|-------------|-------------|
| `CORS_ALLOWED_ORIGINS` | Orígenes permitidos, separados por coma. Usar `*` para permitir todos (solo desarrollo). | `*` |
| `CORS_MAX_AGE` | Tiempo en segundos que el navegador puede cachear la respuesta preflight. | `0` |
| `CORS_SUPPORTS_CREDENTIALS` | Si se permiten cookies/credenciales (útil con Sanctum en SPAs). | `false` |

## Ejemplos

### Desarrollo (permitir todo)

```env
# Opcional: por defecto ya se permite todo si no defines la variable
CORS_ALLOWED_ORIGINS=*
CORS_SUPPORTS_CREDENTIALS=true
```

### Producción (dominios concretos)

```env
CORS_ALLOWED_ORIGINS=https://tu-app.com,https://www.tu-app.com
CORS_MAX_AGE=86400
CORS_SUPPORTS_CREDENTIALS=true
```

### Varios entornos (local + staging)

```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://staging.tu-app.com
CORS_SUPPORTS_CREDENTIALS=true
```

### Frontend local (Vite, localhost:5173) contra API desplegada (ej. Digital Ocean)

Si desarrollas en local (`npm run dev` → `http://localhost:5173`) y la API está en un servidor (ej. `https://ng-api.krugerdavid.com`), el navegador bloquea la petición por CORS si el servidor no permite ese origen. **Configura en el `.env` del servidor** (no en el local):

```env
# Incluir el origen del frontend local para poder probar contra la API desplegada
CORS_ALLOWED_ORIGINS=https://tu-frontend-produccion.com,http://localhost:5173
CORS_SUPPORTS_CREDENTIALS=true
```

Si usas Sanctum con cookies, añade también en el servidor:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost,tu-frontend-produccion.com,ng-api.krugerdavid.com
```

Después de cambiar `.env` en el servidor, ejecuta `php artisan config:clear` (y si usas cache de config, `php artisan config:cache`).

## Rutas afectadas

- `api/*` – Todas las rutas de la API.
- `sanctum/csrf-cookie` – Cookie CSRF para Sanctum (SPAs).

## Notas

- En producción **no** uses `CORS_ALLOWED_ORIGINS=*` si envías cookies o tokens; indica los dominios exactos.
- Si usas Sanctum con cookies (SPA), pon `CORS_SUPPORTS_CREDENTIALS=true` y configura `SANCTUM_STATEFUL_DOMAINS` en `config/sanctum.php` (o vía `.env`).

## Troubleshooting: "No 'Access-Control-Allow-Origin' header" desde localhost

Si ves en el navegador algo como:

```
Access to XMLHttpRequest at 'https://tu-api.com/api/...' from origin 'http://localhost:5173' has been blocked by CORS policy:
Response to preflight request doesn't pass access control check: No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

1. **Revisa el `.env` del servidor** (donde está desplegada la API, no tu máquina). Debe incluir `http://localhost:5173` en `CORS_ALLOWED_ORIGINS`, por ejemplo:
   ```env
   CORS_ALLOWED_ORIGINS=https://tu-dominio.com,http://localhost:5173
   ```
2. **Limpia la caché de configuración** en el servidor:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```
3. **Si usas Nginx u otro proxy**: asegúrate de que las peticiones **OPTIONS** (preflight) lleguen a PHP/Laravel y no devuelvan 4xx antes. Laravel es quien añade los headers CORS; si el proxy responde antes, no aparecerán.
