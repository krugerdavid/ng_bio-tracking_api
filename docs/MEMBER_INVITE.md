# Invitación de miembros (acceso a la app)

Flujo para que un admin dé acceso a un miembro (User `role=member` vinculado) y el miembro active su contraseña por email (Resend).

## Endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| `POST` | `/api/members/{id}/invite` | admin/root | Crea/vincula User, envía mail (o reenvía). |
| `POST` | `/api/invite/accept` | público | Setea contraseña con el token del mail. |

### Invite — body

```json
{ "email": "opcional@si-el-miembro-ya-tiene" }
```

- Si el Member no tiene `email` → **obligatorio** en el body (422 si falta).
- Si ya tiene `user_id` → reenvía invitación (nuevo token; invalida el anterior).
- Respuesta: `MemberResource` + mensaje.

### Accept — body

```json
{
  "token": "...",
  "password": "...",
  "password_confirmation": "..."
}
```

Link del correo:

```text
{FRONTEND_URL}/accept-invite?token={token}
```

Prod: `https://ng-biotracker.netlify.app/accept-invite?token=...`

Token válido **72 horas**, un solo uso efectivo (al aceptar se marca `accepted_at`).

## Configuración requerida

Ver **[RESEND_EMAIL.md](RESEND_EMAIL.md)**.

```env
FRONTEND_URL=https://ng-biotracker.netlify.app
MAIL_*  # Resend en prod
QUEUE_CONNECTION=database  # recomendado (Mail implementa ShouldQueue)
```

Tras deploy:

```bash
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
```

## UI (bio-tracker)

- Detalle de miembro → ícono de sobre → “Dar acceso” / “Reenviar”.
- Ruta pública `/accept-invite`.

## Permisos member (lectura)

Tras invite, el rol `member` solo **ve** su ficha, bio, pagos y plan. No puede crear/editar/borrar esos recursos.
