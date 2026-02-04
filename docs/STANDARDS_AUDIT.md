# Auditoría de estándares Laravel (enfoque senior)

Evaluación del proyecto frente a buenas prácticas y estándares Laravel de nivel senior, y plan de mejoras.

**Actualización (tests extendidos):** Se añadieron Feature tests por recurso (Users, Members, Payments, Bioimpedance, MembershipPlan, AuditLog) cubriendo 401, 403, 404, 422 y flujos de éxito, y Unit tests para LoginAction, CreateUserAction y MemberRepository. Factories: Payment, Bioimpedance, MembershipPlan. Total ~70 tests.

---

## Lo que está bien resuelto

| Área | Estado | Detalle |
|------|--------|---------|
| **Estructura** | ✅ | Separación clara: Actions, Repositories, Policies, Resources. |
| **Autorización** | ✅ | Policies por modelo, `$this->authorize()` en controladores, Gate::before para root. |
| **API** | ✅ | Respuesta unificada (ApiResponse), recursos JSON, rutas apiResource. |
| **Autenticación** | ✅ | Sanctum, login/logout/me, tokens. |
| **Enums** | ✅ | Role como enum (PHP 8.1). |
| **Repositorios** | ✅ | BaseRepository + implementaciones, lógica de negocio fuera del controlador. |
| **Actions** | ✅ | Casos de uso en clases (LoginAction, CreateMemberAction, etc.). |
| **Factories** | ✅ | UserFactory con estados admin()/root(), MemberFactory. |
| **Tests (base)** | ✅ | Pest, SQLite :memory:, AuthTest y MemberTest con Sanctum. |
| **Auditoría** | ✅ | AuditLog, observer, jobs. |
| **Documentación** | ✅ | Docs de CORS, permisos, BD, Digital Ocean. |

---

## Gaps y mejoras recomendadas

### 1. Form Requests (validación)

**Estado:** La validación está inline en controladores (`$request->validate([...])`).

**Problema:** Reglas repetidas, más difícil reutilizar y testear validación de forma aislada.

**Recomendación (senior):** Usar Form Requests por recurso/acción, por ejemplo:
- `StoreMemberRequest`, `UpdateMemberRequest`
- `StoreUserRequest`, `UpdateUserRequest`
- `LoginRequest`
- Y equivalentes para Payment, Bioimpedance, MembershipPlan donde aplique.

**Beneficios:** Reglas en un solo lugar, mensajes personalizados, autorización opcional en el request, tests unitarios de validación.

---

### 2. Cobertura y rigor de tests

**Estado actual:**
- **Feature:** Auth (2), Member (10), User (15), Payment (8), Bioimpedance (6), MembershipPlan (7), AuditLog (7). Cubren 401, 403, 404, 422 y flujos de éxito por recurso.
- **Unit:** LoginAction (4), CreateUserAction (4), MemberRepository (6). Cubren lógica de login, creación de usuario con roles y búsqueda de miembros por rol.
- Factories: User, Member, Payment, Bioimpedance, MembershipPlan.
- `ExampleTest` (Feature y Unit) siguen como placeholders; se pueden eliminar o reemplazar.

**Recomendación (senior):**
- **Feature:** Por recurso, cubrir al menos: éxito 200/201, 404, 403 (roles), 422 (validación), no autenticado (401).
- **Unit:** Actions (LoginAction, CreateUserAction, etc.) y Repositories donde haya lógica (ej. MemberRepository::searchForUser).
- Eliminar o reemplazar los `ExampleTest` por tests reales.
- Opcional: configurar cobertura en `phpunit.xml` (coverageHtml/coverageText) y fijar un mínimo (ej. 70–80 %) en CI.

---

### 3. Compatibilidad MySQL

**Estado:** `MemberRepository` usa `ilike` (PostgreSQL). En producción usáis MySQL.

**Problema:** En MySQL no existe `ILIKE`; las consultas fallarían.

**Recomendación:** Sustituir `ilike` por `like` (en MySQL con collation habitual la búsqueda es case-insensitive) o usar un helper/scope que use `ILIKE` en PostgreSQL y `LIKE` en MySQL para mantener el mismo comportamiento.

---

### 4. Strict types

**Estado:** No hay `declare(strict_types=1);` en clases de `app/`.

**Recomendación:** Añadir `declare(strict_types=1);` en todos los archivos PHP de `app/` (y opcionalmente en `database/`). Mejora type-safety y detecta errores antes.

---

### 5. Registro explícito de Policies

**Estado:** No hay `Gate::policy(Model::class, ModelPolicy::class)` en `AuthServiceProvider`; se depende del auto-discovery de Laravel.

**Recomendación:** Para proyectos senior suele preferirse registro explícito en `AuthServiceProvider` con el array `$policies`, para que quede claro qué modelo usa qué policy y evitar sorpresas con convenciones.

---

### 6. Contratos (interfaces) para repositorios/actions

**Estado:** Actions implementan `Action` (interface). Repositories no tienen interface.

**Recomendación:** Opcional pero muy senior: definir interfaces para repositorios (ej. `MemberRepositoryInterface`) y binding en `AppServiceProvider`. Facilita tests con mocks y cambio de implementación.

---

### 7. Configuración de cobertura en PHPUnit

**Estado:** En `phpunit.xml` hay `<source>` pero no `coverage` reporting (coverageHtml, etc.).

**Recomendación:** Añadir algo como:

```xml
<coverage>
    <report>
        <html outputDirectory="build/coverage"/>
        <text outputFile="build/coverage.txt"/>
    </report>
</coverage>
```

y opcionalmente `include`/`exclude` y requisitos de cobertura mínima.

---

### 8. Consistencia de IDs (User vs Member)

**Estado:** User usa `id` numérico; otros modelos (Member, etc.) pueden usar UUID (HasUuid). UserController usa `(int) $id` correctamente para User.

**Recomendación:** Dejar documentado o en comentario que los usuarios se identifican por `id` entero y el resto por UUID, para evitar confusiones en futuras extensiones.

---

## Resumen ejecutivo

- **Arquitectura y patrones:** Muy alineados con estándares Laravel (Actions, Repositories, Policies, Resources). El proyecto está bien encaminado para nivel senior.
- **Validación:** Funcional pero mejorable con Form Requests.
- **Tests:** Ampliados con Feature por recurso (401/403/404/422/éxito) y Unit para Actions y MemberRepository. Nivel adecuado para considerarse rigurosos en los recursos cubiertos.
- **Producción:** Corregir uso de `ilike` en repositorios para MySQL.
- **Pulido:** Strict types, registro explícito de policies y opcionalmente interfaces + cobertura completarían un perfil senior.

Prioridad sugerida: (1) compatibilidad MySQL en búsquedas, (2) ampliar tests (Feature + Unit), (3) Form Requests, (4) strict types y (5) resto de mejoras opcionales.
