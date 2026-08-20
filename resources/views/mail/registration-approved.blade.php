<x-mail::message>
# Hola {{ $memberName }}

Tu registro en **NG Training** ya fue aprobado. Ya podés iniciar sesión con el **email y la contraseña** que usaste al registrarte.

<x-mail::button :url="$loginUrl">
Entrar a NG Training
</x-mail::button>

Ahí vas a ver tu bioimpedancia, pagos y estado de cuenta.

Si no pediste esta cuenta, podés ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
