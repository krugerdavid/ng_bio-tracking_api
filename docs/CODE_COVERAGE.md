# Cobertura de código

Al ejecutar `php artisan test --coverage` el total debería acercarse a **80%** o más tras las mejoras aplicadas. Este documento resume **qué está cubierto** y **qué puede quedar sin cubrir**.

---

## Qué está cubierto (tras las mejoras)

- **Controladores API:** Flujos principales (éxito, 401, 403, 404, 422) de Users, Members, Payments, Bioimpedance, MembershipPlan, AuditLog y Auth (login, logout, me).
- **Actions con tests unitarios:** LoginAction, CreateUserAction, LogoutAction, CreateMemberAction, UpdateMemberAction, DeleteMemberAction, CreatePaymentAction, RecordBioimpedanceAction, UpdateMembershipPlanAction.
- **Repositorios:** MemberRepository (searchForUser, search), PaymentRepository (findByMonth y uso en CreatePaymentAction).
- **Observer y auditoría:** AuditObserverTest comprueba que al crear/actualizar/borrar un Member se crean entradas en `audit_logs` (eventos created, updated, deleted).
- **Enum Role:** assignableValues() y label() (RoleEnumTest).
- **Policies y Form Requests:** Ejecutados vía Feature tests.
- **Providers:** Excluidos del cálculo de cobertura en `phpunit.xml` (`app/Providers`).

---

## Qué puede seguir sin cubrir

| Origen | Motivo |
|--------|--------|
| **Manejo excepción Bcrypt** | El bloque en `bootstrap/app.php` que devuelve 422 para "Bcrypt algorithm" solo se ejecuta si un usuario tiene contraseña no-Bcrypt; no se simula en tests. |
| **RecordAuditJob** | Si `config('audit.queue')` es true, se usa el job; en tests suele ser false y se llama a createAuditRecord directamente. El job se cubre cuando la cola está activa. |
| **Rutas de error del Observer** | El catch en AuditObserver::createAuditRecord y en RecordAuditJob::handle (log + throw) no se fuerzan en tests. |
| **Algunos accessors** | Por ejemplo AuditLog::getCauserNameAttribute solo se usa al serializar el recurso en contextos concretos. |

---

## Cómo ver el detalle

Para ver **qué líneas concretas** no están cubiertas:

```bash
php artisan test --coverage --coverage-html=build/coverage
```

Luego abre `build/coverage/index.html` en el navegador.

---

## Resumen de tests añadidos (para subir cobertura)

- **Unit:** LogoutActionTest, CreateMemberActionTest, UpdateMemberActionTest, DeleteMemberActionTest, CreatePaymentActionTest, RecordBioimpedanceActionTest, UpdateMembershipPlanActionTest, RoleEnumTest, PaymentRepositoryTest.
- **Feature:** AuditObserverTest (crear/actualizar/borrar Member y comprobar entradas en audit_logs).
- **Exclusión:** `app/Providers` en `phpunit.xml` para no penalizar código de arranque.
