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

## Rutas afectadas

- `api/*` – Todas las rutas de la API.
- `sanctum/csrf-cookie` – Cookie CSRF para Sanctum (SPAs).

## Notas

- En producción **no** uses `CORS_ALLOWED_ORIGINS=*` si envías cookies o tokens; indica los dominios exactos.
- Si usas Sanctum con cookies (SPA), pon `CORS_SUPPORTS_CREDENTIALS=true` y configura `SANCTUM_STATEFUL_DOMAINS` en `config/sanctum.php` (o vía `.env`).
