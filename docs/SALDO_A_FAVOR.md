# Saldo a favor (credit balance)

El backend soporta **saldo a favor** del deportista para que los excedentes de pago se apliquen a la deuda futura.

## Persistencia

- Columna **`members.credit_balance`** (decimal, default 0). El excedente de cada pago se suma aquí.

## Comportamiento

### 1. Al registrar un pago (`POST /payments`)

- Si el pago tiene **`status: 'paid'** y el **monto es mayor** que la cuota mensual del plan del miembro:
  - Se registra el pago con el monto indicado.
  - El **excedente** (`amount - monthly_fee`) se **suma** a `member.credit_balance`.
- Si el miembro no tiene plan activo o el monto no supera la cuota, no se modifica el saldo a favor.

### 2. Cálculo de deuda (`GET /members/{id}/debt`)

Respuesta:

- **`monthly_fee`**: cuota mensual del plan activo.
- **`owed_months`**: lista de meses (YYYY-MM) sin pago con status `paid`.
- **`months_owed`**: cantidad de meses adeudados.
- **`total_debt`**: `months_owed × monthly_fee`.
- **`credit_balance`**: saldo a favor del miembro.
- **`total_debt_after_credit`**: `max(0, total_debt - credit_balance)` (deuda que el frontend puede mostrar como “a pagar”).

Los meses en mora se calculan desde `plan.start_date` hasta el mes actual; cada mes sin un pago con status `paid` cuenta como adeudado.

### 3. En el recurso del miembro

- **`GET /members/{id}`** y listados incluyen **`credit_balance`** en el JSON para que el frontend muestre el saldo a favor.

## Ejemplo

1. Plan: cuota 100, start_date hace 3 meses. Sin pagos → `total_debt` = 300, `credit_balance` = 0, `total_debt_after_credit` = 300.
2. Se registra un pago de 150 para el primer mes (status `paid`). Excedente 50 → `credit_balance` = 50.
3. `GET /members/{id}/debt` → `total_debt` sigue 300 (hasta que haya más pagos que cubran meses), `credit_balance` = 50, `total_debt_after_credit` = 250.

## Tests

- **Payment:** pago con monto > cuota y status `paid` incrementa `credit_balance`.
- **Member:** `GET .../debt` devuelve la estructura indicada; `GET .../members/{id}` incluye `credit_balance`.
