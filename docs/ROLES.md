# Roles y permisos

Tres niveles de acceso: **root**, **admin** y **member**. Autorización con políticas de Laravel (DRY) y `Gate::before` para root.

## Roles

| Rol     | Descripción |
|--------|-------------|
| **root**  | Acceso total. Solo root puede crear/editar/eliminar usuarios (admin o member). |
| **admin** | Crear y gestionar miembros, pagos, bioimpedancia, planes. Ver audit log. No puede gestionar usuarios. |
| **member** | Solo acceso a **sus** datos: su ficha de miembro, sus pagos, su bioimpedancia, su plan. No ve otros miembros ni audit log. |

## Relación User ↔ Member

- **members.user_id** (nullable): cuando un usuario tiene rol `member`, puede estar vinculado a un único `Member` (su ficha).
- **User** tiene `member()` (hasOne) y **Member** tiene `user()` (belongsTo).
- En el futuro: al dar de alta un usuario `member`, se puede crear o vincular un `Member` y asignar `member.user_id = user.id`.
- El endpoint `GET /api/me` devuelve `member_id` cuando el usuario tiene rol member y está vinculado a un miembro.

## Crear usuarios (solo root)

- **POST /api/users**: crear usuario con `role: admin` o `role: member`. No se puede asignar `root` por API.
- Validación: `role` debe ser `admin` o `member`.
- **GET/PUT/DELETE /api/users/{id}**: solo root.

## Cómo está implementado (DRY)

1. **Enum** `App\Enums\Role`: valores `root`, `admin`, `member` y `assignableValues()` para validación.
2. **User**: `role` casteado a `Role`, helpers `isRoot()`, `isAdmin()`, `isMember()`, `canAccessAllMembers()`, relación `member()`.
3. **Policies** por modelo (User, Member, Bioimpedance, Payment, MembershipPlan, AuditLog): una política por recurso, descubiertas por convención.
4. **AuthServiceProvider**: `Gate::before` para que root tenga siempre `true` en cualquier capacidad.
5. **Repositorios**: `MemberRepository::searchForUser(User $user, ...)` devuelve todos los miembros para admin/root o solo el del usuario para member.
6. **Controladores**: `$this->authorize('action', $model)` en cada método; para listados por `memberId` se autoriza `view` sobre el `Member` correspondiente.

## Asignar rol a un usuario existente

Por seeder o tinker:

```php
$user = User::find(1);
$user->update(['role' => 'admin']);
```

Para vincular un usuario member a un miembro:

```php
$member = Member::find($memberUuid);
$member->update(['user_id' => $user->id]);
```

## Tests

- Los tests que requieren listar o crear miembros usan `User::factory()->admin()->create()`.
- La factory por defecto crea usuarios con `role: member`.
