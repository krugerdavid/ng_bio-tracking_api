# Sistema de auditoría

Registro automático de **acciones** (created/updated/deleted), **momento** (timestamp, request url/method) y **autor** (usuario autenticado o "System") para los modelos configurados.

## Diseño (DRY y estándares Laravel)

- **Un solo observer** (`App\Observers\AuditObserver`) para todos los modelos auditable: no se duplica lógica.
- **Configuración central** en `config/audit.php`: modelos, eventos, atributos excluidos y opción de cola.
- **Contrato + trait** (`AuditableContract` + `Auditable`): los modelos solo usan el trait e implementan el contrato; la exclusión de atributos es opcional por modelo.
- **Eventos de Eloquent**: se usa `created`, `updating` y `deleted`; no se tocan Actions ni Controladores.
- **Polimorfismo**: tabla `audit_logs` con `auditable_type` y `auditable_id` para cualquier modelo.
- **Autor**: `user_id` desde `auth()->user()` (nullable para consola o peticiones no autenticadas).
- **Contexto HTTP**: IP, user agent, URL y método cuando hay `request()`.

## Uso

### Activar auditoría en un modelo

1. Añadir el modelo al array `config/audit.php` → `'models'`.
2. En el modelo: `implements AuditableContract` y `use Auditable` (y opcionalmente `excludedAuditAttributes()`).

Ejemplo:

```php
use App\Contracts\AuditableContract;
use App\Traits\Auditable;

class Member extends Model implements AuditableContract
{
    use Auditable;

    public function excludedAuditAttributes(): array
    {
        return []; // o ['campo_sensible']
    }
}
```

### Consultar el registro

- **Listado (API)**: `GET /api/audit-logs` (protegido con `auth:sanctum`).
  - Filtros: `auditable_type`, `auditable_id`, `event`, `user_id`, `from`, `to`, `page_size`.
- **Detalle**: `GET /api/audit-logs/{id}`.
- **Por modelo**: `$member->auditLogs` (relación definida en el trait).
- **Por usuario**: `$user->auditLogs`.

### Variables de entorno

- `AUDIT_ENABLED`: activar/desactivar (default `true`).
- `AUDIT_QUEUE`: si es `true`, los registros se crean vía job en cola.
- `AUDIT_QUEUE_CONNECTION`: conexión de cola (opcional).

## Estructura de una entrada

Cada fila en `audit_logs` incluye:

- `event`: `created` | `updated` | `deleted`
- `auditable_type` / `auditable_id`: modelo afectado
- `old_values` / `new_values`: JSON (solo atributos no excluidos)
- `user_id`: quien realizó la acción (null = sistema/consola)
- `ip_address`, `user_agent`, `url`, `method`: contexto HTTP
- `created_at`: momento del evento

## Archivos relevantes

- `config/audit.php` – configuración
- `app/Contracts/AuditableContract.php` – contrato
- `app/Traits/Auditable.php` – trait y relación `auditLogs()`
- `app/Observers/AuditObserver.php` – lógica de registro
- `app/Models/AuditLog.php` – modelo
- `app/Jobs/RecordAuditJob.php` – job para registro en cola
- `app/Http/Controllers/Api/AuditLogController.php` – API de consulta
