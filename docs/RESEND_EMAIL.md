# Correo con Resend (DigitalOcean + Netlify)

Guía para enviar correos transaccionales (invites, reset de password, etc.) desde **ng-api** en DigitalOcean usando [Resend](https://resend.com) (plan free: 100 correos/día).

## Arquitectura (quién hace qué)

| Pieza | URL / rol | ¿Envía mail? |
|-------|-----------|--------------|
| **bio-tracker** (React) | [https://ng-biotracker.netlify.app](https://ng-biotracker.netlify.app) | No. Solo recibe links (`/accept-invite?token=...`). |
| **ng-api** (Laravel) | [https://ng-api.krugerdavid.com](https://ng-api.krugerdavid.com) | Sí. Genera token y llama a Resend. |
| **Resend** | SMTP / API | Entrega el correo. |
| **Dominio de envío** | p.ej. `krugerdavid.com` | SPF + DKIM en DNS. **No** usar `*.netlify.app`. |

```
Admin invita en bio-tracker
        → POST ng-api (DigitalOcean)
        → Job / Mailable
        → Resend SMTP
        → Inbox del miembro
        → Clic en link → https://ng-biotracker.netlify.app/accept-invite?token=...
```

**Importante**

- `*.netlify.app` **no** sirve como dominio `From` (no verificás SPF/DKIM ahí).
- Sí sirve como `FRONTEND_URL` (destino del link del mail).
- Nunca commits de API keys. Si una key se filtró: revocarla en Resend y crear otra.

---

## Paso 1 — Cuenta Resend y API key

1. Entrá a [https://resend.com](https://resend.com) y creá una cuenta.
2. **API Keys** → **Create API Key** (permiso de envío).
3. Copiá la key (`re_...`) **una sola vez** y guardala en un gestor de secretos / el `.env` del droplet.
4. Plan free: ~100 correos/día (suficiente para invites).

Local: no hace falta Resend; usá `MAIL_MAILER=log`.

---

## Paso 2 — Verificar dominio de envío

Resend solo deja enviar a tu propio email de prueba hasta que verifiques un dominio.

1. En Resend: **Domains** → **Add Domain**.
2. **Recomendado si el dominio raíz ya usa Gmail / Google Workspace:** un subdominio dedicado, p.ej. `updates.krugerdavid.com` (también vale `mail.`). Así no tocás MX/SPF del root.

| Opción | From ejemplo | Cuándo |
|--------|--------------|--------|
| **Subdominio (recomendado)** | `noreply@updates.krugerdavid.com` | Root con Gmail; transactional separado. |
| Dominio raíz | `noreply@krugerdavid.com` | Solo si no hay conflicto con Gmail/SPF. |

3. Resend muestra records DNS (típicamente **DKIM** TXT/CNAME y a veces SPF) **para ese subdominio**.
4. En el panel DNS de `krugerdavid.com`, creá **exactamente** los records que indica Resend (no cambies los MX del root si usás Gmail).
5. Volvé a Resend → **Verify**. Puede tardar minutos (a veces hasta unas horas por propagación DNS).

Cuando el dominio esté **Verified**, podés enviar a cualquier destinatario.

### Qué no hacer

- No uses `MAIL_FROM_ADDRESS` con `@ng-biotracker.netlify.app`.
- No uses el SMTP del droplet ni `sendmail` del sistema para prod.
- No pongas la API key en el front (Netlify / Vite).
- No mezcles records de Resend en el apex si Gmail ya gestiona el correo entrante; preferí `updates.`.

---

## Paso 3 — Variables en el droplet (producción)

SSH al droplet donde corre ng-api y editá el `.env` del proyecto (ruta típica: `/var/www/ng-api` o la que uses):

```bash
ssh usuario@tu-droplet
cd /var/www/ng-api   # ajustar ruta
nano .env            # o vim
```

### Bloque de correo (Resend vía SMTP)

```env
APP_URL=https://ng-api.krugerdavid.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxxxxx   # API key de Resend (nunca en git)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@updates.krugerdavid.com
MAIL_FROM_NAME="NG Training"

# Destino de links en los mails (bio-tracker en Netlify)
FRONTEND_URL=https://ng-biotracker.netlify.app
```

Notas:

- `MAIL_FROM_ADDRESS` debe usar el **dominio verificado** en Resend (p.ej. `@updates.krugerdavid.com`).
- Si Laravel 12 / tu `config/mail.php` usa `MAIL_SCHEME` en lugar de `MAIL_ENCRYPTION`, podés probar:

```env
MAIL_SCHEME=smtps
MAIL_PORT=465
```

- Tras cambiar `.env`:

```bash
php artisan config:clear
php artisan config:cache
```

### Local (desarrollo)

En tu máquina, **no** copies la key de prod. Dejá:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
FRONTEND_URL=http://localhost:5173
```

Los mails se escriben en `storage/logs/laravel.log` (o el channel configurado).

---

## Paso 4 — Probar el envío desde el droplet

Con dominio verificado y `.env` cargado:

```bash
cd /var/www/ng-api
php artisan tinker
```

```php
Mail::raw('Prueba NG Training desde ng-api + Resend', function ($message) {
    $message->to('tu-email@ejemplo.com')
        ->subject('Prueba Resend ng-api');
});
```

Si falla:

1. Revisá `storage/logs/laravel.log`.
2. Confirmá en Resend → **Emails** / logs si el mensaje llegó a Resend.
3. Verificá que el dominio siga **Verified** y que `MAIL_FROM_ADDRESS` coincida.
4. Firewall del droplet: salida HTTPS/465 hacia internet (casi siempre abierta).

---

## Paso 5 — Cola (recomendado para invites)

Para que un invite no bloquee la request HTTP si Resend tarda, enviá los `Mailable` con `ShouldQueue` y un worker activo.

Ya hay guía: **[Cola en DigitalOcean](QUEUE_DIGITALOCEAN.md)**.

Checklist rápido:

```env
QUEUE_CONNECTION=database
```

```bash
php artisan migrate --force
# Supervisor con: php artisan queue:work database ...
```

Tras deploy de código de mails:

```bash
php artisan queue:restart
```

---

## Paso 6 — CORS (recordatorio)

El front en Netlify debe estar permitido en la API:

```env
CORS_ALLOWED_ORIGINS=https://ng-biotracker.netlify.app,http://localhost:5173
```

Detalle: **[CORS](CORS.md)**.

---

## Paso 7 — Checklist pre–invite en producción

- [ ] Dominio verificado en Resend (SPF/DKIM OK).
- [ ] API key solo en `.env` del droplet (key anterior filtrada → revocada).
- [ ] `MAIL_*` + `FRONTEND_URL` + `APP_URL` en prod.
- [ ] `php artisan config:cache` aplicado.
- [ ] Prueba `Mail::raw` llega a la bandeja (y no a spam).
- [ ] Worker de cola corriendo (si los mails van en queue).
- [ ] `CORS_ALLOWED_ORIGINS` incluye Netlify.
- [ ] Feature invite implementada (endpoint + Mailable + página accept en bio-tracker). Ver [MEMBER_INVITE.md](MEMBER_INVITE.md).
- [ ] Migración `member_invites` aplicada en el droplet (`php artisan migrate --force`).
- [ ] `FRONTEND_URL=https://ng-biotracker.netlify.app` en el `.env` del droplet.

---

## Troubleshooting

| Síntoma | Qué revisar |
|---------|-------------|
| “Domain not verified” / solo podés enviar a tu mail | Dominio en Resend aún no Verified; DNS incorrecto. |
| Mail en spam | DKIM/SPF; From coherente; evitar subjects tipo “URGENT”. |
| Nada en inbox ni en Resend | Credenciales SMTP; `config:cache` viejo; logs Laravel. |
| Link del mail apunta a localhost | `FRONTEND_URL` mal o cache de config. |
| Timeout en HTTP al invitar | Mail síncrono; pasar a queue + worker. |
| 401/CORS desde el front | `CORS_ALLOWED_ORIGINS` sin la URL de Netlify. |

---

## Referencia rápida de entornos

| Variable | Local | Producción |
|----------|-------|------------|
| `APP_URL` | `http://localhost` | `https://ng-api.krugerdavid.com` |
| `FRONTEND_URL` | `http://localhost:5173` | `https://ng-biotracker.netlify.app` |
| `MAIL_MAILER` | `log` | `smtp` |
| `MAIL_HOST` | — | `smtp.resend.com` |
| `MAIL_PASSWORD` | — | API key Resend |
| `MAIL_FROM_ADDRESS` | dummy | `noreply@updates.krugerdavid.com` (subdominio verificado) |

Cuando el feature de invite esté listo, los links del correo deben construirse así:

```text
{FRONTEND_URL}/accept-invite?token={token}
```
