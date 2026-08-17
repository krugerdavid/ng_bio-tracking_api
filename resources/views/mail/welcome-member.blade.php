<x-mail::message>
# Hola {{ $memberName }}

¡Bienvenido a **NG Training**! Recibimos tu registro{{ $groupLine }}.

Tu cuenta está **pendiente de aprobación**. En cuanto el profe la confirme, vas a poder iniciar sesión y ver tu bioimpedancia, historial de pagos y estado de cuenta.

Si no hiciste este registro, podés ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
