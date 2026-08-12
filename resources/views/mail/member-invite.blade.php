<x-mail::message>
# Hola {{ $memberName }}

Te invitaron a acceder a **NG Training** para ver tu bioimpedancia, historial de pagos y estado de cuenta.

Hacé clic en el botón para crear tu contraseña y activar el acceso. El enlace vence en **{{ $expiresInHours }} horas**.

<x-mail::button :url="$acceptUrl">
Activar acceso
</x-mail::button>

Si no pediste esta invitación, podés ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
